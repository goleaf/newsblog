<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $user;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->user = User::factory()->create(['role' => 'user']);
        $this->post = Post::factory()->create(['status' => 'published']);
    }

    public function test_store_creates_comment_with_spam_detection(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a great article!',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'status' => CommentStatus::Pending->value,
        ]);
    }

    // Note: IP blocking via blockIp is tested in SpamDetectionServiceTest
    // Route rate limiting is handled by middleware and tested separately

    public function test_reply_creates_nested_comment(): void
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Approved,
        ]);

        $response = $this->post(route('comments.reply'), [
            'parent_id' => $parentComment->id,
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
            'content' => 'This is a reply!',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Jane Doe',
            'status' => CommentStatus::Pending->value,
        ]);
    }

    public function test_reply_prevents_exceeding_max_nesting_level(): void
    {
        // Create a comment chain at max depth (3 levels)
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
            'status' => CommentStatus::Approved,
        ]);

        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level1->id,
            'status' => CommentStatus::Approved,
        ]);

        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level2->id,
            'status' => CommentStatus::Approved,
        ]);

        // Try to reply to level 3 (would create level 4, exceeding limit)
        $response = $this->post(route('comments.reply'), [
            'parent_id' => $level3->id,
            'author_name' => 'Test User',
            'author_email' => 'test@example.com',
            'content' => 'This should fail',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Maximum nesting level reached. Cannot reply to this comment.');
    }

    public function test_approve_changes_comment_status_to_approved(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('comments.approve', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment approved successfully.');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Approved->value,
        ]);
    }

    public function test_approve_requires_admin_or_editor_role(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        // Regular user cannot approve
        $response = $this->actingAs($this->user)
            ->post(route('comments.approve', $comment));

        $response->assertForbidden();

        // Editor can approve
        $response = $this->actingAs($this->editor)
            ->post(route('comments.approve', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_approve_handles_already_approved_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Approved,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('comments.approve', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('info', 'Comment is already approved.');
    }

    public function test_reject_changes_comment_status_to_spam(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('comments.reject', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment rejected successfully.');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Rejected->value,
        ]);
    }

    public function test_reject_requires_admin_or_editor_role(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        // Regular user cannot reject
        $response = $this->actingAs($this->user)
            ->post(route('comments.reject', $comment));

        $response->assertForbidden();

        // Editor can reject
        $response = $this->actingAs($this->editor)
            ->post(route('comments.reject', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_reject_handles_already_spam_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Rejected,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('comments.reject', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('info', 'Comment is already rejected.');
    }

    public function test_destroy_deletes_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment deleted successfully.');

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_destroy_allows_user_to_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_destroy_allows_admin_to_delete_any_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_destroy_prevents_user_from_deleting_other_users_comment(): void
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('comments.destroy', $comment));

        $response->assertForbidden();
    }

    public function test_destroy_requires_authentication(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
        ]);

        $response = $this->delete(route('comments.destroy', $comment));

        $response->assertRedirect(route('login'));
    }
}

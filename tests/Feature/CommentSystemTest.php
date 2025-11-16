<?php

namespace Tests\Feature;

use App\Enums\CommentStatus;
use App\Jobs\SendCommentApprovedNotification;
use App\Jobs\SendCommentReplyNotification;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected Post $post;

    protected User $admin;

    protected User $editor;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->post = Post::factory()->create(['status' => 'published']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    // ============================================
    // COMMENT SUBMISSION TESTS
    // ============================================

    public function test_guest_can_submit_comment(): void
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

    public function test_comment_submission_requires_valid_post(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => 99999,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertSessionHasErrors(['post_id']);
    }

    public function test_comment_submission_requires_all_fields(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
        ]);

        $response->assertSessionHasErrors(['author_name', 'author_email', 'content']);
    }

    public function test_comment_submission_stores_ip_address_and_user_agent(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $comment = Comment::where('post_id', $this->post->id)->first();

        $this->assertNotNull($comment->ip_address);
        $this->assertNotNull($comment->user_agent);
    }

    public function test_comment_submission_with_parent_id_creates_reply(): void
    {
        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Approved,
        ]);

        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Jane Doe',
            'author_email' => 'jane@example.com',
            'content' => 'This is a reply',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'parent_id' => $parentComment->id,
            'author_name' => 'Jane Doe',
        ]);
    }

    // ============================================
    // SPAM DETECTION TESTS
    // ============================================

    public function test_spam_detection_marks_comment_with_excessive_links_as_spam(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'Spammer',
            'author_email' => 'spam@example.com',
            'content' => 'Check http://spam1.com and http://spam2.com and http://spam3.com and http://spam4.com',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Spam->value,
        ]);
    }

    public function test_spam_detection_allows_comments_with_acceptable_link_count(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Check http://example.com and http://test.com',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending->value,
        ]);
    }

    public function test_spam_detection_marks_comment_with_blacklisted_keywords_as_spam(): void
    {
        // Test with 'viagra' keyword
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'Spammer',
            'author_email' => 'spam@example.com',
            'content' => 'Buy viagra now!',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Spam->value,
        ]);
    }

    public function test_spam_detection_marks_comment_with_casino_keyword_as_spam(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'Spammer',
            'author_email' => 'spam2@example.com',
            'content' => 'Check out this casino offer!',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Spam->value,
        ]);
    }

    public function test_spam_detection_marks_quick_submission_as_spam(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'Bot',
            'author_email' => 'bot@example.com',
            'content' => 'Quick comment',
            'page_load_time' => time() - 1, // Less than 3 seconds
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Spam->value,
        ]);
    }

    public function test_spam_detection_allows_normal_submission_speed(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Normal comment',
            'page_load_time' => time() - 10, // More than 3 seconds
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending->value,
        ]);
    }

    public function test_spam_detection_marks_comment_with_filled_honeypot_as_spam(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'Bot',
            'author_email' => 'bot@example.com',
            'content' => 'Bot comment',
            'page_load_time' => time() - 10,
            'honeypot' => 'bot filled this',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Spam->value,
        ]);
    }

    public function test_spam_detection_allows_empty_honeypot(): void
    {
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Legitimate comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending->value,
        ]);
    }

    // ============================================
    // NESTED REPLIES TESTS
    // ============================================

    public function test_nested_replies_can_be_created_up_to_3_levels(): void
    {
        // Level 1 (depth 0)
        $level1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
            'status' => CommentStatus::Approved,
        ]);

        $this->assertEquals(0, $level1->depth());
        $this->assertTrue($level1->canReply());

        // Level 2 (depth 1)
        $level2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level1->id,
            'status' => CommentStatus::Approved,
        ]);

        $level2->refresh();
        $this->assertEquals(1, $level2->depth());
        $this->assertTrue($level2->canReply());

        // Level 3 (depth 2) - maximum allowed
        $level3 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $level2->id,
            'status' => CommentStatus::Approved,
        ]);

        $level3->refresh();
        $this->assertEquals(2, $level3->depth());
        $this->assertFalse($level3->canReply());
    }

    public function test_reply_method_prevents_exceeding_max_nesting_level(): void
    {
        // Create a comment chain at max depth
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

    public function test_reply_method_allows_replies_within_nesting_limit(): void
    {
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

        // Should be able to reply to level 2 (creates level 3, which is within limit)
        $response = $this->post(route('comments.reply'), [
            'parent_id' => $level2->id,
            'author_name' => 'Test User',
            'author_email' => 'test@example.com',
            'content' => 'This should work',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'parent_id' => $level2->id,
        ]);
    }

    public function test_nested_replies_load_correctly_via_relationships(): void
    {
        $parent = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => null,
            'status' => CommentStatus::Approved,
        ]);

        $reply1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $parent->id,
            'status' => CommentStatus::Approved,
        ]);

        $reply2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $parent->id,
            'status' => CommentStatus::Approved,
        ]);

        $nestedReply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $reply1->id,
            'status' => CommentStatus::Approved,
        ]);

        $parent->load('replies.replies');

        $this->assertCount(2, $parent->replies);
        $this->assertCount(1, $reply1->replies);
        $this->assertCount(0, $reply2->replies);
        $this->assertEquals($nestedReply->id, $reply1->replies->first()->id);
    }

    public function test_reply_dispatches_notification_job(): void
    {
        Queue::fake();

        $parentComment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Approved,
        ]);

        $this->post(route('comments.reply'), [
            'parent_id' => $parentComment->id,
            'author_name' => 'Reply Author',
            'author_email' => 'reply@example.com',
            'content' => 'This is a reply',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        Queue::assertPushed(SendCommentReplyNotification::class);
    }

    // ============================================
    // MODERATION WORKFLOW TESTS
    // ============================================

    public function test_admin_can_approve_pending_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('comments.approve', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment approved successfully.');

        $comment->refresh();
        $this->assertEquals(CommentStatus::Approved, $comment->status);

        Queue::assertPushed(SendCommentApprovedNotification::class);
    }

    public function test_editor_can_approve_pending_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->editor)
            ->post(route('comments.approve', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $comment->refresh();
        $this->assertEquals(CommentStatus::Approved, $comment->status);
    }

    public function test_regular_user_cannot_approve_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('comments.approve', $comment));

        $response->assertForbidden();
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

    public function test_admin_can_reject_pending_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('comments.reject', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment rejected successfully.');

        $comment->refresh();
        $this->assertEquals(CommentStatus::Rejected, $comment->status);
    }

    public function test_editor_can_reject_pending_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->editor)
            ->post(route('comments.reject', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $comment->refresh();
        $this->assertEquals(CommentStatus::Rejected, $comment->status);
    }

    public function test_regular_user_cannot_reject_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('comments.reject', $comment));

        $response->assertForbidden();
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

    public function test_moderation_workflow_approve_then_reject(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'status' => CommentStatus::Pending,
        ]);

        // Approve
        $this->actingAs($this->admin)
            ->post(route('comments.approve', $comment));

        $comment->refresh();
        $this->assertEquals(CommentStatus::Approved, $comment->status);

        // Reject
        $this->actingAs($this->admin)
            ->post(route('comments.reject', $comment));

        $comment->refresh();
        $this->assertEquals(CommentStatus::Rejected, $comment->status);
    }

    public function test_user_can_delete_own_comment(): void
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

    public function test_admin_can_delete_any_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }

    public function test_user_cannot_delete_other_users_comment(): void
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

    public function test_complete_moderation_workflow(): void
    {
        // 1. Submit comment
        $response = $this->post(route('comments.store'), [
            'post_id' => $this->post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a test comment',
            'page_load_time' => time() - 10,
            'honeypot' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $comment = Comment::where('post_id', $this->post->id)
            ->where('author_email', 'john@example.com')
            ->first();

        $this->assertEquals(CommentStatus::Pending, $comment->status);

        // 2. Approve comment
        Queue::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('comments.approve', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $comment->refresh();
        $this->assertEquals(CommentStatus::Approved, $comment->status);
        Queue::assertPushed(SendCommentApprovedNotification::class);

        // 3. Reject comment
        $response = $this->actingAs($this->admin)
            ->post(route('comments.reject', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $comment->refresh();
        $this->assertEquals(CommentStatus::Rejected, $comment->status);

        // 4. Delete comment
        $response = $this->actingAs($this->admin)
            ->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }
}

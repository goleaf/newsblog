<?php

namespace Tests\Feature\Nova;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
    }

    public function test_admin_can_view_comments_index(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(5)->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments');

        $response->assertOk()
            ->assertJsonStructure([
                'resources',
            ]);
    }

    public function test_editor_can_view_comments_index(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(5)->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->editor)
            ->getJson('/nova-api/comments');

        $response->assertOk();
    }

    public function test_author_can_view_comments_index(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->count(5)->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->author)
            ->getJson('/nova-api/comments');

        $response->assertOk();
    }

    public function test_admin_can_create_comment(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/comments', [
                'post_id' => $post->id,
                'content' => 'Test comment content',
                'status' => 'approved',
                'author_name' => 'Test Author',
                'author_email' => 'test@example.com',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'content' => 'Test comment content',
        ]);
    }

    public function test_editor_can_create_comment(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->editor)
            ->postJson('/nova-api/comments', [
                'post_id' => $post->id,
                'content' => 'Editor comment',
                'status' => 'approved',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'content' => 'Editor comment',
        ]);
    }

    public function test_admin_can_update_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/nova-api/comments/{$comment->id}", [
                'post_id' => $post->id,
                'content' => 'Updated content',
                'status' => $comment->status,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_editor_can_update_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->editor)
            ->putJson("/nova-api/comments/{$comment->id}", [
                'post_id' => $post->id,
                'content' => 'Editor updated',
                'status' => $comment->status,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Editor updated',
        ]);
    }

    public function test_admin_can_delete_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/nova-api/comments?resources[]={$comment->id}");

        $response->assertOk();
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_editor_can_delete_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->editor)
            ->deleteJson("/nova-api/comments?resources[]={$comment->id}");

        $response->assertOk();
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_author_cannot_delete_comment(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->author)
            ->deleteJson("/nova-api/comments?resources[]={$comment->id}");

        $response->assertForbidden();
    }

    public function test_comment_creation_requires_content(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/comments', [
                'post_id' => $post->id,
                'status' => 'pending',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_comment_creation_requires_post_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/comments', [
                'content' => 'Test comment',
                'status' => 'pending',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['post_id']);
    }

    public function test_can_create_guest_comment_with_author_details(): void
    {
        $post = Post::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/comments', [
                'post_id' => $post->id,
                'content' => 'Guest comment',
                'status' => 'pending',
                'author_name' => 'Guest User',
                'author_email' => 'guest@example.com',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'content' => 'Guest comment',
            'author_name' => 'Guest User',
            'author_email' => 'guest@example.com',
        ]);
    }

    public function test_can_create_authenticated_user_comment(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/comments', [
                'post_id' => $post->id,
                'user_id' => $user->id,
                'content' => 'User comment',
                'status' => 'approved',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'content' => 'User comment',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_create_reply_to_comment(): void
    {
        $post = Post::factory()->create();
        $parentComment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/comments', [
                'post_id' => $post->id,
                'parent_id' => $parentComment->id,
                'content' => 'Reply comment',
                'status' => 'approved',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'content' => 'Reply comment',
            'parent_id' => $parentComment->id,
        ]);
    }

    public function test_admin_can_change_comment_status(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/nova-api/comments/{$comment->id}", [
                'post_id' => $post->id,
                'content' => $comment->content,
                'status' => 'approved',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'status' => 'approved',
        ]);
    }
}

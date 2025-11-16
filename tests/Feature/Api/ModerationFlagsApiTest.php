<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\CommentFlag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationFlagsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function editor(): User
    {
        return User::factory()->create(['role' => 'editor']);
    }

    protected function user(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_non_admin_cannot_list_flags(): void
    {
        $user = $this->user();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/moderation/flags');
        $response->assertStatus(403);
    }

    public function test_admin_can_list_open_flags(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => \App\Enums\CommentStatus::Approved]);
        CommentFlag::factory()->create(['comment_id' => $comment->id, 'status' => 'open', 'reason' => 'spam']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/moderation/flags?status=open');
        $response->assertOk();
        $response->assertJsonStructure(['data', 'links']);
        $this->assertGreaterThan(0, $response->json('total'));
    }

    public function test_editor_can_review_flag(): void
    {
        $editor = $this->editor();
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => \App\Enums\CommentStatus::Approved]);
        $flag = CommentFlag::factory()->create(['comment_id' => $comment->id, 'status' => 'open', 'reason' => 'spam']);

        $response = $this->actingAs($editor, 'sanctum')->postJson(
            "/api/v1/moderation/flags/{$flag->id}/review",
            ['status' => 'reviewed']
        );

        $response->assertOk()->assertJson([
            'success' => true,
            'status' => 'reviewed',
        ]);

        $this->assertDatabaseHas('comment_flags', [
            'id' => $flag->id,
            'status' => 'reviewed',
        ]);
    }

    public function test_editor_can_bulk_review_flags(): void
    {
        $editor = $this->editor();
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => \App\Enums\CommentStatus::Approved]);
        $flags = CommentFlag::factory()->count(3)->create(['comment_id' => $comment->id, 'status' => 'open']);

        $ids = $flags->pluck('id')->all();

        $response = $this->actingAs($editor, 'sanctum')->postJson(
            '/api/v1/moderation/flags/bulk-review',
            ['ids' => $ids, 'status' => 'resolved']
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'resolved',
            ]);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('comment_flags', [
                'id' => $id,
                'status' => 'resolved',
            ]);
        }
    }

    public function test_user_cannot_bulk_review_flags(): void
    {
        $user = $this->user();
        $response = $this->actingAs($user, 'sanctum')->postJson(
            '/api/v1/moderation/flags/bulk-review',
            ['ids' => [1], 'status' => 'resolved']
        );

        $response->assertStatus(403);
    }
}

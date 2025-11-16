<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentFlagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_flag_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => \App\Enums\CommentStatus::Approved,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/comments/{$comment->id}/flags",
            ['reason' => 'spam', 'notes' => 'Looks like spam.']
        );

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'status' => 'open',
            ]);

        $this->assertDatabaseHas('comment_flags', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);
    }

    public function test_flag_validation_rejects_invalid_reason(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => \App\Enums\CommentStatus::Approved,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/comments/{$comment->id}/flags",
            ['reason' => 'not-a-reason']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }
}

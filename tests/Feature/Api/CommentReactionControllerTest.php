<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CommentReactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('cache.default', 'array');
    }

    public function test_authenticated_user_can_react_to_comment(): void
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
            "/api/v1/comments/{$comment->id}/reactions",
            ['type' => CommentReaction::TYPES[0]]
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);

        $this->assertDatabaseHas('comment_reactions', [
            'comment_id' => $comment->id,
            'user_id' => $user->id,
            'type' => CommentReaction::TYPES[0],
        ]);
    }

    public function test_reaction_validation_rejects_invalid_type(): void
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
            "/api/v1/comments/{$comment->id}/reactions",
            ['type' => 'invalid-type']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $this->assertDatabaseCount('comment_reactions', 0);
    }
}

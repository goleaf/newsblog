<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_comments_for_post(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Comment::factory()->count(2)->create([
            'post_id' => $post->id,
            'status' => \App\Enums\CommentStatus::Approved,
        ]);

        $res = $this->getJson('/api/v1/comments?post_id='.$post->id);
        $res->assertOk();
        $this->assertArrayHasKey('data', $res->json());
        $this->assertGreaterThanOrEqual(1, $res->json('total'));
    }

    public function test_authenticated_user_can_create_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $payload = [
            'post_id' => $post->id,
            'author_name' => 'Alice',
            'author_email' => 'alice@example.com',
            'content' => 'Great post! Thanks for sharing.',
        ];

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/comments', $payload);
        $res->assertCreated();
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'author_name' => 'Alice',
        ]);
    }
}

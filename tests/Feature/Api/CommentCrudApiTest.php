<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentCrudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_own_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Original content',
        ]);

        $res = $this->actingAs($user, 'sanctum')->putJson('/api/v1/comments/'.$comment->id, [
            'content' => 'Updated comment content',
        ]);

        $res->assertOk();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment content',
        ]);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Something to remove',
        ]);

        $res = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/comments/'.$comment->id);

        $res->assertNoContent();
        $this->assertSoftDeleted('comments', [
            'id' => $comment->id,
        ]);
    }
}

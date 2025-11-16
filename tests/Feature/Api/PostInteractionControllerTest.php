<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PostInteractionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
    }

    public function test_authenticated_user_can_react_to_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/posts/{$post->id}/reactions",
            ['type' => Reaction::TYPES[0]]
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 1,
            ]);

        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => Reaction::TYPES[0],
        ]);
    }

    public function test_reaction_validation_rejects_invalid_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/posts/{$post->id}/reactions",
            ['type' => 'invalid-type']
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_authenticated_user_can_toggle_bookmark(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $bookmarkResponse = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/posts/{$post->id}/bookmark"
        );

        $bookmarkResponse->assertOk()
            ->assertJson([
                'success' => true,
                'bookmarked' => true,
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $unbookmarkResponse = $this->actingAs($user, 'sanctum')->postJson(
            "/api/v1/posts/{$post->id}/bookmark"
        );

        $unbookmarkResponse->assertOk()
            ->assertJson([
                'success' => true,
                'bookmarked' => false,
            ]);

        $this->assertDatabaseMissing('bookmarks', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }
}



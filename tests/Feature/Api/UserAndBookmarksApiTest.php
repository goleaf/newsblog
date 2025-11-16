<?php

namespace Tests\Feature\Api;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAndBookmarksApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_endpoint_returns_current_user(): void
    {
        $user = User::factory()->create();
        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users/me');
        $res->assertOk();
        $this->assertSame($user->id, $res->json('id'));
    }

    public function test_bookmarks_list_returns_users_bookmarks(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        Bookmark::create(['user_id' => $user->id, 'post_id' => $post->id]);

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/bookmarks');
        $res->assertOk();
        $this->assertGreaterThanOrEqual(1, $res->json('total'));
    }
}

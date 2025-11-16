<?php

namespace Tests\Feature\Api;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowSuggestionsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_suggestions_excluding_self_and_following(): void
    {
        $me = User::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        // Give u1 and u2 some published posts to rank them
        Post::factory()->count(2)->create(['user_id' => $u1->id, 'status' => 'published', 'published_at' => now()->subDay()]);
        Post::factory()->count(1)->create(['user_id' => $u2->id, 'status' => 'published', 'published_at' => now()->subDay()]);

        // me already follows u1
        \App\Models\Follow::create(['follower_id' => $me->id, 'followed_id' => $u1->id]);

        $res = $this->actingAs($me, 'sanctum')->getJson('/api/v1/users/suggestions');
        $res->assertOk();
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertNotContains($me->id, $ids);
        $this->assertNotContains($u1->id, $ids);
        $this->assertContains($u2->id, $ids);
        $this->assertContains($u3->id, $ids);
    }
}

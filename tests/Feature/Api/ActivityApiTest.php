<?php

namespace Tests\Feature\Api;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_activity_feed_includes_follow_events(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice, 'sanctum')->postJson('/api/v1/users/'.$bob->id.'/follow')->assertOk();

        $feed = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/activity/me');
        $feed->assertOk();
        $this->assertGreaterThanOrEqual(1, $feed->json('total'));
    }

    public function test_following_feed_includes_followed_users_activity(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();

        // Alice follows Bob
        Follow::create(['follower_id' => $alice->id, 'followed_id' => $bob->id]);

        // Bob follows Carol (creates an activity)
        $this->actingAs($bob, 'sanctum')->postJson('/api/v1/users/'.$carol->id.'/follow')->assertOk();

        // Alice's following feed should include Bob's follow
        $feed = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/activity/following');
        $feed->assertOk();
        $this->assertGreaterThanOrEqual(1, $feed->json('total'));
    }
}

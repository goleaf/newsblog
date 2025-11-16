<?php

namespace Tests\Feature\Api;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $res = $this->actingAs($alice, 'sanctum')->postJson('/api/v1/users/'.$bob->id.'/follow');
        $res->assertOk();
        $this->assertDatabaseHas('follows', [
            'follower_id' => $alice->id,
            'followed_id' => $bob->id,
        ]);

        $del = $this->actingAs($alice, 'sanctum')->deleteJson('/api/v1/users/'.$bob->id.'/follow');
        $del->assertNoContent();
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $alice->id,
            'followed_id' => $bob->id,
        ]);
    }

    public function test_lists_followers_and_following(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $carol = User::factory()->create();

        Follow::create(['follower_id' => $bob->id, 'followed_id' => $alice->id]);
        Follow::create(['follower_id' => $alice->id, 'followed_id' => $carol->id]);

        $followers = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/users/'.$alice->id.'/followers');
        $followers->assertOk();
        $this->assertGreaterThanOrEqual(1, $followers->json('total'));

        $following = $this->actingAs($alice, 'sanctum')->getJson('/api/v1/users/'.$alice->id.'/following');
        $following->assertOk();
        $this->assertGreaterThanOrEqual(1, $following->json('total'));
    }
}

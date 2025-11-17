<?php

namespace Tests\Feature;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_another_user(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $response = $this->actingAs($follower)
            ->post(route('users.follow', $followed));

        $response->assertRedirect();
        $this->assertTrue($follower->isFollowing($followed));
        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);
    }

    public function test_user_can_unfollow_another_user(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        $response = $this->actingAs($follower)
            ->delete(route('users.unfollow', $followed));

        $response->assertRedirect();
        $this->assertFalse($follower->isFollowing($followed));
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('users.follow', $user));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $user->id,
            'followed_id' => $user->id,
        ]);
    }

    public function test_user_cannot_follow_same_user_twice(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        $response = $this->actingAs($follower)
            ->post(route('users.follow', $followed));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, Follow::where('follower_id', $follower->id)
            ->where('followed_id', $followed->id)
            ->count());
    }

    public function test_followers_page_displays_followers(): void
    {
        $user = User::factory()->create();
        $followers = User::factory()->count(3)->create();

        foreach ($followers as $follower) {
            Follow::create([
                'follower_id' => $follower->id,
                'followed_id' => $user->id,
            ]);
        }

        $response = $this->get(route('users.followers', $user));

        $response->assertOk();
        $response->assertViewIs('follows.followers');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('followers');
    }

    public function test_following_page_displays_following(): void
    {
        $user = User::factory()->create();
        $following = User::factory()->count(3)->create();

        foreach ($following as $followed) {
            Follow::create([
                'follower_id' => $user->id,
                'followed_id' => $followed->id,
            ]);
        }

        $response = $this->get(route('users.following', $user));

        $response->assertOk();
        $response->assertViewIs('follows.following');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('following');
    }

    public function test_follow_status_endpoint_returns_correct_status(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $response = $this->actingAs($follower)
            ->get(route('users.follow-status', $followed));

        $response->assertOk();
        $response->assertJson(['following' => false]);

        Follow::create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        $response = $this->actingAs($follower)
            ->get(route('users.follow-status', $followed));

        $response->assertOk();
        $response->assertJson(['following' => true]);
    }

    public function test_guest_cannot_follow_user(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('users.follow', $user));

        $response->assertRedirect(route('login'));
    }

    public function test_follow_api_returns_json_response(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $response = $this->actingAs($follower)
            ->postJson(route('users.follow', $followed));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Successfully followed user.',
            'following' => true,
        ]);
    }

    public function test_unfollow_api_returns_json_response(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        $response = $this->actingAs($follower)
            ->deleteJson(route('users.unfollow', $followed));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Successfully unfollowed user.',
            'following' => false,
        ]);
    }
}

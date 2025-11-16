<?php

namespace Tests\Unit\Models;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFollowingTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_following_and_relationships(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->assertFalse($alice->isFollowing($bob));

        Follow::create([
            'follower_id' => $alice->id,
            'followed_id' => $bob->id,
        ]);

        $this->assertTrue($alice->isFollowing($bob));

        $this->assertEquals(1, $alice->following()->count());
        $this->assertEquals(1, $bob->followers()->count());
    }
}

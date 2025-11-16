<?php

namespace Tests\Unit\Models;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserBookmarkHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_bookmarked_helper(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->assertFalse($user->hasBookmarked($post));

        Bookmark::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertTrue($user->hasBookmarked($post));
    }
}

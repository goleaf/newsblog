<?php

namespace Tests\Unit;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_flags_default_to_false(): void
    {
        $post = Post::factory()->create();

        $this->assertFalse($post->is_featured);
        $this->assertFalse($post->is_trending);
        $this->assertFalse($post->is_breaking);
        $this->assertFalse($post->is_sponsored);
        $this->assertFalse($post->is_editors_pick);
    }

    public function test_post_status_is_cast_to_enum(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->assertInstanceOf(PostStatus::class, $post->status);
        $this->assertTrue($post->status === PostStatus::Published);
    }
}

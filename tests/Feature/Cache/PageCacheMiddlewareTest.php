<?php

namespace Tests\Feature\Cache;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PageCacheMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_homepage_is_cached_for_10_minutes(): void
    {
        $first = $this->get(route('home'));
        $first->assertOk();

        $cached = $this->get(route('home'));
        $cached->assertOk();
        $this->assertNotEmpty(Cache::get('pagecache.home./.'.md5(json_encode([]))));
    }

    public function test_post_and_category_routes_set_page_cache_keys(): void
    {
        // Create basic fixtures
        $category = \App\Models\Category::factory()->create(['slug' => 'tech']);
        $post = \App\Models\Post::factory()->create(['slug' => 'hello-world', 'status' => 'published', 'published_at' => now(), 'category_id' => $category->id]);

        $this->get(route('category.show', ['slug' => $category->slug]))->assertOk();
        $this->get(route('post.show', ['slug' => $post->slug]))->assertOk();

        $this->assertTrue(Cache::has('pagecache.category.show.category/tech.'.md5(json_encode([]))));
        $this->assertTrue(Cache::has('pagecache.post.show.post/hello-world.'.md5(json_encode([]))));
    }
}




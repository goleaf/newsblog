<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheService::class);
        Cache::flush();
    }

    public function test_cache_service_remembers_values(): void
    {
        $key = 'test.key';
        $value = 'test value';

        $result = $this->cacheService->remember($key, CacheService::TTL_SHORT, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);
        $this->assertTrue(Cache::has($key));
    }

    public function test_homepage_cache_works(): void
    {
        $data = ['featured' => 'posts'];

        $result = $this->cacheService->cacheHomepage(function () use ($data) {
            return $data;
        });

        $this->assertEquals($data, $result);
        $this->assertTrue(Cache::has('home.page'));
    }

    public function test_category_page_cache_works(): void
    {
        $category = Category::factory()->create();
        $filters = ['sort' => 'latest'];

        $data = ['posts' => []];

        $result = $this->cacheService->cacheCategoryPage($category->id, $filters, function () use ($data) {
            return $data;
        });

        $this->assertEquals($data, $result);
    }

    public function test_tag_page_cache_works(): void
    {
        $tag = Tag::factory()->create();
        $filters = ['sort' => 'popular'];

        $data = ['posts' => []];

        $result = $this->cacheService->cacheTagPage($tag->id, $filters, function () use ($data) {
            return $data;
        });

        $this->assertEquals($data, $result);
    }

    public function test_query_cache_works(): void
    {
        $queryKey = 'expensive.query';
        $data = ['result' => 'data'];

        $result = $this->cacheService->cacheQuery($queryKey, CacheService::TTL_MEDIUM, function () use ($data) {
            return $data;
        });

        $this->assertEquals($data, $result);
        $this->assertTrue(Cache::has('query.'.$queryKey));
    }

    public function test_model_cache_works(): void
    {
        $post = Post::factory()->create();

        $result = $this->cacheService->cacheModel('post', $post->id, CacheService::TTL_LONG, function () use ($post) {
            return $post;
        });

        $this->assertEquals($post->id, $result->id);
        $this->assertTrue(Cache::has('model.post.'.$post->id));
    }

    public function test_invalidate_homepage_clears_cache(): void
    {
        Cache::put('home.page', 'data', 60);
        Cache::put('home.featured', 'data', 60);
        Cache::put('home.trending', 'data', 60);
        Cache::put('home.recent', 'data', 60);
        Cache::put('home.categories', 'data', 60);

        $this->cacheService->invalidateHomepage();

        $this->assertFalse(Cache::has('home.page'));
        $this->assertFalse(Cache::has('home.featured'));
        $this->assertFalse(Cache::has('home.trending'));
        $this->assertFalse(Cache::has('home.recent'));
        $this->assertFalse(Cache::has('home.categories'));
    }

    public function test_invalidate_category_clears_cache(): void
    {
        $category = Category::factory()->create();
        Cache::put('category.'.$category->id, 'data', 60);

        $this->cacheService->invalidateCategory($category->id);

        $this->assertFalse(Cache::has('category.'.$category->id));
    }

    public function test_invalidate_tag_clears_cache(): void
    {
        $tag = Tag::factory()->create();
        Cache::put('tag.'.$tag->id, 'data', 60);

        $this->cacheService->invalidateTag($tag->id);

        $this->assertFalse(Cache::has('tag.'.$tag->id));
    }

    public function test_invalidate_post_clears_cache(): void
    {
        $post = Post::factory()->create();
        Cache::put('post.'.$post->id, 'data', 60);
        Cache::put('model.post.'.$post->id, 'data', 60);

        $this->cacheService->invalidatePost($post->id);

        $this->assertFalse(Cache::has('post.'.$post->id));
        $this->assertFalse(Cache::has('model.post.'.$post->id));
    }

    public function test_creating_post_invalidates_homepage_cache(): void
    {
        Cache::put('home.page', 'data', 60);
        Cache::put('home.featured', 'data', 60);

        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertFalse(Cache::has('home.page'));
        $this->assertFalse(Cache::has('home.featured'));
    }

    public function test_updating_post_invalidates_caches(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Cache::put('home.page', 'data', 60);
        Cache::put('post.'.$post->slug, 'data', 60);

        $post->update(['title' => 'Updated Title']);

        $this->assertFalse(Cache::has('home.page'));
        $this->assertFalse(Cache::has('post.'.$post->slug));
    }

    public function test_deleting_post_invalidates_caches(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Cache::put('home.page', 'data', 60);
        Cache::put('post.'.$post->id, 'data', 60);

        $post->delete();

        $this->assertFalse(Cache::has('home.page'));
        $this->assertFalse(Cache::has('post.'.$post->id));
    }

    public function test_updating_category_invalidates_caches(): void
    {
        $category = Category::factory()->create();

        Cache::put('home.page', 'data', 60);
        Cache::put('category.'.$category->id, 'data', 60);

        $category->update(['name' => 'Updated Category']);

        $this->assertFalse(Cache::has('home.page'));
        $this->assertFalse(Cache::has('category.'.$category->id));
    }

    public function test_clear_all_flushes_cache(): void
    {
        Cache::put('test.key', 'value', 60);
        Cache::put('another.key', 'value', 60);

        $this->cacheService->clearAll();

        $this->assertFalse(Cache::has('test.key'));
        $this->assertFalse(Cache::has('another.key'));
    }
}

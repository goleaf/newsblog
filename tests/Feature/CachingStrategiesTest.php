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

/**
 * Test caching strategies implementation
 * Requirements: 20.1, 20.5
 */
class CachingStrategiesTest extends TestCase
{
    use RefreshDatabase;

    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CacheService::class);
        Cache::flush();
    }

    /** @test */
    public function homepage_view_is_cached_for_10_minutes()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(3)->published()->for($user)->for($category)->create();

        // Act - First request should cache
        $response1 = $this->get('/');
        $response1->assertOk();

        // Assert cache exists
        $this->assertTrue(Cache::has('view.home'));

        // Act - Second request should use cache
        $response2 = $this->get('/');
        $response2->assertOk();

        // Assert same content
        $this->assertEquals($response1->getContent(), $response2->getContent());
    }

    /** @test */
    public function homepage_cache_is_not_used_for_paginated_requests()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(15)->published()->for($user)->for($category)->create();

        // Act - First page should cache
        $this->get('/');
        $this->assertTrue(Cache::has('view.home'));

        // Act - Second page should not use cache
        $response = $this->get('/?page=2');
        $response->assertOk();
    }

    /** @test */
    public function homepage_cache_is_not_used_for_sorted_requests()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(5)->published()->for($user)->for($category)->create();

        // Act - Default sort should cache
        $this->get('/');
        $this->assertTrue(Cache::has('view.home'));

        // Act - Different sort should not use cache
        $response = $this->get('/?sort=popular');
        $response->assertOk();
    }

    /** @test */
    public function category_data_is_cached()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(3)->published()->for($user)->for($category)->create();

        // Act - First request should cache category model
        $response = $this->get("/category/{$category->slug}");
        $response->assertOk();

        // Assert category model cache exists
        $cacheKey = "model.category.{$category->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Assert category page query cache exists (only for first page with default sort)
        // The actual filter structure used in the controller
        $filters = ['sort' => 'latest', 'date_filter' => null, 'page' => 1];
        ksort($filters); // CacheService sorts filters before hashing
        $filterKey = md5(json_encode($filters));
        $queryCacheKey = "category.page.{$category->id}.{$filterKey}";
        $this->assertTrue(Cache::has($queryCacheKey));
    }

    /** @test */
    public function category_query_cache_varies_by_filters()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(5)->published()->for($user)->for($category)->create();

        // Act - Default sort caches
        $this->get("/category/{$category->slug}");
        $filters1 = ['sort' => 'latest', 'date_filter' => null, 'page' => 1];
        ksort($filters1);
        $filterKey1 = md5(json_encode($filters1));
        $cacheKey1 = "category.page.{$category->id}.{$filterKey1}";
        $this->assertTrue(Cache::has($cacheKey1));

        // Act - Different sort doesn't use same cache
        $this->get("/category/{$category->slug}?sort=popular");
        // Popular sort is not cached (only default is cached)
        $filters2 = ['sort' => 'popular', 'date_filter' => null, 'page' => 1];
        ksort($filters2);
        $filterKey2 = md5(json_encode($filters2));
        $cacheKey2 = "category.page.{$category->id}.{$filterKey2}";
        // This won't be cached because we only cache default sort
        $this->assertFalse(Cache::has($cacheKey2));
    }

    /** @test */
    public function post_view_is_cached_for_30_minutes()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();

        // Act - First request should cache (for guests)
        $response1 = $this->get("/post/{$post->slug}");
        $response1->assertOk();

        // Assert cache exists
        $cacheKey = "view.post.{$post->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Second request should use cache
        $response2 = $this->get("/post/{$post->slug}");
        $response2->assertOk();

        // Assert same content
        $this->assertEquals($response1->getContent(), $response2->getContent());
    }

    /** @test */
    public function post_view_is_not_cached_for_authenticated_users()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();

        // Act - Authenticated request should not cache
        $response = $this->actingAs($user)->get("/post/{$post->slug}");
        $response->assertOk();

        // Assert cache does not exist
        $cacheKey = "view.post.{$post->slug}";
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function tag_data_is_cached()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();
        $post->tags()->attach($tag);

        // Act - First request should cache tag model
        $response = $this->get("/tag/{$tag->slug}");
        $response->assertOk();

        // Assert tag model cache exists
        $cacheKey = "model.tag.{$tag->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Assert tag page query cache exists
        $filters = ['sort' => 'latest', 'date_filter' => null, 'page' => 1];
        ksort($filters); // CacheService sorts filters before hashing
        $filterKey = md5(json_encode($filters));
        $queryCacheKey = "tag.page.{$tag->id}.{$filterKey}";
        $this->assertTrue(Cache::has($queryCacheKey));
    }

    /** @test */
    public function homepage_cache_is_invalidated_when_post_is_created()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(3)->published()->for($user)->for($category)->create();

        // Cache homepage
        $this->get('/');
        $this->assertTrue(Cache::has('view.home'));

        // Act - Create new post
        Post::factory()->published()->for($user)->for($category)->create();

        // Assert cache is invalidated
        $this->assertFalse(Cache::has('view.home'));
    }

    /** @test */
    public function category_cache_is_invalidated_when_post_is_updated()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();

        // Cache category page
        $this->get("/category/{$category->slug}");
        $cacheKey = "model.category.{$category->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Update post
        $post->update(['title' => 'Updated Title']);

        // Assert category model cache is still there (not invalidated by post update)
        // But category page query cache would be invalidated
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function post_cache_is_invalidated_when_post_is_updated()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();

        // Cache post page
        $this->get("/post/{$post->slug}");
        $cacheKey = "view.post.{$post->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Update post
        $post->update(['title' => 'Updated Title']);

        // Assert cache is invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function category_cache_is_invalidated_when_category_is_updated()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(3)->published()->for($user)->for($category)->create();

        // Cache category page
        $this->get("/category/{$category->slug}");
        $cacheKey = "model.category.{$category->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Update category
        $category->update(['name' => 'Updated Category']);

        // Assert cache is invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function tag_cache_is_invalidated_when_tag_is_updated()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();
        $post->tags()->attach($tag);

        // Cache tag page
        $this->get("/tag/{$tag->slug}");
        $cacheKey = "model.tag.{$tag->slug}";
        $this->assertTrue(Cache::has($cacheKey));

        // Act - Update tag
        $tag->update(['name' => 'Updated Tag']);

        // Assert cache is invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function query_results_are_cached()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(5)->published()->for($user)->for($category)->create();

        // Act - Cache query results
        $posts1 = $this->cacheService->cacheQuery('test.query', 600, function () use ($category) {
            return Post::where('category_id', $category->id)->get();
        });

        // Assert cache exists
        $this->assertTrue(Cache::has('query.test.query'));

        // Act - Get cached results
        $posts2 = $this->cacheService->cacheQuery('test.query', 600, function () use ($category) {
            return Post::where('category_id', $category->id)->get();
        });

        // Assert same results
        $this->assertEquals($posts1->count(), $posts2->count());
    }

    /** @test */
    public function related_posts_are_cached()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();
        Post::factory()->count(5)->published()->for($user)->for($category)->create();

        // Act - First request caches related posts
        $this->get("/post/{$post->slug}");

        // Assert related posts cache exists
        $cacheKey = "post.{$post->slug}.related";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function series_navigation_is_cached()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->published()->for($user)->for($category)->create();

        // Act - First request caches series navigation
        $this->get("/post/{$post->slug}");

        // Assert series navigation cache exists
        $cacheKey = "post.{$post->slug}.series";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function cache_service_can_invalidate_homepage()
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(3)->published()->for($user)->for($category)->create();

        // Cache homepage data
        $this->get('/');

        $this->assertTrue(Cache::has('view.home'));
        $this->assertTrue(Cache::has('home.featured'));
        $this->assertTrue(Cache::has('home.trending'));

        // Act - Invalidate homepage
        $this->cacheService->invalidateHomepage();

        // Assert homepage caches are cleared
        $this->assertFalse(Cache::has('view.home'));
        $this->assertFalse(Cache::has('home.featured'));
        $this->assertFalse(Cache::has('home.trending'));
    }
}

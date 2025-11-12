<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test cache hit scenario for post
     */
    public function test_post_cache_hit_scenario(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'slug' => 'test-post-cache',
        ]);

        $cacheKey = "post.{$post->slug}";

        // First request - cache miss, should populate cache
        $this->assertFalse(Cache::has($cacheKey), 'Cache should not exist before first request');

        $response1 = $this->get("/post/{$post->slug}");
        $response1->assertStatus(200);

        $this->assertTrue(Cache::has($cacheKey), 'Cache should exist after first request');

        // Second request - cache hit
        $response2 = $this->get("/post/{$post->slug}");
        $response2->assertStatus(200);

        // Verify cache still exists
        $this->assertTrue(Cache::has($cacheKey), 'Cache should still exist after second request');
    }

    /**
     * Test cache miss scenario for post
     */
    public function test_post_cache_miss_scenario(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'slug' => 'test-post-miss',
        ]);

        $cacheKey = "post.{$post->slug}";

        // Verify cache does not exist
        $this->assertFalse(Cache::has($cacheKey), 'Cache should not exist initially');

        // Make request - should populate cache
        $response = $this->get("/post/{$post->slug}");
        $response->assertStatus(200);

        // Verify cache now exists
        $this->assertTrue(Cache::has($cacheKey), 'Cache should exist after request');
    }

    /**
     * Test cache invalidation on post update
     */
    public function test_cache_invalidation_on_post_update(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'slug' => 'test-post-invalidation',
            'title' => 'Original Title',
        ]);

        $cacheKey = "post.{$post->slug}";
        $relatedCacheKey = "post.{$post->id}.related";

        // Populate cache
        $this->get("/post/{$post->slug}");
        $this->assertTrue(Cache::has($cacheKey), 'Post cache should exist');
        $this->assertTrue(Cache::has($relatedCacheKey), 'Related posts cache should exist');

        // Populate search index cache first
        $searchIndexService = app(\App\Services\SearchIndexService::class);
        $searchIndexService->getIndex('posts');
        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        // Get initial cache content
        $indexBefore = Cache::get($searchIndexCacheKey);
        $indexedPostBefore = collect($indexBefore)->firstWhere('id', $post->id);
        $this->assertEquals('Original Title', $indexedPostBefore['title'], 'Initial title should be correct');

        // Update post title (search-relevant field)
        $post->update(['title' => 'Updated Title']);

        // Note: The Post model boot method invalidates search caches, then PostObserver repopulates it
        // Verify that cache content is updated correctly
        $indexAfter = $searchIndexService->getIndex('posts');
        $indexedPost = collect($indexAfter)->firstWhere('id', $post->id);
        $this->assertEquals('Updated Title', $indexedPost['title'], 'Index should contain updated title');
        $this->assertNotEquals($indexedPostBefore['title'], $indexedPost['title'], 'Title should have changed');
    }

    /**
     * Test cache invalidation on post deletion
     */
    public function test_cache_invalidation_on_post_deletion(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'slug' => 'test-post-deletion',
        ]);

        $cacheKey = "post.{$post->slug}";
        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        // Populate caches
        $this->get("/post/{$post->slug}");
        $this->assertTrue(Cache::has($cacheKey), 'Post cache should exist');

        // Populate search index cache
        $searchIndexService = app(\App\Services\SearchIndexService::class);
        $searchIndexService->getIndex('posts');
        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        // Verify post is in index before deletion
        $indexBefore = $searchIndexService->getIndex('posts');
        $indexedPostBefore = collect($indexBefore)->firstWhere('id', $post->id);
        $this->assertNotNull($indexedPostBefore, 'Post should be in index before deletion');

        // Delete post
        $post->delete();

        // Note: The Post model boot method invalidates search caches, then PostObserver removes the post
        // Verify post is removed from index
        $indexAfter = $searchIndexService->getIndex('posts');
        $indexedPostAfter = collect($indexAfter)->firstWhere('id', $post->id);
        $this->assertNull($indexedPostAfter, 'Deleted post should be removed from index');
        $this->assertCount(count($indexBefore) - 1, $indexAfter, 'Index should have one less post');
    }

    /**
     * Test cache invalidation on post creation
     */
    public function test_cache_invalidation_on_post_creation(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create initial post and populate cache
        $existingPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Populate search index cache
        $searchIndexService = app(\App\Services\SearchIndexService::class);
        $searchIndexService->getIndex('posts');
        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        $indexBefore = $searchIndexService->getIndex('posts');
        $initialCount = count($indexBefore);

        // Create new published post
        $newPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // Note: The Post model boot method invalidates search caches, then PostObserver adds the new post
        // Verify new post appears in index
        $indexAfter = $searchIndexService->getIndex('posts');
        $indexedNewPost = collect($indexAfter)->firstWhere('id', $newPost->id);
        $this->assertNotNull($indexedNewPost, 'New post should be in index');
        $this->assertCount($initialCount + 1, $indexAfter, 'Index should contain one more post');
    }

    /**
     * Test home page cache hit scenario
     */
    public function test_home_page_cache_hit_scenario(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create posts to populate home page
        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'is_featured' => true,
        ]);

        Post::factory()->count(6)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'is_trending' => true,
        ]);

        $featuredCacheKey = 'home.featured';
        $trendingCacheKey = 'home.trending';
        $recentCacheKey = 'home.recent';
        $categoriesCacheKey = 'home.categories';

        // First request - cache miss
        $this->assertFalse(Cache::has($featuredCacheKey), 'Featured cache should not exist');
        $this->assertFalse(Cache::has($trendingCacheKey), 'Trending cache should not exist');
        $this->assertFalse(Cache::has($recentCacheKey), 'Recent cache should not exist');
        $this->assertFalse(Cache::has($categoriesCacheKey), 'Categories cache should not exist');

        $response1 = $this->get('/');
        $response1->assertStatus(200);

        // Verify caches are populated
        $this->assertTrue(Cache::has($featuredCacheKey), 'Featured cache should exist');
        $this->assertTrue(Cache::has($trendingCacheKey), 'Trending cache should exist');
        $this->assertTrue(Cache::has($recentCacheKey), 'Recent cache should exist');
        $this->assertTrue(Cache::has($categoriesCacheKey), 'Categories cache should exist');

        // Second request - cache hit
        $response2 = $this->get('/');
        $response2->assertStatus(200);

        // Verify caches still exist
        $this->assertTrue(Cache::has($featuredCacheKey), 'Featured cache should still exist');
        $this->assertTrue(Cache::has($trendingCacheKey), 'Trending cache should still exist');
    }

    /**
     * Test related posts cache
     */
    public function test_related_posts_cache(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'slug' => 'test-related-cache',
        ]);

        // Create related posts in same category
        Post::factory()->count(4)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $relatedCacheKey = "post.{$post->id}.related";

        // First request - cache miss
        $this->assertFalse(Cache::has($relatedCacheKey), 'Related posts cache should not exist');

        $response1 = $this->get("/post/{$post->slug}");
        $response1->assertStatus(200);

        // Verify cache is populated
        $this->assertTrue(Cache::has($relatedCacheKey), 'Related posts cache should exist');

        // Second request - cache hit
        $response2 = $this->get("/post/{$post->slug}");
        $response2->assertStatus(200);

        // Verify cache still exists
        $this->assertTrue(Cache::has($relatedCacheKey), 'Related posts cache should still exist');
    }

    /**
     * Test category cache hit/miss scenarios
     */
    public function test_category_cache_hit_miss_scenarios(): void
    {
        $category = Category::factory()->create([
            'slug' => 'test-category',
            'status' => 'active',
        ]);

        $cacheKey = "category.{$category->slug}";

        // First request - cache miss
        $this->assertFalse(Cache::has($cacheKey), 'Category cache should not exist');

        $response1 = $this->get("/category/{$category->slug}");
        $response1->assertStatus(200);

        // Verify cache is populated
        $this->assertTrue(Cache::has($cacheKey), 'Category cache should exist');

        // Second request - cache hit
        $response2 = $this->get("/category/{$category->slug}");
        $response2->assertStatus(200);

        // Verify cache still exists
        $this->assertTrue(Cache::has($cacheKey), 'Category cache should still exist');
    }

    /**
     * Test that cache is properly invalidated when post content changes
     */
    public function test_cache_invalidation_on_content_changes(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'title' => 'Original Title',
            'excerpt' => 'Original excerpt',
            'content' => 'Original content',
        ]);

        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        // Populate search index cache
        $searchIndexService = app(\App\Services\SearchIndexService::class);
        $searchIndexService->getIndex('posts');
        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        // Get initial cache content
        $indexBefore = $searchIndexService->getIndex('posts');
        $indexedPostBefore = collect($indexBefore)->firstWhere('id', $post->id);
        $this->assertEquals('Original Title', $indexedPostBefore['title'], 'Initial title should be correct');

        // Update title (search-relevant field)
        $post->update(['title' => 'Updated Title']);

        // Note: The Post model boot method invalidates search caches, then PostObserver repopulates it
        // Verify updated data appears in index
        $indexAfterTitle = $searchIndexService->getIndex('posts');
        $indexedPost = collect($indexAfterTitle)->firstWhere('id', $post->id);
        $this->assertEquals('Updated Title', $indexedPost['title'], 'Index should contain updated title');

        // Update excerpt (search-relevant field)
        $post->update(['excerpt' => 'Updated excerpt']);

        // Verify updated excerpt appears in index
        $indexAfterExcerpt = $searchIndexService->getIndex('posts');
        $indexedPost = collect($indexAfterExcerpt)->firstWhere('id', $post->id);
        $this->assertEquals('Updated excerpt', $indexedPost['excerpt'], 'Index should contain updated excerpt');

        // Update content (search-relevant field)
        $post->update(['content' => 'Updated content']);

        // Verify updated content appears in index
        $indexAfterContent = $searchIndexService->getIndex('posts');
        $indexedPost = collect($indexAfterContent)->firstWhere('id', $post->id);
        $this->assertStringContainsString('Updated content', $indexedPost['content'], 'Index should contain updated content');
    }

    /**
     * Test that non-search-relevant field changes do not invalidate search cache
     */
    public function test_non_search_relevant_changes_do_not_invalidate_search_cache(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $searchIndexCacheKey = 'fuzzy_search:index:posts';

        // Populate search index cache
        app(\App\Services\SearchIndexService::class)->getIndex('posts');
        $this->assertTrue(Cache::has($searchIndexCacheKey), 'Search index cache should exist');

        // Update non-search-relevant field (view_count)
        $post->increment('view_count');

        // Note: The Post model boot method checks for search-relevant fields
        // Since view_count is not in the searchRelevantFields array,
        // the cache should NOT be invalidated
        // However, the model's updated event will still trigger, but won't invalidate cache
        // Let's verify the cache still exists
        $this->assertTrue(Cache::has($searchIndexCacheKey), 'Search index cache should still exist after non-search-relevant change');
    }
}

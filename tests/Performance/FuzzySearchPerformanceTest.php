<?php

namespace Tests\Performance;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\FuzzySearchService;
use App\Services\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuzzySearchPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private FuzzySearchService $searchService;

    private SearchIndexService $indexService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->searchService = app(FuzzySearchService::class);
        $this->indexService = app(SearchIndexService::class);
    }

    /**
     * Test search performance with small dataset (100 posts)
     */
    public function test_search_performance_with_small_dataset(): void
    {
        // Arrange: Create 100 posts
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(100)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        // Act: Measure search time
        $startTime = microtime(true);
        $results = $this->searchService->searchPosts('laravel', ['limit' => 15]);
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        // Assert: Should complete within 500ms
        $this->assertLessThan(500, $executionTime, "Search took {$executionTime}ms, expected < 500ms");
        $this->assertNotNull($results);

        echo "\n  Small dataset (100 posts): {$executionTime}ms\n";
    }

    /**
     * Test search performance with medium dataset (1000 posts)
     */
    public function test_search_performance_with_medium_dataset(): void
    {
        // Arrange: Create 1000 posts
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(1000)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        // Act: Measure search time
        $startTime = microtime(true);
        $results = $this->searchService->searchPosts('laravel', ['limit' => 15]);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert: Should complete within 1000ms
        $this->assertLessThan(1000, $executionTime, "Search took {$executionTime}ms, expected < 1000ms");
        $this->assertNotNull($results);

        echo "\n  Medium dataset (1000 posts): {$executionTime}ms\n";
    }

    /**
     * Test search performance with cache enabled
     */
    public function test_search_performance_with_cache(): void
    {
        // Arrange
        config(['fuzzy-search.cache.enabled' => true]);
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(500)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        // Act: First search (cache miss)
        $startTime = microtime(true);
        $this->searchService->searchPosts('laravel', ['limit' => 15]);
        $firstSearchTime = (microtime(true) - $startTime) * 1000;

        // Act: Second search (cache hit)
        $startTime = microtime(true);
        $this->searchService->searchPosts('laravel', ['limit' => 15]);
        $secondSearchTime = (microtime(true) - $startTime) * 1000;

        // Assert: Cached search should be significantly faster
        $this->assertLessThan($firstSearchTime, $secondSearchTime, 'Cached search should be faster');
        $this->assertLessThan(100, $secondSearchTime, "Cached search took {$secondSearchTime}ms, expected < 100ms");

        echo "\n  First search (cache miss): {$firstSearchTime}ms\n";
        echo "  Second search (cache hit): {$secondSearchTime}ms\n";
        echo '  Speed improvement: '.round(($firstSearchTime - $secondSearchTime) / $firstSearchTime * 100, 2)."%\n";
    }

    /**
     * Test index build performance
     */
    public function test_index_build_performance(): void
    {
        // Arrange: Create 1000 posts
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(1000)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        // Act: Measure index build time
        $startTime = microtime(true);
        $count = $this->indexService->rebuildIndex('posts');
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert: Should complete within 5000ms for 1000 posts
        $this->assertLessThan(5000, $executionTime, "Index build took {$executionTime}ms, expected < 5000ms");
        $this->assertEquals(1000, $count);

        echo "\n  Index build (1000 posts): {$executionTime}ms\n";
        echo '  Average per post: '.round($executionTime / 1000, 2)."ms\n";
    }

    /**
     * Test concurrent search requests
     */
    public function test_concurrent_search_performance(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(500)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        $queries = ['laravel', 'php', 'javascript', 'vue', 'react'];
        $times = [];

        // Act: Perform multiple searches
        foreach ($queries as $query) {
            $startTime = microtime(true);
            $this->searchService->searchPosts($query, ['limit' => 15]);
            $times[] = (microtime(true) - $startTime) * 1000;
        }

        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);

        // Assert: Average should be reasonable
        $this->assertLessThan(1000, $avgTime, "Average search time {$avgTime}ms, expected < 1000ms");
        $this->assertLessThan(1500, $maxTime, "Max search time {$maxTime}ms, expected < 1500ms");

        echo "\n  Concurrent searches:\n";
        echo "    Average: {$avgTime}ms\n";
        echo "    Max: {$maxTime}ms\n";
        echo '    Min: '.min($times)."ms\n";
    }

    /**
     * Test fuzzy matching performance vs exact matching
     */
    public function test_fuzzy_vs_exact_matching_performance(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(500)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        // Act: Exact match
        $startTime = microtime(true);
        $this->searchService->searchPosts('laravel', ['exact' => true, 'limit' => 15]);
        $exactTime = (microtime(true) - $startTime) * 1000;

        // Act: Fuzzy match
        $startTime = microtime(true);
        $this->searchService->searchPosts('laravle', ['limit' => 15]);
        $fuzzyTime = (microtime(true) - $startTime) * 1000;

        // Assert: Both should be reasonably fast
        $this->assertLessThan(1000, $exactTime, "Exact search took {$exactTime}ms");
        $this->assertLessThan(1000, $fuzzyTime, "Fuzzy search took {$fuzzyTime}ms");

        echo "\n  Exact matching: {$exactTime}ms\n";
        echo "  Fuzzy matching: {$fuzzyTime}ms\n";
    }

    /**
     * Test tag and category search performance
     */
    public function test_tag_and_category_search_performance(): void
    {
        // Arrange
        Tag::factory()->count(100)->create();
        Category::factory()->count(50)->create();

        $this->indexService->rebuildIndex('tags');
        $this->indexService->rebuildIndex('categories');

        // Act: Tag search
        $startTime = microtime(true);
        $this->searchService->searchTags('php', 10);
        $tagSearchTime = (microtime(true) - $startTime) * 1000;

        // Act: Category search
        $startTime = microtime(true);
        $this->searchService->searchCategories('tech', 10);
        $categorySearchTime = (microtime(true) - $startTime) * 1000;

        // Assert: Should be very fast for small datasets
        $this->assertLessThan(200, $tagSearchTime, "Tag search took {$tagSearchTime}ms, expected < 200ms");
        $this->assertLessThan(200, $categorySearchTime, "Category search took {$categorySearchTime}ms, expected < 200ms");

        echo "\n  Tag search: {$tagSearchTime}ms\n";
        echo "  Category search: {$categorySearchTime}ms\n";
    }

    /**
     * Test suggestion performance
     */
    public function test_suggestion_performance(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(500)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        // Act: Get suggestions
        $startTime = microtime(true);
        $suggestions = $this->searchService->getSuggestions('lar', 5);
        $executionTime = (microtime(true) - $startTime) * 1000;

        // Assert: Should be very fast
        $this->assertLessThan(200, $executionTime, "Suggestions took {$executionTime}ms, expected < 200ms");
        $this->assertIsArray($suggestions);

        echo "\n  Suggestions: {$executionTime}ms\n";
    }

    /**
     * Test memory usage during search
     */
    public function test_memory_usage_during_search(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Post::factory()->count(1000)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $this->indexService->rebuildIndex('posts');

        // Act: Measure memory
        $memoryBefore = memory_get_usage(true);
        $this->searchService->searchPosts('laravel', ['limit' => 15]);
        $memoryAfter = memory_get_usage(true);

        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        // Assert: Should not use excessive memory
        $this->assertLessThan(50, $memoryUsed, "Search used {$memoryUsed}MB, expected < 50MB");

        echo "\n  Memory used: ".round($memoryUsed, 2)."MB\n";
    }
}

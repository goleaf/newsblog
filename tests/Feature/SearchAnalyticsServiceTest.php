<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SearchClick;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\SearchAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SearchAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SearchAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = app(SearchAnalyticsService::class);
    }

    public function test_can_log_query(): void
    {
        $this->analyticsService->logQuery(
            query: 'Laravel Testing',
            resultCount: 5,
            executionTime: 0.123,
            metadata: [
                'search_type' => 'posts',
                'fuzzy_enabled' => true,
                'threshold' => 60,
                'filters' => ['category' => 'Technology'],
            ]
        );

        $this->assertDatabaseHas('search_logs', [
            'query' => 'Laravel Testing',
            'result_count' => 5,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
            'threshold' => 60,
        ]);

        $log = SearchLog::latest()->first();
        $this->assertEquals(['category' => 'Technology'], $log->filters);
        $this->assertNotNull($log->ip_address);
    }

    public function test_log_query_associates_with_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->analyticsService->logQuery(
            query: 'Test Query',
            resultCount: 3,
            executionTime: 0.05
        );

        $log = SearchLog::latest()->first();
        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_log_query_handles_errors_gracefully(): void
    {
        // This should not throw an exception even with invalid data
        $this->analyticsService->logQuery(
            query: str_repeat('a', 1000), // Exceeds column length
            resultCount: -1,
            executionTime: -0.5
        );

        // Test continues without exception
        $this->assertTrue(true);
    }

    public function test_can_log_click(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $log = SearchLog::create([
            'query' => 'test',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $this->analyticsService->logClick(
            searchLogId: $log->id,
            postId: $post->id,
            position: 1
        );

        $this->assertDatabaseHas('search_clicks', [
            'search_log_id' => $log->id,
            'post_id' => $post->id,
            'position' => 1,
        ]);
    }

    public function test_get_top_queries_returns_most_frequent(): void
    {
        SearchLog::create(['query' => 'Laravel', 'result_count' => 5, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Laravel', 'result_count' => 3, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'PHP', 'result_count' => 2, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Testing', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $topQueries = $this->analyticsService->getTopQueries(limit: 2, period: 'month');

        $this->assertCount(2, $topQueries);
        $this->assertEquals('Laravel', $topQueries->first()->query);
        $this->assertEquals(2, $topQueries->first()->count);
    }

    public function test_get_top_queries_respects_period(): void
    {
        // Create old log
        $oldLog = new SearchLog([
            'query' => 'Old Query',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog->created_at = now()->subMonths(2);
        $oldLog->updated_at = now()->subMonths(2);
        $oldLog->save();

        // Create recent log
        SearchLog::create([
            'query' => 'Recent Query',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $topQueries = $this->analyticsService->getTopQueries(limit: 10, period: 'month');

        $this->assertCount(1, $topQueries);
        $this->assertEquals('Recent Query', $topQueries->first()->query);
    }

    public function test_get_no_result_queries(): void
    {
        SearchLog::create(['query' => 'No Results 1', 'result_count' => 0, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'No Results 1', 'result_count' => 0, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Has Results', 'result_count' => 5, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'No Results 2', 'result_count' => 0, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $noResultQueries = $this->analyticsService->getNoResultQueries(limit: 10);

        $this->assertCount(2, $noResultQueries);
        $this->assertEquals('No Results 1', $noResultQueries->first()->query);
        $this->assertEquals(2, $noResultQueries->first()->count);
    }

    public function test_get_performance_metrics(): void
    {
        SearchLog::create([
            'query' => 'Query 1',
            'result_count' => 5,
            'execution_time' => 0.1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        SearchLog::create([
            'query' => 'Query 2',
            'result_count' => 0,
            'execution_time' => 0.2,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        SearchLog::create([
            'query' => 'Query 3',
            'result_count' => 10,
            'execution_time' => 0.15,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $metrics = $this->analyticsService->getPerformanceMetrics(period: 'day');

        $this->assertEquals(0.15, $metrics['avg_execution_time']);
        $this->assertEquals(0.2, $metrics['max_execution_time']);
        $this->assertEquals(0.1, $metrics['min_execution_time']);
        $this->assertEquals(3, $metrics['total_searches']);
        $this->assertEquals(1, $metrics['no_result_searches']);
        $this->assertEquals(5.0, $metrics['avg_result_count']);
        $this->assertEquals(33.33, $metrics['no_result_percentage']);
    }

    public function test_archive_logs_removes_old_entries(): void
    {
        // Create old logs
        $oldLog1 = new SearchLog([
            'query' => 'Old 1',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog1->created_at = now()->subDays(100);
        $oldLog1->updated_at = now()->subDays(100);
        $oldLog1->save();

        $oldLog2 = new SearchLog([
            'query' => 'Old 2',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog2->created_at = now()->subDays(95);
        $oldLog2->updated_at = now()->subDays(95);
        $oldLog2->save();

        // Create recent log
        SearchLog::create([
            'query' => 'Recent',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $archivedCount = $this->analyticsService->archiveLogs(daysToKeep: 90);

        $this->assertEquals(2, $archivedCount);
        $this->assertEquals(1, SearchLog::count());
        $this->assertEquals('Recent', SearchLog::first()->query);
    }

    public function test_get_click_through_rate(): void
    {
        $log1 = SearchLog::create([
            'query' => 'Query 1',
            'result_count' => 5,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $log2 = SearchLog::create([
            'query' => 'Query 2',
            'result_count' => 3,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        SearchLog::create([
            'query' => 'Query 3',
            'result_count' => 2,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $post = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Add clicks to 2 out of 3 searches
        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $log2->id, 'post_id' => $post->id, 'position' => 1]);

        $ctr = $this->analyticsService->getClickThroughRate(period: 'day');

        $this->assertEquals(66.67, $ctr);
    }

    public function test_get_click_through_rate_returns_zero_for_no_searches(): void
    {
        $ctr = $this->analyticsService->getClickThroughRate(period: 'day');

        $this->assertEquals(0.0, $ctr);
    }

    public function test_get_most_clicked_posts(): void
    {
        $post1 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post2 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post3 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        $log1 = SearchLog::create(['query' => 'test1', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        $log2 = SearchLog::create(['query' => 'test2', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        $log3 = SearchLog::create(['query' => 'test3', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        // Post 1: 3 clicks
        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post1->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $log2->id, 'post_id' => $post1->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $log3->id, 'post_id' => $post1->id, 'position' => 1]);

        // Post 2: 2 clicks
        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post2->id, 'position' => 2]);
        SearchClick::create(['search_log_id' => $log2->id, 'post_id' => $post2->id, 'position' => 2]);

        // Post 3: 1 click
        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post3->id, 'position' => 3]);

        $mostClicked = $this->analyticsService->getMostClickedPosts(limit: 2, period: 'month');

        $this->assertCount(2, $mostClicked);
        $this->assertEquals($post1->id, $mostClicked->first()->post_id);
        $this->assertEquals(3, $mostClicked->first()->click_count);
        $this->assertEquals($post2->id, $mostClicked->get(1)->post_id);
        $this->assertEquals(2, $mostClicked->get(1)->click_count);
    }

    public function test_can_log_cache_hit(): void
    {
        Cache::flush();

        $this->analyticsService->logCacheHit('posts', 'test query');

        $this->assertEquals(1, Cache::get('search_cache_hits:posts', 0));
    }

    public function test_can_log_cache_miss(): void
    {
        Cache::flush();

        $this->analyticsService->logCacheMiss('posts', 'test query');

        $this->assertEquals(1, Cache::get('search_cache_misses:posts', 0));
    }

    public function test_can_log_slow_query(): void
    {
        Cache::flush();

        $this->analyticsService->logSlowQuery('slow query', 1500.0, [
            'search_type' => 'posts',
            'result_count' => 10,
        ]);

        $this->assertEquals(1, Cache::get('search_slow_queries_count', 0));
    }

    public function test_get_performance_metrics_includes_cache_stats(): void
    {
        Cache::flush();

        // Set up cache stats
        Cache::put('search_cache_hits:posts', 75, 3600);
        Cache::put('search_cache_misses:posts', 25, 3600);

        SearchLog::create([
            'query' => 'Query 1',
            'result_count' => 5,
            'execution_time' => 0.1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        SearchLog::create([
            'query' => 'Query 2',
            'result_count' => 0,
            'execution_time' => 1200.0, // Slow query
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $metrics = $this->analyticsService->getPerformanceMetrics(period: 'day');

        $this->assertArrayHasKey('cache_hits', $metrics);
        $this->assertArrayHasKey('cache_misses', $metrics);
        $this->assertArrayHasKey('cache_hit_rate', $metrics);
        $this->assertArrayHasKey('slow_queries_count', $metrics);

        $this->assertEquals(75, $metrics['cache_hits']);
        $this->assertEquals(25, $metrics['cache_misses']);
        $this->assertEquals(75.0, $metrics['cache_hit_rate']); // 75 / (75 + 25) * 100
        $this->assertEquals(1, $metrics['slow_queries_count']);
    }

    public function test_get_performance_metrics_handles_zero_cache_requests(): void
    {
        Cache::flush();

        $metrics = $this->analyticsService->getPerformanceMetrics(period: 'day');

        $this->assertEquals(0, $metrics['cache_hits']);
        $this->assertEquals(0, $metrics['cache_misses']);
        $this->assertEquals(0, $metrics['cache_hit_rate']);
    }
}

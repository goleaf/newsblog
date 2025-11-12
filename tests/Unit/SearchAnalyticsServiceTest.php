<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\SearchClick;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\SearchAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    // ========== Query Logging Tests ==========

    public function test_log_query_creates_search_log_entry(): void
    {
        $this->analyticsService->logQuery(
            query: 'Laravel Testing',
            resultCount: 5,
            executionTime: 0.123,
            metadata: [
                'search_type' => 'posts',
                'fuzzy_enabled' => true,
                'threshold' => 60,
            ]
        );

        $this->assertDatabaseHas('search_logs', [
            'query' => 'Laravel Testing',
            'result_count' => 5,
            'execution_time' => 0.123,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
            'threshold' => 60,
        ]);
    }

    public function test_log_query_stores_default_metadata(): void
    {
        $this->analyticsService->logQuery(
            query: 'Test Query',
            resultCount: 3,
            executionTime: 0.05
        );

        $log = SearchLog::latest()->first();

        $this->assertEquals('posts', $log->search_type);
        $this->assertTrue($log->fuzzy_enabled);
        $this->assertNull($log->threshold);
    }

    public function test_log_query_stores_filters_metadata(): void
    {
        $this->analyticsService->logQuery(
            query: 'Test',
            resultCount: 2,
            executionTime: 0.1,
            metadata: [
                'filters' => ['category' => 'Technology', 'author' => 'John'],
            ]
        );

        $log = SearchLog::latest()->first();
        $this->assertEquals(['category' => 'Technology', 'author' => 'John'], $log->filters);
    }

    public function test_log_query_stores_ip_address(): void
    {
        $this->analyticsService->logQuery(
            query: 'Test',
            resultCount: 1,
            executionTime: 0.05
        );

        $log = SearchLog::latest()->first();
        $this->assertNotNull($log->ip_address);
    }

    public function test_log_query_stores_user_agent(): void
    {
        $this->analyticsService->logQuery(
            query: 'Test',
            resultCount: 1,
            executionTime: 0.05
        );

        $log = SearchLog::latest()->first();
        $this->assertNotNull($log->user_agent);
    }

    public function test_log_query_associates_with_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->analyticsService->logQuery(
            query: 'Test',
            resultCount: 1,
            executionTime: 0.05
        );

        $log = SearchLog::latest()->first();
        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_log_query_handles_unauthenticated_user(): void
    {
        $this->analyticsService->logQuery(
            query: 'Test',
            resultCount: 1,
            executionTime: 0.05
        );

        $log = SearchLog::latest()->first();
        $this->assertNull($log->user_id);
    }

    public function test_log_query_handles_errors_gracefully(): void
    {
        // This should not throw an exception
        $this->analyticsService->logQuery(
            query: str_repeat('a', 1000),
            resultCount: -1,
            executionTime: -0.5
        );

        $this->assertTrue(true);
    }

    // ========== Click Tracking Tests ==========

    public function test_log_click_creates_click_entry(): void
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

    public function test_log_click_handles_multiple_clicks(): void
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

        $this->analyticsService->logClick($log->id, $post->id, 1);
        $this->analyticsService->logClick($log->id, $post->id, 2);

        $clicks = SearchClick::where('search_log_id', $log->id)->get();
        $this->assertCount(2, $clicks);
    }

    public function test_log_click_handles_errors_gracefully(): void
    {
        // This should not throw an exception
        $this->analyticsService->logClick(
            searchLogId: 99999,
            postId: 99999,
            position: 1
        );

        $this->assertTrue(true);
    }

    // ========== Analytics Retrieval Tests ==========

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

    public function test_get_top_queries_respects_limit(): void
    {
        SearchLog::create(['query' => 'Query1', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Query2', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Query3', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $topQueries = $this->analyticsService->getTopQueries(limit: 2, period: 'month');

        $this->assertCount(2, $topQueries);
    }

    public function test_get_top_queries_respects_period_filter(): void
    {
        $oldLog = new SearchLog([
            'query' => 'Old Query',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog->created_at = now()->subMonths(2);
        $oldLog->updated_at = now()->subMonths(2);
        $oldLog->save();

        SearchLog::create(['query' => 'Recent Query', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $topQueries = $this->analyticsService->getTopQueries(limit: 10, period: 'month');

        $this->assertCount(1, $topQueries);
        $this->assertEquals('Recent Query', $topQueries->first()->query);
    }

    public function test_get_top_queries_supports_different_periods(): void
    {
        SearchLog::create(['query' => 'Test', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $dayQueries = $this->analyticsService->getTopQueries(limit: 10, period: 'day');
        $weekQueries = $this->analyticsService->getTopQueries(limit: 10, period: 'week');
        $monthQueries = $this->analyticsService->getTopQueries(limit: 10, period: 'month');
        $yearQueries = $this->analyticsService->getTopQueries(limit: 10, period: 'year');

        $this->assertIsIterable($dayQueries);
        $this->assertIsIterable($weekQueries);
        $this->assertIsIterable($monthQueries);
        $this->assertIsIterable($yearQueries);
    }

    public function test_get_no_result_queries_returns_only_zero_result_queries(): void
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

    public function test_get_no_result_queries_respects_limit(): void
    {
        SearchLog::create(['query' => 'No1', 'result_count' => 0, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'No2', 'result_count' => 0, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'No3', 'result_count' => 0, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $noResultQueries = $this->analyticsService->getNoResultQueries(limit: 2);

        $this->assertCount(2, $noResultQueries);
    }

    public function test_get_performance_metrics_calculates_averages(): void
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

    public function test_get_performance_metrics_handles_empty_data(): void
    {
        $metrics = $this->analyticsService->getPerformanceMetrics(period: 'day');

        $this->assertEquals(0, $metrics['avg_execution_time']);
        $this->assertEquals(0, $metrics['total_searches']);
        $this->assertEquals(0, $metrics['no_result_searches']);
        $this->assertEquals(0, $metrics['no_result_percentage']);
    }

    public function test_get_performance_metrics_supports_different_periods(): void
    {
        SearchLog::create([
            'query' => 'Test',
            'result_count' => 1,
            'execution_time' => 0.1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $dayMetrics = $this->analyticsService->getPerformanceMetrics(period: 'day');
        $weekMetrics = $this->analyticsService->getPerformanceMetrics(period: 'week');
        $monthMetrics = $this->analyticsService->getPerformanceMetrics(period: 'month');
        $yearMetrics = $this->analyticsService->getPerformanceMetrics(period: 'year');

        $this->assertIsArray($dayMetrics);
        $this->assertIsArray($weekMetrics);
        $this->assertIsArray($monthMetrics);
        $this->assertIsArray($yearMetrics);
    }

    public function test_get_click_through_rate_calculates_percentage(): void
    {
        $log1 = SearchLog::create(['query' => 'Query 1', 'result_count' => 5, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        $log2 = SearchLog::create(['query' => 'Query 2', 'result_count' => 3, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Query 3', 'result_count' => 2, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

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

    public function test_get_click_through_rate_returns_zero_for_no_clicks(): void
    {
        SearchLog::create(['query' => 'Test', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $ctr = $this->analyticsService->getClickThroughRate(period: 'day');

        $this->assertEquals(0.0, $ctr);
    }

    public function test_get_most_clicked_posts_returns_top_posts(): void
    {
        $post1 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post2 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post3 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        $log1 = SearchLog::create(['query' => 'test1', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        $log2 = SearchLog::create(['query' => 'test2', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        $log3 = SearchLog::create(['query' => 'test3', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post1->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $log2->id, 'post_id' => $post1->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $log3->id, 'post_id' => $post1->id, 'position' => 1]);

        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post2->id, 'position' => 2]);
        SearchClick::create(['search_log_id' => $log2->id, 'post_id' => $post2->id, 'position' => 2]);

        SearchClick::create(['search_log_id' => $log1->id, 'post_id' => $post3->id, 'position' => 3]);

        $mostClicked = $this->analyticsService->getMostClickedPosts(limit: 2, period: 'month');

        $this->assertCount(2, $mostClicked);
        $this->assertEquals($post1->id, $mostClicked->first()->post_id);
        $this->assertEquals(3, $mostClicked->first()->click_count);
        $this->assertEquals($post2->id, $mostClicked->get(1)->post_id);
        $this->assertEquals(2, $mostClicked->get(1)->click_count);
    }

    public function test_get_most_clicked_posts_respects_limit(): void
    {
        $post1 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);
        $post2 = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        $log = SearchLog::create(['query' => 'test', 'result_count' => 2, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        SearchClick::create(['search_log_id' => $log->id, 'post_id' => $post1->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $log->id, 'post_id' => $post2->id, 'position' => 2]);

        $mostClicked = $this->analyticsService->getMostClickedPosts(limit: 1, period: 'month');

        $this->assertCount(1, $mostClicked);
    }

    public function test_get_most_clicked_posts_respects_period(): void
    {
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        $oldLog = new SearchLog([
            'query' => 'old',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog->created_at = now()->subMonths(2);
        $oldLog->updated_at = now()->subMonths(2);
        $oldLog->save();

        $recentLog = SearchLog::create(['query' => 'recent', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        SearchClick::create(['search_log_id' => $oldLog->id, 'post_id' => $post->id, 'position' => 1]);
        SearchClick::create(['search_log_id' => $recentLog->id, 'post_id' => $post->id, 'position' => 1]);

        $mostClicked = $this->analyticsService->getMostClickedPosts(limit: 10, period: 'month');

        $this->assertCount(1, $mostClicked);
        $this->assertEquals(1, $mostClicked->first()->click_count);
    }

    // ========== Log Archiving Tests ==========

    public function test_archive_logs_removes_old_entries(): void
    {
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

        SearchLog::create(['query' => 'Recent', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $archivedCount = $this->analyticsService->archiveLogs(daysToKeep: 90);

        $this->assertEquals(2, $archivedCount);
        $this->assertEquals(1, SearchLog::count());
        $this->assertEquals('Recent', SearchLog::first()->query);
    }

    public function test_archive_logs_keeps_recent_entries(): void
    {
        SearchLog::create(['query' => 'Recent 1', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);
        SearchLog::create(['query' => 'Recent 2', 'result_count' => 1, 'search_type' => 'posts', 'fuzzy_enabled' => true]);

        $archivedCount = $this->analyticsService->archiveLogs(daysToKeep: 90);

        $this->assertEquals(0, $archivedCount);
        $this->assertEquals(2, SearchLog::count());
    }

    public function test_archive_logs_handles_empty_logs(): void
    {
        $archivedCount = $this->analyticsService->archiveLogs(daysToKeep: 90);

        $this->assertEquals(0, $archivedCount);
    }

    public function test_archive_logs_handles_errors_gracefully(): void
    {
        // This should not throw an exception
        $archivedCount = $this->analyticsService->archiveLogs(daysToKeep: 90);

        $this->assertIsInt($archivedCount);
    }

    public function test_archive_logs_respects_days_to_keep(): void
    {
        $oldLog = new SearchLog([
            'query' => 'Old',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog->created_at = now()->subDays(50);
        $oldLog->updated_at = now()->subDays(50);
        $oldLog->save();

        $archivedCount = $this->analyticsService->archiveLogs(daysToKeep: 30);

        $this->assertEquals(1, $archivedCount);
    }
}

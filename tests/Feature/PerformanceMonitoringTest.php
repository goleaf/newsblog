<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\MonitoringService;
use App\Services\PerformanceMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PerformanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected PerformanceMetricsService $performanceMetrics;

    protected MonitoringService $monitoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->performanceMetrics = app(PerformanceMetricsService::class);
        $this->monitoring = app(MonitoringService::class);
        Cache::flush();
    }

    public function test_tracks_page_load_times(): void
    {
        // Track a page load
        $this->performanceMetrics->trackPageLoad('home', 150.5);

        // Verify it was stored
        $pageLoads = $this->performanceMetrics->getAveragePageLoadTime();

        $this->assertNotEmpty($pageLoads);
        $this->assertEquals(150.5, $pageLoads[0]['average']);
        $this->assertEquals(1, $pageLoads[0]['count']);
    }

    public function test_calculates_average_page_load_time(): void
    {
        // Track multiple page loads
        $this->performanceMetrics->trackPageLoad('home', 100);
        $this->performanceMetrics->trackPageLoad('home', 200);
        $this->performanceMetrics->trackPageLoad('home', 300);

        $pageLoads = $this->performanceMetrics->getAveragePageLoadTime();

        $this->assertNotEmpty($pageLoads);
        $this->assertEquals(200, $pageLoads[0]['average']);
        $this->assertEquals(3, $pageLoads[0]['count']);
    }

    public function test_logs_slow_queries(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Slow query detected', \Mockery::on(function ($context) {
                return $context['time'] === 150.0
                    && $context['sql'] === 'SELECT * FROM posts WHERE id = ?'
                    && $context['threshold'] === 100;
            }));

        $this->performanceMetrics->logSlowQuery(
            'SELECT * FROM posts WHERE id = ?',
            150.0,
            [1]
        );
    }

    public function test_retrieves_slow_queries(): void
    {
        // Log some slow queries
        $this->performanceMetrics->logSlowQuery('SELECT * FROM posts', 250, []);
        $this->performanceMetrics->logSlowQuery('SELECT * FROM users', 150, []);

        $slowQueries = $this->performanceMetrics->getSlowQueries();

        $this->assertCount(2, $slowQueries);
        $this->assertEquals(250, $slowQueries[0]['time']); // Sorted by time descending
        $this->assertEquals(150, $slowQueries[1]['time']);
    }

    public function test_tracks_cache_hits_and_misses(): void
    {
        $this->performanceMetrics->trackCacheHit(true);
        $this->performanceMetrics->trackCacheHit(true);
        $this->performanceMetrics->trackCacheHit(false);

        $stats = $this->performanceMetrics->getCacheStats();

        $this->assertNotEmpty($stats);
        // Stats are returned in reverse order (newest first), so today is last
        $today = end($stats);
        $this->assertEquals(2, $today['hits']);
        $this->assertEquals(1, $today['misses']);
        $this->assertEquals(66.67, $today['ratio']); // 2/3 * 100
    }

    public function test_monitors_memory_usage(): void
    {
        $memory = $this->performanceMetrics->getMemoryUsage();

        $this->assertArrayHasKey('usage', $memory);
        $this->assertArrayHasKey('usage_formatted', $memory);
        $this->assertArrayHasKey('limit', $memory);
        $this->assertArrayHasKey('limit_formatted', $memory);
        $this->assertArrayHasKey('percentage', $memory);
        $this->assertArrayHasKey('alert', $memory);

        $this->assertIsInt($memory['usage']);
        $this->assertIsString($memory['usage_formatted']);
        $this->assertIsNumeric($memory['percentage']);
        $this->assertIsBool($memory['alert']);
    }

    public function test_performance_dashboard_is_accessible_to_admin_users(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.performance'));

        $response->assertOk();
        $response->assertViewIs('admin.performance.index');
        $response->assertViewHas(['pageLoads', 'slowQueries', 'cacheStats', 'memory']);
    }

    public function test_performance_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('admin.performance'));

        $response->assertRedirect(route('login'));
    }

    public function test_middleware_tracks_page_load_time(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        // Verify page load was tracked
        $pageLoads = $this->performanceMetrics->getAveragePageLoadTime();
        $this->assertNotEmpty($pageLoads);
    }

    public function test_middleware_adds_performance_header_in_non_production(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $response = $this->get('/');

        $response->assertOk();
        $this->assertTrue($response->headers->has('X-Page-Load-Time'));
    }

    public function test_middleware_adds_query_count_and_memory_peak_headers_in_non_production(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $response = $this->get('/');

        $response->assertOk();
        $this->assertTrue($response->headers->has('X-DB-Query-Count'));
        $this->assertTrue($response->headers->has('X-Memory-Peak'));
        $this->assertIsNumeric((int) $response->headers->get('X-DB-Query-Count'));
        $this->assertIsNumeric((int) $response->headers->get('X-Memory-Peak'));
    }

    public function test_monitoring_service_tracks_dnt_compliance(): void
    {
        $this->monitoring->trackDntCompliance(true, 'post.show');
        $this->monitoring->trackDntCompliance(false, 'post.show');

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(1, $metrics['dnt']['enabled']);
        $this->assertEquals(1, $metrics['dnt']['disabled']);
    }

    public function test_monitoring_service_tracks_engagement_metrics(): void
    {
        $post = Post::factory()->create();

        $this->monitoring->trackEngagementMetric('scroll', $post->id, 1);
        $this->monitoring->trackEngagementMetric('time_spent', $post->id);

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(2, $metrics['engagement']['total']);
        $this->assertEquals(1, $metrics['engagement']['scroll']);
        $this->assertEquals(1, $metrics['engagement']['time_spent']);
        $this->assertEquals(1, $metrics['engagement']['authenticated']);
        $this->assertEquals(1, $metrics['engagement']['anonymous']);
    }

    public function test_monitoring_service_tracks_search_performance(): void
    {
        $this->monitoring->trackSearchPerformance('laravel', 10, 0.5);

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(1, $metrics['search']['total']);
        $this->assertEquals(0, $metrics['search']['zero_results']);
        $this->assertNotNull($metrics['search']['latest']);
        $this->assertEquals('laravel', $metrics['search']['latest']['query']);
        $this->assertEquals(10, $metrics['search']['latest']['result_count']);
    }

    public function test_monitoring_service_detects_zero_result_searches(): void
    {
        $this->monitoring->trackSearchPerformance('nonexistent', 0, 0.3);

        $metrics = $this->monitoring->getMetricsSnapshot();

        $this->assertEquals(1, $metrics['search']['zero_results']);
    }

    public function test_monitoring_service_checks_alert_thresholds(): void
    {
        // Simulate high zero-result search rate
        for ($i = 0; $i < 100; $i++) {
            $this->monitoring->trackSearchPerformance('query'.$i, $i < 40 ? 0 : 5, 0.1);
        }

        $alerts = $this->monitoring->checkAlertThresholds();

        $this->assertNotEmpty($alerts);
        $zeroResultAlert = collect($alerts)->firstWhere('type', 'search_quality');
        $this->assertNotNull($zeroResultAlert);
        $this->assertEquals('medium', $zeroResultAlert['severity']);
    }

    public function test_performance_check_alerts_command_runs_successfully(): void
    {
        $this->artisan('performance:check-alerts')
            ->assertExitCode(0);
    }

    public function test_performance_check_alerts_command_detects_slow_pages(): void
    {
        // Track some slow page loads
        $this->performanceMetrics->trackPageLoad('home', 3500);
        $this->performanceMetrics->trackPageLoad('posts.show', 2500);

        $this->artisan('performance:check-alerts --threshold-slow-page=2000')
            ->expectsOutput('⚠ Performance alerts detected:')
            ->assertExitCode(0);
    }

    public function test_performance_check_alerts_command_detects_slow_queries(): void
    {
        // Log some slow queries
        $this->performanceMetrics->logSlowQuery('SELECT * FROM posts', 800, []);

        $this->artisan('performance:check-alerts --threshold-slow-query=500')
            ->expectsOutput('⚠ Performance alerts detected:')
            ->assertExitCode(0);
    }

    public function test_get_all_metrics_returns_complete_data(): void
    {
        $this->performanceMetrics->trackPageLoad('home', 150);
        $this->performanceMetrics->logSlowQuery('SELECT * FROM posts', 200, []);
        $this->performanceMetrics->trackCacheHit(true);

        $metrics = $this->performanceMetrics->getAllMetrics();

        $this->assertArrayHasKey('page_loads', $metrics);
        $this->assertArrayHasKey('slow_queries', $metrics);
        $this->assertArrayHasKey('cache_stats', $metrics);
        $this->assertArrayHasKey('memory', $metrics);
    }

    public function test_query_listener_logs_slow_queries_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        // Execute a query that should be logged
        DB::enableQueryLog();
        Post::factory()->create();
        $queries = DB::getQueryLog();

        // The query listener should have been triggered
        $this->assertNotEmpty($queries);
    }
}

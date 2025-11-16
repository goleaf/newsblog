<?php

namespace Tests\Unit;

use App\Services\PerformanceMetricsService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PerformanceMetricsServiceTest extends TestCase
{
    private PerformanceMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PerformanceMetricsService;
        Cache::flush();
    }

    public function test_tracks_page_load_time(): void
    {
        $this->service->trackPageLoad('home', 150.5);

        $key = 'performance.page_loads.'.date('Y-m-d-H');
        $data = Cache::get($key);

        $this->assertNotNull($data);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals('home', $data[0]['route']);
        $this->assertEquals(150.5, $data[0]['load_time']);
    }

    public function test_logs_slow_query(): void
    {
        $this->service->logSlowQuery('SELECT * FROM posts', 150.0, []);

        $key = 'performance.slow_queries.'.date('Y-m-d');
        $queries = Cache::get($key);

        $this->assertNotNull($queries);
        $this->assertIsArray($queries);
        $this->assertCount(1, $queries);
        $this->assertEquals('SELECT * FROM posts', $queries[0]['sql']);
        $this->assertEquals(150.0, $queries[0]['time']);
    }

    public function test_does_not_log_fast_query(): void
    {
        $this->service->logSlowQuery('SELECT * FROM posts', 50.0, []);

        $key = 'performance.slow_queries.'.date('Y-m-d');
        $queries = Cache::get($key);

        $this->assertNull($queries);
    }

    public function test_tracks_cache_hit(): void
    {
        $this->service->trackCacheHit(true);

        $key = 'performance.cache_stats.'.date('Y-m-d');
        $stats = Cache::get($key);

        $this->assertNotNull($stats);
        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(0, $stats['misses']);
    }

    public function test_tracks_cache_miss(): void
    {
        $this->service->trackCacheHit(false);

        $key = 'performance.cache_stats.'.date('Y-m-d');
        $stats = Cache::get($key);

        $this->assertNotNull($stats);
        $this->assertEquals(0, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
    }

    public function test_gets_memory_usage(): void
    {
        $memory = $this->service->getMemoryUsage();

        $this->assertIsArray($memory);
        $this->assertArrayHasKey('usage', $memory);
        $this->assertArrayHasKey('usage_formatted', $memory);
        $this->assertArrayHasKey('limit', $memory);
        $this->assertArrayHasKey('limit_formatted', $memory);
        $this->assertArrayHasKey('percentage', $memory);
        $this->assertArrayHasKey('alert', $memory);
        $this->assertIsInt($memory['usage']);
        $this->assertIsString($memory['usage_formatted']);
        $this->assertIsBool($memory['alert']);
    }

    public function test_gets_cache_stats(): void
    {
        // Add some test data
        $this->service->trackCacheHit(true);
        $this->service->trackCacheHit(true);
        $this->service->trackCacheHit(false);

        $stats = $this->service->getCacheStats(1);

        $this->assertIsArray($stats);
        $this->assertCount(1, $stats);
        $this->assertEquals(2, $stats[0]['hits']);
        $this->assertEquals(1, $stats[0]['misses']);
        $this->assertEquals(66.67, $stats[0]['ratio']);
    }

    public function test_gets_all_metrics(): void
    {
        $metrics = $this->service->getAllMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('page_loads', $metrics);
        $this->assertArrayHasKey('slow_queries', $metrics);
        $this->assertArrayHasKey('cache_stats', $metrics);
        $this->assertArrayHasKey('memory', $metrics);
    }

    public function test_memory_usage_trend_from_page_loads(): void
    {
        // Track page loads with memory peaks
        $this->service->trackPageLoad('home', 120.0, 5, 50 * 1024 * 1024); // 50MB
        $this->service->trackPageLoad('home', 180.0, 8, 70 * 1024 * 1024); // 70MB

        $trend = $this->service->getAverageMemoryUsage();

        $this->assertNotEmpty($trend);
        $last = end($trend);
        $this->assertArrayHasKey('average_mb', $last);
        $this->assertEquals(60.0, $last['average_mb']);
        $this->assertEquals(2, $last['count']);
    }
}

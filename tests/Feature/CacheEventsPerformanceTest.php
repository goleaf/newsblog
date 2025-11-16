<?php

namespace Tests\Feature;

use App\Services\PerformanceMetricsService;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheEventsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cache_hit_and_miss_events_update_metrics(): void
    {
        // Dispatch events as Laravel would
        event(new CacheMissed(config('cache.default'), 'foo'));
        event(new CacheHit(config('cache.default'), 'bar', 'baz'));

        $service = app(PerformanceMetricsService::class);
        $stats = $service->getCacheStats(1);

        $this->assertNotEmpty($stats);
        $today = end($stats);
        $this->assertEquals(1, $today['hits']);
        $this->assertEquals(1, $today['misses']);
        $this->assertEquals(50.0, $today['ratio']);
    }
}

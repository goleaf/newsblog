<?php

namespace Tests\Feature;

use App\Jobs\Analytics\CalculateDailyMetricsJob;
use App\Jobs\Analytics\CleanOldAnalyticsDataJob;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalyticsJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_daily_metrics_job_stores_cache(): void
    {
        // Create a couple of search logs today
        SearchLog::factory()->create([
            'query' => 'laravel',
            'result_count' => 10,
            'execution_time' => 120,
            'user_id' => null,
            'created_at' => now()->subMinutes(10),
        ]);
        SearchLog::factory()->create([
            'query' => 'php',
            'result_count' => 0,
            'execution_time' => 80,
            'user_id' => null,
            'created_at' => now()->subMinutes(5),
        ]);

        (new CalculateDailyMetricsJob)->handle(app(\App\Services\SearchAnalyticsService::class));

        $key = 'analytics:search:daily:'.now()->toDateString();
        $this->assertTrue(Cache::has($key));
        $metrics = Cache::get($key);
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_searches', $metrics);
    }

    public function test_clean_old_analytics_data_job_archives(): void
    {
        // Old log beyond 1 day retention
        SearchLog::factory()->create([
            'query' => 'old',
            'result_count' => 1,
            'execution_time' => 50,
            'user_id' => null,
            'created_at' => now()->subDays(2),
        ]);

        // Recent log
        SearchLog::factory()->create([
            'query' => 'recent',
            'result_count' => 2,
            'execution_time' => 40,
            'user_id' => null,
            'created_at' => now()->subHours(1),
        ]);

        // Run cleanup for 1 day retention
        (new CleanOldAnalyticsDataJob(1))->handle(app(\App\Services\SearchAnalyticsService::class));

        $this->assertDatabaseMissing('search_logs', ['query' => 'old']);
        $this->assertDatabaseHas('search_logs', ['query' => 'recent']);
    }
}

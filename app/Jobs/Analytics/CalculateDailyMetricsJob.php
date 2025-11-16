<?php

namespace App\Jobs\Analytics;

use App\Services\SearchAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class CalculateDailyMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SearchAnalyticsService $searchAnalytics): void
    {
        $metrics = $searchAnalytics->getPerformanceMetrics('day');
        Cache::put('analytics:search:daily:'.now()->toDateString(), $metrics, now()->addDays(7));
    }
}

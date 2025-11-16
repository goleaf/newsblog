<?php

namespace App\Jobs\Analytics;

use App\Services\SearchAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class AggregateWeeklyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SearchAnalyticsService $searchAnalytics): void
    {
        $metrics = $searchAnalytics->getPerformanceMetrics('week');
        Cache::put('analytics:search:weekly:'.now()->startOfWeek()->toDateString(), $metrics, now()->addWeeks(4));
    }
}

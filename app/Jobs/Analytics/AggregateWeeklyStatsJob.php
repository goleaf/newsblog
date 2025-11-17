<?php

namespace App\Jobs\Analytics;

use App\Services\AnalyticsService;
use App\Services\SearchAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AggregateWeeklyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Aggregate and cache weekly analytics stats.
     * Requirements: 8.1
     */
    public function handle(AnalyticsService $analytics, SearchAnalyticsService $searchAnalytics): void
    {
        $endDate = now();
        $startDate = $endDate->copy()->subWeek();

        // Aggregate weekly stats
        $aggregatedStats = $analytics->aggregateStats('daily', $startDate, $endDate);
        Cache::put('analytics:weekly:aggregated:'.$startDate->format('Y-W'), $aggregatedStats, now()->addWeeks(4));

        // Calculate user metrics for the week
        $userMetrics = $analytics->calculateUserMetrics($startDate, $endDate);
        Cache::put('analytics:weekly:users:'.$startDate->format('Y-W'), $userMetrics, now()->addWeeks(4));

        // Calculate traffic metrics for the week
        $trafficMetrics = $analytics->calculateTrafficMetrics($startDate, $endDate);
        Cache::put('analytics:weekly:traffic:'.$startDate->format('Y-W'), $trafficMetrics, now()->addWeeks(4));

        // Calculate search metrics
        $searchMetrics = $searchAnalytics->getPerformanceMetrics('week');
        Cache::put('analytics:weekly:search:'.$startDate->format('Y-W'), $searchMetrics, now()->addWeeks(4));

        Log::info('Weekly analytics stats aggregated', [
            'week' => $startDate->format('Y-W'),
            'period' => $startDate->toDateString().' to '.$endDate->toDateString(),
        ]);
    }
}

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

class CalculateDailyMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Calculate and cache daily analytics metrics.
     * Requirements: 8.1
     */
    public function handle(SearchAnalyticsService $searchAnalytics, ?AnalyticsService $analytics = null): void
    {
        $analytics = $analytics ?? app(AnalyticsService::class);
        $date = now();
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        // Calculate user metrics
        $userMetrics = $analytics->calculateUserMetrics($startDate, $endDate);
        Cache::put('analytics:daily:users:'.$date->toDateString(), $userMetrics, now()->addDays(7));

        // Calculate traffic metrics
        $trafficMetrics = $analytics->calculateTrafficMetrics($startDate, $endDate);
        Cache::put('analytics:daily:traffic:'.$date->toDateString(), $trafficMetrics, now()->addDays(7));

        // Calculate top articles
        $topArticles = $analytics->getTopArticles(50, $startDate, $endDate);
        Cache::put('analytics:daily:top_articles:'.$date->toDateString(), $topArticles, now()->addDays(7));

        // Calculate search metrics
        $searchMetrics = $searchAnalytics->getPerformanceMetrics('day');
        Cache::put('analytics:search:daily:'.$date->toDateString(), $searchMetrics, now()->addDays(7));

        Log::info('Daily analytics metrics calculated', [
            'date' => $date->toDateString(),
            'user_metrics' => $userMetrics,
            'traffic_total' => $trafficMetrics['total_traffic'] ?? 0,
            'top_articles_count' => count($topArticles),
        ]);
    }
}

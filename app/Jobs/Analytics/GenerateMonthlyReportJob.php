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

class GenerateMonthlyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Generate comprehensive monthly analytics report.
     * Requirements: 8.1
     */
    public function handle(AnalyticsService $analytics, SearchAnalyticsService $searchAnalytics): void
    {
        $endDate = now();
        $startDate = $endDate->copy()->subMonth();
        $monthKey = $endDate->format('Y-m');

        // Aggregate monthly stats
        $aggregatedStats = $analytics->aggregateStats('daily', $startDate, $endDate);
        Cache::put('analytics:monthly:aggregated:'.$monthKey, $aggregatedStats, now()->addMonths(6));

        // Calculate user metrics
        $userMetrics = $analytics->calculateUserMetrics($startDate, $endDate);
        Cache::put('analytics:monthly:users:'.$monthKey, $userMetrics, now()->addMonths(6));

        // Calculate traffic metrics
        $trafficMetrics = $analytics->calculateTrafficMetrics($startDate, $endDate);
        Cache::put('analytics:monthly:traffic:'.$monthKey, $trafficMetrics, now()->addMonths(6));

        // Get top articles
        $topArticles = $analytics->getTopArticles(100, $startDate, $endDate);
        Cache::put('analytics:monthly:top_articles:'.$monthKey, $topArticles, now()->addMonths(6));

        // Calculate search metrics
        $searchMetrics = $searchAnalytics->getPerformanceMetrics('month');
        Cache::put('analytics:monthly:search:'.$monthKey, $searchMetrics, now()->addMonths(6));

        // Generate summary report
        $report = [
            'period' => $monthKey,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_users' => $userMetrics['registrations']['total'] ?? 0,
                'new_users' => $userMetrics['registrations']['new_in_period'] ?? 0,
                'dau' => $userMetrics['active_users']['daily'] ?? 0,
                'mau' => $userMetrics['active_users']['monthly'] ?? 0,
                'total_traffic' => $trafficMetrics['total_traffic'] ?? 0,
                'top_article_views' => $topArticles[0]['views'] ?? 0,
                'total_searches' => $searchMetrics['total_searches'] ?? 0,
            ],
        ];

        Cache::put('analytics:monthly:report:'.$monthKey, $report, now()->addMonths(12));

        Log::info('Monthly analytics report generated', [
            'month' => $monthKey,
            'summary' => $report['summary'],
        ]);
    }
}

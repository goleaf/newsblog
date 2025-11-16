<?php

namespace App\Jobs\Analytics;

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

    public function handle(SearchAnalyticsService $searchAnalytics): void
    {
        $metrics = $searchAnalytics->getPerformanceMetrics('month');
        Cache::put('analytics:search:monthly:'.now()->format('Y-m'), $metrics, now()->addMonths(3));
        Log::info('Monthly search analytics report generated', ['period' => now()->format('Y-m'), 'metrics' => $metrics]);
    }
}

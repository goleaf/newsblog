<?php

namespace App\Jobs\Analytics;

use App\Models\EngagementMetric;
use App\Models\PostView;
use App\Models\TrafficSource;
use App\Services\SearchAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanOldAnalyticsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $daysToKeep = 90) {}

    /**
     * Clean old analytics data to maintain database performance.
     * Requirements: 8.1
     */
    public function handle(SearchAnalyticsService $searchAnalytics): void
    {
        $cutoffDate = now()->subDays($this->daysToKeep);

        $counts = [
            'search_logs' => 0,
            'post_views' => 0,
            'engagement_metrics' => 0,
            'traffic_sources' => 0,
        ];

        // Archive search logs
        $counts['search_logs'] = $searchAnalytics->archiveLogs($this->daysToKeep);

        // Clean old post views (keep aggregated data in cache)
        $counts['post_views'] = PostView::where('viewed_at', '<', $cutoffDate)->delete();

        // Clean old engagement metrics
        $counts['engagement_metrics'] = EngagementMetric::where('created_at', '<', $cutoffDate)->delete();

        // Clean old traffic sources
        $counts['traffic_sources'] = TrafficSource::where('created_at', '<', $cutoffDate)->delete();

        // Optimize tables after cleanup (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('OPTIMIZE TABLE post_views');
            DB::statement('OPTIMIZE TABLE engagement_metrics');
            DB::statement('OPTIMIZE TABLE traffic_sources');
            DB::statement('OPTIMIZE TABLE search_logs');
        }

        Log::info('Cleaned old analytics data', [
            'days_to_keep' => $this->daysToKeep,
            'cutoff_date' => $cutoffDate->toDateString(),
            'deleted_counts' => $counts,
        ]);
    }
}

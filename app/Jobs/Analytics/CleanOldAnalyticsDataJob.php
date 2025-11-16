<?php

namespace App\Jobs\Analytics;

use App\Services\SearchAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanOldAnalyticsDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $daysToKeep = 90) {}

    public function handle(SearchAnalyticsService $searchAnalytics): void
    {
        $archived = $searchAnalytics->archiveLogs($this->daysToKeep);
        Log::info('Cleaned old analytics data', ['archived' => $archived, 'days_to_keep' => $this->daysToKeep]);
    }
}

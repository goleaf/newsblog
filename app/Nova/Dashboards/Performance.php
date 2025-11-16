<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\AveragePageLoad;
use App\Nova\Metrics\CacheHitRatio;
use App\Nova\Metrics\MemoryUsage;
use App\Nova\Metrics\MemoryUsageTrend;
use App\Nova\Metrics\QueryCountTrend;
use App\Nova\Metrics\SlowQueriesTable;
use Laravel\Nova\Dashboards\Dashboard;

class Performance extends Dashboard
{
    /**
     * Get the displayable name of the dashboard.
     */
    public function name(): string
    {
        return 'Performance';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(): array
    {
        return [
            (new MemoryUsage)->width('1/3'),
            (new MemoryUsageTrend)->width('full'),
            (new QueryCountTrend)->width('full'),
            (new AveragePageLoad)->width('full'),
            (new CacheHitRatio)->width('full'),
            (new SlowQueriesTable)->width('full'),
        ];
    }

    public function uriKey(): string
    {
        return 'performance';
    }
}

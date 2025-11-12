<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\PostsByCategory;
use App\Nova\Metrics\PostsByStatus;
use App\Nova\Metrics\PostsPerDay;
use App\Nova\Metrics\TotalPosts;
use App\Nova\Metrics\TotalUsers;
use App\Nova\Metrics\TotalViews;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{
    /**
     * Get the displayable name of the dashboard.
     */
    public function name(): string
    {
        return 'Tech News Dashboard';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(): array
    {
        return [
            // Value metrics - showing key statistics
            (new TotalPosts)->width('1/3'),
            (new TotalUsers)->width('1/3'),
            (new TotalViews)->width('1/3'),

            // Trend metric - showing posts over time
            (new PostsPerDay)->width('full'),

            // Partition metrics - showing distribution
            (new PostsByStatus)->width('1/2'),
            (new PostsByCategory)->width('1/2'),
        ];
    }

    /**
     * Get the URI key for the dashboard.
     */
    public function uriKey(): string
    {
        return 'main';
    }
}

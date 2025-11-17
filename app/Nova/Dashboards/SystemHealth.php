<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\ActiveUsers;
use App\Nova\Metrics\ContentStatistics;
use App\Nova\Metrics\DatabaseStatus;
use App\Nova\Metrics\QueueStatus;
use App\Nova\Metrics\RecentActivity;
use App\Nova\Metrics\RedisStatus;
use App\Nova\Metrics\StorageStatus;
use App\Nova\Metrics\SystemHealthOverview;
use App\Nova\Metrics\UserStatistics;
use Laravel\Nova\Dashboard;

class SystemHealth extends Dashboard
{
    /**
     * Get the displayable name of the dashboard.
     */
    public function name(): string
    {
        return 'System Health';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array<int, \Laravel\Nova\Card>
     */
    public function cards(): array
    {
        return [
            // System health overview
            (new SystemHealthOverview)->width('full'),

            // Connection status metrics
            (new DatabaseStatus)->width('1/3'),
            (new RedisStatus)->width('1/3'),
            (new StorageStatus)->width('1/3'),

            // Queue and activity metrics
            (new QueueStatus)->width('1/2'),
            (new ActiveUsers)->width('1/2'),

            // User and content statistics
            (new ContentStatistics)->width('1/2'),
            (new UserStatistics)->width('1/2'),

            // Recent activity feed
            (new RecentActivity)->width('full'),
        ];
    }

    /**
     * Get the URI key for the dashboard.
     */
    public function uriKey(): string
    {
        return 'system-health';
    }
}

<?php

namespace App\Nova\Metrics;

use App\Models\User;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class ActiveUsers extends Value
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Active Users';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        // Users active in the last 24 hours
        $activeUsers = User::where('last_login_at', '>=', now()->subDay())->count();

        // Total active users
        $totalUsers = User::where('status', 'active')->count();

        return $this->result($activeUsers)
            ->suffix("of {$totalUsers} total")
            ->format(function ($value) {
                return "<span class='text-blue-600 font-semibold'>{$value}</span>";
            });
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'active-users';
    }
}

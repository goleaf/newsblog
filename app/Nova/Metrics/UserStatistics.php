<?php

namespace App\Nova\Metrics;

use App\Models\User;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class UserStatistics extends Partition
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Users by Role';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): mixed
    {
        return $this->count($request, User::class, 'role')
            ->colors([
                'admin' => '#DC2626',
                'moderator' => '#F59E0B',
                'author' => '#3B82F6',
                'reader' => '#10B981',
            ]);
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'user-statistics';
    }
}

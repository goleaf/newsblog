<?php

namespace App\Nova\Metrics;

use App\Models\Post;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class PostsByStatus extends Partition
{
    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, Post::class, 'status')
            ->label(fn ($value) => match ($value) {
                'draft' => 'Draft',
                'published' => 'Published',
                'scheduled' => 'Scheduled',
                'archived' => 'Archived',
                default => ucfirst($value ?? 'Unknown'),
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
        return 'posts-by-status';
    }
}

<?php

namespace App\Nova\Metrics;

use App\Models\Comment;
use App\Models\Post;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class ContentStatistics extends Value
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Total Content';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        $posts = Post::count();
        $comments = Comment::count();

        return $this->result($posts)
            ->suffix("{$comments} comments")
            ->format(function ($value) use ($comments) {
                return "<div class='text-center'>
                    <div class='text-2xl font-bold text-blue-600'>{$value}</div>
                    <div class='text-sm text-gray-600'>Posts</div>
                    <div class='text-xl font-semibold text-green-600 mt-2'>{$comments}</div>
                    <div class='text-sm text-gray-600'>Comments</div>
                </div>";
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
        return 'content-statistics';
    }
}

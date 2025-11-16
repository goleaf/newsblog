<?php

namespace App\Nova\Metrics;

use App\Enums\PostStatus;
use App\Models\Post;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\MetricTableRow;
use Laravel\Nova\Metrics\Table;

class PopularPosts extends Table
{
    /**
     * Calculate the value of the metric.
     *
     * @return array<int, \Laravel\Nova\Metrics\MetricTableRow>
     */
    public function calculate(NovaRequest $request): array
    {
        $posts = Post::where('status', PostStatus::Published)
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get();

        return $posts->map(function (Post $post) {
            $categoryName = $post->category?->name ?? 'Uncategorized';

            return MetricTableRow::make()
                ->icon('eye')
                ->iconClass('text-blue-500')
                ->title($post->title)
                ->subtitle("{$post->view_count} views • {$categoryName}")
                ->url("/nova/resources/posts/{$post->id}");
        })->toArray();
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
        return 'popular-posts';
    }
}

<?php

namespace App\Nova\Metrics;

use App\Models\Post;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class PostsByCategory extends Partition
{
    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        $results = Post::query()
            ->leftJoin('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('count(*) as count'))
            ->groupBy('categories.name')
            ->get()
            ->mapWithKeys(function ($item) {
                $categoryName = $item->name ?? 'Uncategorized';

                return [$categoryName => $item->count];
            })
            ->toArray();

        return $this->result($results);
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
        return 'posts-by-category';
    }
}

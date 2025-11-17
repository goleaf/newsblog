<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * FilterService handles article filtering operations.
 *
 * Requirements: 6.2
 */
class FilterService
{
    /**
     * Apply filters to a query builder.
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Filter by category
        if (isset($filters['category']) && ! empty($filters['category'])) {
            $query = $this->filterByCategory($query, $filters['category']);
        }

        // Filter by author
        if (isset($filters['author']) && ! empty($filters['author'])) {
            $query = $this->filterByAuthor($query, $filters['author']);
        }

        // Filter by tags
        if (isset($filters['tags']) && ! empty($filters['tags'])) {
            $query = $this->filterByTags($query, $filters['tags']);
        }

        // Filter by date range
        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $query = $this->filterByDateRange(
                $query,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null
            );
        }

        // Filter by reading time
        if (isset($filters['reading_time_min']) || isset($filters['reading_time_max'])) {
            $query = $this->filterByReadingTime(
                $query,
                $filters['reading_time_min'] ?? null,
                $filters['reading_time_max'] ?? null
            );
        }

        return $query;
    }

    /**
     * Filter by category (supports both ID and slug).
     *
     * @param  mixed  $category
     */
    public function filterByCategory(Builder $query, $category): Builder
    {
        if (is_numeric($category)) {
            return $query->where('category_id', $category);
        }

        return $query->whereHas('category', function ($q) use ($category) {
            $q->where('slug', $category);
        });
    }

    /**
     * Filter by author (supports both ID and username).
     *
     * @param  mixed  $author
     */
    public function filterByAuthor(Builder $query, $author): Builder
    {
        if (is_numeric($author)) {
            return $query->where('user_id', $author);
        }

        return $query->whereHas('user', function ($q) use ($author) {
            $q->where('name', $author)
                ->orWhere('email', $author);
        });
    }

    /**
     * Filter by tags (supports array of tag IDs or slugs).
     *
     * @param  array|string  $tags
     */
    public function filterByTags(Builder $query, $tags): Builder
    {
        // Convert single tag to array
        if (! is_array($tags)) {
            $tags = [$tags];
        }

        // Filter out empty values
        $tags = array_filter($tags);

        if (empty($tags)) {
            return $query;
        }

        // Check if tags are numeric (IDs) or strings (slugs)
        $isNumeric = is_numeric($tags[0]);

        return $query->whereHas('tags', function ($q) use ($tags, $isNumeric) {
            if ($isNumeric) {
                $q->whereIn('tags.id', $tags);
            } else {
                $q->whereIn('tags.slug', $tags);
            }
        });
    }

    /**
     * Filter by date range.
     */
    public function filterByDateRange(Builder $query, ?string $dateFrom, ?string $dateTo): Builder
    {
        if ($dateFrom) {
            $query->where('published_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('published_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Filter by reading time range (in minutes).
     */
    public function filterByReadingTime(Builder $query, ?int $minTime, ?int $maxTime): Builder
    {
        if ($minTime !== null) {
            $query->where('reading_time', '>=', $minTime);
        }

        if ($maxTime !== null) {
            $query->where('reading_time', '<=', $maxTime);
        }

        return $query;
    }

    /**
     * Get available filter options for the UI.
     */
    public function getFilterOptions(): array
    {
        return [
            'categories' => \App\Models\Category::orderBy('name')->get(['id', 'name', 'slug']),
            'authors' => \App\Models\User::whereHas('posts', function ($q) {
                $q->published();
            })->orderBy('name')->get(['id', 'name']),
            'tags' => \App\Models\Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'reading_time_ranges' => [
                ['min' => 0, 'max' => 5, 'label' => 'Under 5 min'],
                ['min' => 5, 'max' => 10, 'label' => '5-10 min'],
                ['min' => 10, 'max' => 20, 'label' => '10-20 min'],
                ['min' => 20, 'max' => null, 'label' => 'Over 20 min'],
            ],
        ];
    }

    /**
     * Count active filters.
     */
    public function countActiveFilters(array $filters): int
    {
        $count = 0;

        if (! empty($filters['category'])) {
            $count++;
        }

        if (! empty($filters['author'])) {
            $count++;
        }

        if (! empty($filters['tags'])) {
            $count++;
        }

        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $count++;
        }

        if (! empty($filters['reading_time_min']) || ! empty($filters['reading_time_max'])) {
            $count++;
        }

        return $count;
    }

    /**
     * Apply sorting to a query builder.
     *
     * Requirements: 6.2
     */
    public function applySorting(Builder $query, string $sort = 'newest'): Builder
    {
        return match ($sort) {
            'relevance' => $query, // Relevance sorting is handled by search engine
            'oldest' => $query->oldest('published_at'),
            'popular' => $query->orderBy('view_count', 'desc'),
            'engagement' => $this->sortByEngagement($query),
            'reading_time_asc' => $query->orderBy('reading_time', 'asc'),
            'reading_time_desc' => $query->orderBy('reading_time', 'desc'),
            'newest' => $query->latest('published_at'),
            default => $query->latest('published_at'),
        };
    }

    /**
     * Sort by engagement (combination of comments and shares).
     */
    protected function sortByEngagement(Builder $query): Builder
    {
        return $query->withCount(['comments', 'reactions'])
            ->orderByRaw('(comments_count + reactions_count) DESC');
    }

    /**
     * Get available sorting options.
     */
    public function getSortingOptions(): array
    {
        return [
            'newest' => 'Newest First',
            'oldest' => 'Oldest First',
            'popular' => 'Most Popular',
            'engagement' => 'Most Engaged',
            'reading_time_asc' => 'Shortest Read',
            'reading_time_desc' => 'Longest Read',
        ];
    }

    /**
     * Build filter query string for URLs.
     */
    public function buildFilterQueryString(array $filters): string
    {
        $params = [];

        foreach ($filters as $key => $value) {
            if (! empty($value)) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $params[] = $key.'[]='.urlencode($v);
                    }
                } else {
                    $params[] = $key.'='.urlencode($value);
                }
            }
        }

        return implode('&', $params);
    }
}

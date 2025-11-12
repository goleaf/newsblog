<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * Search posts with full-text search capabilities.
     *
     * @param  string  $query  The search query
     * @param  array  $filters  Optional filters (category, author, date_from, date_to)
     * @param  int  $perPage  Results per page (default: 15)
     */
    public function search(string $query, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $queryBuilder = $this->buildSearchQuery($query, $filters);

        $posts = $queryBuilder->paginate($perPage);

        // Add highlighting to results
        $posts->getCollection()->transform(function ($post) use ($query) {
            $post->highlighted_title = $this->highlightMatches($post->title, $query);
            $post->highlighted_excerpt = $this->highlightMatches($post->excerpt ?? '', $query);

            return $post;
        });

        return $posts;
    }

    /**
     * Build the search query with filters.
     */
    protected function buildSearchQuery(string $query, array $filters = []): Builder
    {
        $queryBuilder = Post::query()
            ->published()
            ->with(['user', 'category', 'tags']);

        // Multi-field search (title, content, excerpt)
        $queryBuilder->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhere('content', 'like', "%{$query}%")
                ->orWhere('excerpt', 'like', "%{$query}%");
        });

        // Apply filters
        if (isset($filters['category'])) {
            $queryBuilder->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        if (isset($filters['author'])) {
            $queryBuilder->where('user_id', $filters['author']);
        }

        if (isset($filters['date_from'])) {
            $queryBuilder->where('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryBuilder->where('published_at', '<=', $filters['date_to']);
        }

        // Relevance-based sorting (exact title matches first)
        $queryBuilder->orderByRaw('
            CASE 
                WHEN title LIKE ? THEN 1
                WHEN title LIKE ? THEN 2
                WHEN excerpt LIKE ? THEN 3
                ELSE 4
            END
        ', [
            $query, // Exact match
            "%{$query}%", // Contains match
            "%{$query}%", // Excerpt match
        ])->latest('published_at');

        return $queryBuilder;
    }

    /**
     * Highlight matching terms in text.
     */
    public function highlightMatches(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return e($text);
        }

        // Split query into words
        $words = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);

        // Escape text first
        $escapedText = e($text);

        // Highlight each word
        foreach ($words as $word) {
            $escapedWord = preg_quote($word, '/');
            $escapedText = preg_replace(
                '/('.$escapedWord.')/iu',
                '<mark class="search-highlight">$1</mark>',
                $escapedText
            );
        }

        return $escapedText;
    }

    /**
     * Get search suggestions based on query.
     */
    public function getSuggestions(string $query, int $limit = 5): Collection
    {
        if (empty($query) || strlen($query) < 2) {
            return collect([]);
        }

        return Post::query()
            ->published()
            ->where('title', 'like', "%{$query}%")
            ->orderByRaw('
                CASE 
                    WHEN title LIKE ? THEN 1
                    WHEN title LIKE ? THEN 2
                    ELSE 3
                END
            ', [
                "{$query}%", // Starts with
                "%{$query}%", // Contains
            ])
            ->limit($limit)
            ->pluck('title');
    }

    /**
     * Count total search results without pagination.
     */
    public function countResults(string $query, array $filters = []): int
    {
        return $this->buildSearchQuery($query, $filters)->count();
    }
}

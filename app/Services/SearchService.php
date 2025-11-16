<?php

namespace App\Services;

use App\Models\Post;
use App\Models\SearchLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchService
{
    /**
     * Search posts with full-text search using SQLite FTS5.
     *
     * Implements Requirement 8: Full-text search using SQLite FTS5
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
     * Build the search query with filters using FTS5 when available.
     */
    protected function buildSearchQuery(string $query, array $filters = []): Builder
    {
        $driver = Schema::getConnection()->getDriverName();
        $useFts5 = $driver === 'sqlite' && $this->isFts5Available();

        $queryBuilder = Post::query()
            ->published()
            ->with(['user', 'category', 'tags']);

        // Use FTS5 for full-text search if available, otherwise fall back to LIKE queries
        if ($useFts5 && ! empty($query)) {
            // Use FTS5 for full-text search
            $ftsQuery = $this->prepareFts5Query($query);

            $queryBuilder->whereIn('id', function ($subQuery) use ($ftsQuery) {
                $subQuery->select('post_id')
                    ->from('posts_fts5')
                    ->whereRaw('posts_fts5 MATCH ?', [$ftsQuery])
                    ->orderByRaw('bm25(posts_fts5) ASC'); // Lower BM25 score = better match
            });

            // Sort by relevance (FTS5 ranking) then by published date
            $queryBuilder->orderByRaw('
                CASE 
                    WHEN posts.title LIKE ? THEN 1
                    WHEN posts.title LIKE ? THEN 2
                    WHEN posts.excerpt LIKE ? THEN 3
                    ELSE 4
                END
            ', [
                $query, // Exact match
                "%{$query}%", // Contains match
                "%{$query}%", // Excerpt match
            ])->latest('published_at');
        } else {
            // Fallback to LIKE queries for non-SQLite databases or when FTS5 is not available
            if (! empty($query)) {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%")
                        ->orWhere('excerpt', 'like', "%{$query}%");
                });

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
            } else {
                $queryBuilder->latest('published_at');
            }
        }

        // Apply filters
        if (isset($filters['category'])) {
            if (is_numeric($filters['category'])) {
                $queryBuilder->where('category_id', $filters['category']);
            } else {
                $queryBuilder->whereHas('category', function ($q) use ($filters) {
                    $q->where('slug', $filters['category']);
                });
            }
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

        return $queryBuilder;
    }

    /**
     * Prepare query for FTS5 search.
     * Escapes special characters and formats for FTS5 syntax.
     */
    protected function prepareFts5Query(string $query): string
    {
        // Remove special FTS5 characters and escape
        $query = trim($query);

        // Split into words and quote each word to allow phrase matching
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);

        // Format as: "word1" "word2" OR word1 OR word2
        // This allows both exact phrase matches and individual word matches
        $quotedWords = array_map(fn ($word) => '"'.str_replace('"', '""', $word).'"', $words);
        $ftsQuery = implode(' OR ', $quotedWords);

        // Also add the original query for broader matching
        $originalEscaped = str_replace('"', '""', $query);

        return "({$ftsQuery}) OR {$originalEscaped}";
    }

    /**
     * Check if FTS5 virtual table is available.
     */
    protected function isFts5Available(): bool
    {
        try {
            $exists = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='posts_fts5'");

            return ! empty($exists);
        } catch (\Exception $e) {
            return false;
        }
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
     * Get autocomplete suggestions with debouncing support.
     *
     * Implements Requirement 8: Autocomplete with debouncing
     * Note: Debouncing is handled on the frontend, this method provides the backend API
     *
     * @param  string  $query  The search query
     * @param  int  $limit  Maximum number of suggestions (default: 5)
     */
    public function autocomplete(string $query, int $limit = 5): Collection
    {
        if (empty($query) || strlen($query) < 2) {
            return collect([]);
        }

        $driver = Schema::getConnection()->getDriverName();
        $useFts5 = $driver === 'sqlite' && $this->isFts5Available();

        if ($useFts5) {
            // Use FTS5 for autocomplete suggestions
            $ftsQuery = $this->prepareFts5Query($query);

            return Post::query()
                ->published()
                ->whereIn('id', function ($subQuery) use ($ftsQuery) {
                    $subQuery->select('post_id')
                        ->from('posts_fts5')
                        ->whereRaw('posts_fts5 MATCH ?', [$ftsQuery])
                        ->orderByRaw('bm25(posts_fts5) ASC')
                        ->limit($limit * 2); // Get more for better sorting
                })
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
                ->pluck('title')
                ->unique();
        }

        // Fallback to LIKE queries
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
            ->pluck('title')
            ->unique();
    }

    /**
     * Get search suggestions based on query (alias for backward compatibility).
     */
    public function getSuggestions(string $query, int $limit = 5): Collection
    {
        return $this->autocomplete($query, $limit);
    }

    /**
     * Log search query for analytics.
     *
     * Implements Requirement 8: Search logging for analytics
     *
     * @param  string  $query  The search query
     * @param  int  $resultCount  Number of results found
     * @param  float  $executionTime  Execution time in milliseconds
     * @param  array  $metadata  Additional metadata (filters, search_type, etc.)
     */
    public function logSearch(string $query, int $resultCount, float $executionTime, array $metadata = []): void
    {
        try {
            SearchLog::create([
                'query' => $query,
                'result_count' => $resultCount,
                'execution_time' => $executionTime,
                'search_type' => $metadata['search_type'] ?? 'posts',
                'fuzzy_enabled' => $metadata['fuzzy_enabled'] ?? false,
                'threshold' => $metadata['threshold'] ?? null,
                'filters' => $metadata['filters'] ?? [],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail the search
            \Illuminate\Support\Facades\Log::error('Failed to log search', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get popular searches.
     *
     * Implements Requirement 8: Get popular searches for analytics
     *
     * @param  int  $limit  Maximum number of popular searches to return (default: 10)
     * @param  string  $period  Time period: 'day', 'week', 'month', 'year' (default: 'month')
     */
    public function getPopularSearches(int $limit = 10, string $period = 'month'): Collection
    {
        $date = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        return SearchLog::select('query', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $date)
            ->where('query', '!=', '')
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'query' => $log->query,
                    'count' => $log->count,
                ];
            });
    }

    /**
     * Count total search results without pagination.
     */
    public function countResults(string $query, array $filters = []): int
    {
        return $this->buildSearchQuery($query, $filters)->count();
    }
}

<?php

namespace App\Services;

use App\DataTransferObjects\SearchResult;
use App\Exceptions\FuzzySearch\FuzzySearchException;
use App\Exceptions\FuzzySearch\InvalidQueryException;
use App\Exceptions\FuzzySearch\SearchTimeoutException;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FuzzySearchService
{
    protected SearchIndexService $indexService;

    protected int $threshold;

    protected int $levenshteinDistance;

    protected bool $cacheEnabled;

    protected int $cacheTtl;

    protected string $cachePrefix;

    protected SearchAnalyticsService $analyticsService;

    public function __construct(SearchIndexService $indexService, SearchAnalyticsService $analyticsService)
    {
        $this->indexService = $indexService;
        $this->analyticsService = $analyticsService;
        $this->threshold = config('fuzzy-search.threshold', 60);
        $this->levenshteinDistance = config('fuzzy-search.levenshtein_distance', 2);
        $this->cacheEnabled = config('fuzzy-search.cache.enabled', true);
        $this->cacheTtl = config('fuzzy-search.cache.ttl', 600);
        $this->cachePrefix = config('fuzzy-search.cache.prefix', 'fuzzy_search');
    }

    /**
     * Generic search method with logging support
     */
    public function search(
        string $query,
        ?int $threshold = null,
        ?int $limit = null,
        array $filters = [],
        bool $logSearch = false
    ): Collection {
        if (empty(trim($query))) {
            return collect();
        }

        $startTime = microtime(true);

        try {
            $this->validateQuery($query);

            $threshold = $threshold ?? $this->threshold;
            $limit = $limit ?? 15;

            $index = $this->indexService->getIndex('posts');
            $results = $this->performSimpleFuzzySearch($query, $index, $threshold, $filters);

            $results = $results->take($limit);

            if ($logSearch) {
                $executionTime = microtime(true) - $startTime;

                $this->analyticsService->logQuery(
                    query: $query,
                    resultCount: $results->count(),
                    executionTime: $executionTime,
                    metadata: [
                        'search_type' => 'posts',
                        'fuzzy_enabled' => true,
                        'threshold' => $threshold,
                        'filters' => $filters,
                    ]
                );
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Perform simple fuzzy search returning Post models directly
     */
    protected function performSimpleFuzzySearch(string $query, array $index, int $threshold, array $filters = []): Collection
    {
        $results = collect();
        $queryLower = mb_strtolower($query);

        foreach ($index as $item) {
            if (! $this->passesFilters($item, $filters)) {
                continue;
            }

            $titleScore = $this->calculateScore($queryLower, mb_strtolower($item['title']));
            $excerptScore = isset($item['excerpt'])
                ? $this->calculateScore($queryLower, mb_strtolower($item['excerpt'])) * 0.5
                : 0;

            $score = max($titleScore, $excerptScore);

            if ($score >= $threshold) {
                $post = Post::with(['user', 'category', 'tags'])->find($item['id']);
                if ($post) {
                    $results->push($post);
                }
            }
        }

        return $results;
    }

    /**
     * Search posts with fuzzy matching
     */
    public function searchPosts(string $query, array $options = []): Collection
    {
        try {
            $this->validateQuery($query);

            $threshold = $options['threshold'] ?? $this->threshold;
            $limit = $options['limit'] ?? 15;
            $filters = $options['filters'] ?? [];
            $exact = $options['exact'] ?? false;

            if ($exact) {
                return $this->exactSearch($query, $filters, $limit);
            }

            $cacheKey = $this->getCacheKey('posts', $query, $options);

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $startTime = microtime(true);

            $index = $this->indexService->getIndex('posts');
            $results = $this->performFuzzySearch($query, $index, $threshold, $filters);

            $executionTime = (microtime(true) - $startTime) * 1000;

            if ($executionTime > 1000) {
                throw new SearchTimeoutException('Search exceeded 1 second', $executionTime);
            }

            $results = $results->take($limit);

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $results, $this->cacheTtl);
            }

            return $results;
        } catch (SearchTimeoutException $e) {
            Log::warning('Fuzzy search timeout', [
                'query' => $query,
                'execution_time' => $e->getExecutionTime(),
            ]);

            return $this->fallbackSearch($query, $filters ?? [], $limit ?? 15);
        } catch (FuzzySearchException $e) {
            Log::error('Fuzzy search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackSearch($query, $filters ?? [], $limit ?? 15);
        }
    }

    /**
     * Search tags with fuzzy matching
     */
    public function searchTags(string $query, int $limit = 10): Collection
    {
        try {
            $this->validateQuery($query);

            $index = $this->indexService->getIndex('tags');
            $results = collect();

            foreach ($index as $item) {
                $score = $this->calculateScore($query, $item['name']);

                if ($score >= $this->threshold) {
                    $tag = Tag::find($item['id']);
                    if ($tag) {
                        $results->push(SearchResult::fromTag($tag, $score));
                    }
                }
            }

            return $results->sortByDesc('relevanceScore')->take($limit)->values();
        } catch (\Exception $e) {
            Log::error('Tag search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Search categories with fuzzy matching
     */
    public function searchCategories(string $query, int $limit = 10): Collection
    {
        try {
            $this->validateQuery($query);

            $index = $this->indexService->getIndex('categories');
            $results = collect();

            foreach ($index as $item) {
                $nameScore = $this->calculateScore($query, $item['name']);
                $descScore = $item['description']
                    ? $this->calculateScore($query, $item['description']) * 0.5
                    : 0;

                $score = max($nameScore, $descScore);

                if ($score >= $this->threshold) {
                    $category = Category::find($item['id']);
                    if ($category) {
                        $results->push(SearchResult::fromCategory($category, $score));
                    }
                }
            }

            return $results->sortByDesc('relevanceScore')->take($limit)->values();
        } catch (\Exception $e) {
            Log::error('Category search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Get search suggestions for autocomplete
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        $minLength = config('fuzzy-search.limits.suggestion_min_length', 3);

        if (strlen($query) < $minLength) {
            return [];
        }

        $cacheKey = "{$this->cachePrefix}:suggestions:".md5($query);

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $index = $this->indexService->getIndex('posts');
            $suggestions = collect();
            $queryLower = mb_strtolower($query);

            foreach ($index as $item) {
                $title = mb_strtolower($item['title']);

                if (str_contains($title, $queryLower)) {
                    $suggestions->push([
                        'text' => $item['title'],
                        'score' => 100,
                    ]);
                } else {
                    $score = $this->calculateScore($queryLower, $title);
                    if ($score >= $this->threshold) {
                        $suggestions->push([
                            'text' => $item['title'],
                            'score' => $score,
                        ]);
                    }
                }
            }

            $result = $suggestions
                ->sortByDesc('score')
                ->take($limit)
                ->pluck('text')
                ->unique()
                ->values()
                ->toArray();

            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $result, 3600); // 1 hour TTL
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Suggestions error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Search with multiple fields and weighted scoring
     */
    public function multiFieldSearch(string $query, array $fields, array $filters = []): Collection
    {
        try {
            $this->validateQuery($query);

            $index = $this->indexService->getIndex('posts');
            $results = collect();
            $queryLower = mb_strtolower($query);

            $weights = config('fuzzy-search.weights', [
                'title' => 3.0,
                'excerpt' => 2.0,
                'content' => 1.0,
                'tags' => 1.5,
                'category' => 1.5,
            ]);

            foreach ($index as $item) {
                if (! $this->passesFilters($item, $filters)) {
                    continue;
                }

                $combinedScore = 0;
                $maxScore = 0;

                foreach ($fields as $field) {
                    if (! isset($item[$field])) {
                        continue;
                    }

                    $fieldValue = is_array($item[$field])
                        ? implode(' ', $item[$field])
                        : $item[$field];

                    $fieldScore = $this->calculateScore($queryLower, mb_strtolower($fieldValue));
                    $weight = $weights[$field] ?? 1.0;
                    $weightedScore = $fieldScore * $weight;

                    $combinedScore += $weightedScore;
                    $maxScore += (100 * $weight);
                }

                // Normalize score to 0-100 range
                $normalizedScore = $maxScore > 0 ? ($combinedScore / $maxScore) * 100 : 0;

                if ($normalizedScore >= $this->threshold) {
                    $post = Post::with(['user', 'category', 'tags'])->find($item['id']);
                    if ($post) {
                        // Generate highlights for all searched fields
                        $highlightedItem = $this->highlightResultFields($item, $query, $fields);
                        $highlights = [];

                        foreach ($fields as $field) {
                            if (isset($highlightedItem["{$field}_highlighted"])) {
                                $highlights[$field] = $highlightedItem["{$field}_highlighted"];
                            }
                            if (isset($highlightedItem["{$field}_context"])) {
                                $highlights["{$field}_context"] = $highlightedItem["{$field}_context"];
                            }
                        }

                        $results->push(SearchResult::fromPost($post, $normalizedScore, $highlights));
                    }
                }
            }

            return $results->sortByDesc('relevanceScore')->values();
        } catch (\Exception $e) {
            Log::error('Multi-field search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Check if fuzzy search is enabled
     */
    public function isEnabled(string $context = 'posts'): bool
    {
        return config("fuzzy-search.enabled.{$context}", true);
    }

    /**
     * Perform fuzzy search on indexed data
     */
    protected function performFuzzySearch(string $query, array $index, int $threshold, array $filters = []): Collection
    {
        $results = collect();
        $queryLower = mb_strtolower($query);

        foreach ($index as $item) {
            // Apply filters first
            if (! $this->passesFilters($item, $filters)) {
                continue;
            }

            $titleScore = $this->calculateScore($queryLower, mb_strtolower($item['title']));
            $excerptScore = isset($item['excerpt'])
                ? $this->calculateScore($queryLower, mb_strtolower($item['excerpt'])) * 0.5
                : 0;

            $score = max($titleScore, $excerptScore);

            if ($score >= $threshold) {
                $post = Post::with(['user', 'category', 'tags'])->find($item['id']);
                if ($post) {
                    // Generate highlights for matched portions
                    $highlightedItem = $this->highlightResultFields($item, $query, ['title', 'excerpt']);
                    $highlights = [
                        'title' => $highlightedItem['title_highlighted'] ?? $item['title'],
                        'excerpt' => $highlightedItem['excerpt_highlighted'] ?? $item['excerpt'] ?? '',
                        'excerpt_context' => $highlightedItem['excerpt_context'] ?? $item['excerpt'] ?? '',
                    ];

                    $results->push(SearchResult::fromPost($post, $score, $highlights));
                }
            }
        }

        return $results->sortByDesc('relevanceScore')->values();
    }

    /**
     * Calculate fuzzy match score using Levenshtein distance
     */
    protected function calculateScore(string $query, string $text): float
    {
        $query = mb_strtolower(trim($query));
        $text = mb_strtolower(trim($text));

        // Exact match
        if ($query === $text) {
            return 100.0;
        }

        // Contains exact query
        if (str_contains($text, $query)) {
            return 95.0;
        }

        // Calculate Levenshtein distance
        $distance = levenshtein($query, $text);

        if ($distance <= $this->levenshteinDistance) {
            return max(0, 100 - ($distance * 20));
        }

        // Check for partial matches in words
        $queryWords = explode(' ', $query);
        $textWords = explode(' ', $text);

        $maxWordScore = 0;
        foreach ($queryWords as $queryWord) {
            foreach ($textWords as $textWord) {
                if (strlen($queryWord) < 3 || strlen($textWord) < 3) {
                    continue;
                }

                $wordDistance = levenshtein($queryWord, $textWord);
                $wordScore = max(0, 100 - ($wordDistance * 25));

                if ($wordScore > $maxWordScore) {
                    $maxWordScore = $wordScore;
                }
            }
        }

        return $maxWordScore;
    }

    /**
     * Find text portions to highlight
     */
    protected function findHighlights(string $query, string $text): array
    {
        $highlights = [];
        $queryLower = mb_strtolower($query);
        $textLower = mb_strtolower($text);

        if (str_contains($textLower, $queryLower)) {
            $highlights[] = $query;
        }

        return $highlights;
    }

    /**
     * Highlight matched terms in text
     *
     * @param  string  $text  The text to highlight
     * @param  string  $query  The search query
     * @return string Text with highlighted matches
     */
    public function highlightMatches(string $text, string $query): string
    {
        if (empty($text) || empty($query)) {
            return $text;
        }

        $highlightEnabled = config('fuzzy-search.highlighting.enabled', true);
        if (! $highlightEnabled) {
            return $text;
        }

        $tag = config('fuzzy-search.highlighting.tag', 'mark');
        $class = config('fuzzy-search.highlighting.class', 'search-highlight');

        $queryLower = mb_strtolower($query);
        $queryWords = array_filter(explode(' ', $queryLower));

        // Escape special regex characters in query words
        $queryWords = array_map(function ($word) {
            return preg_quote($word, '/');
        }, $queryWords);

        // Build regex pattern for all query words
        $pattern = '/\b('.implode('|', $queryWords).')\b/iu';

        // Replace matches with highlighted version
        $highlighted = preg_replace_callback($pattern, function ($matches) use ($tag, $class) {
            return "<{$tag} class=\"{$class}\">{$matches[0]}</{$tag}>";
        }, $text);

        return $highlighted ?? $text;
    }

    /**
     * Extract context around matched terms
     *
     * @param  string  $text  The full text
     * @param  string  $query  The search query
     * @param  int|null  $contextLength  Maximum context length (null uses config)
     * @return string Extracted context with highlights
     */
    public function extractContext(string $text, string $query, ?int $contextLength = null): string
    {
        if (empty($text) || empty($query)) {
            return $text;
        }

        $contextLength = $contextLength ?? config('fuzzy-search.highlighting.context_length', 200);
        $queryLower = mb_strtolower($query);
        $textLower = mb_strtolower($text);

        // Find the position of the first match
        $position = mb_strpos($textLower, $queryLower);

        if ($position === false) {
            // Try to find any word from the query
            $queryWords = array_filter(explode(' ', $queryLower));
            foreach ($queryWords as $word) {
                $position = mb_strpos($textLower, $word);
                if ($position !== false) {
                    break;
                }
            }
        }

        // If no match found, return truncated text
        if ($position === false) {
            return mb_substr($text, 0, $contextLength).(mb_strlen($text) > $contextLength ? '...' : '');
        }

        // Calculate start and end positions for context
        $halfContext = (int) ($contextLength / 2);
        $start = max(0, $position - $halfContext);
        $end = min(mb_strlen($text), $position + mb_strlen($query) + $halfContext);

        // Adjust start to word boundary
        if ($start > 0) {
            $spacePos = mb_strpos($text, ' ', $start);
            if ($spacePos !== false && $spacePos < $position) {
                $start = $spacePos + 1;
            }
        }

        // Adjust end to word boundary
        if ($end < mb_strlen($text)) {
            $spacePos = mb_strrpos(mb_substr($text, 0, $end), ' ');
            if ($spacePos !== false) {
                $end = $spacePos;
            }
        }

        // Extract context
        $context = mb_substr($text, $start, $end - $start);

        // Add ellipsis if needed
        $prefix = $start > 0 ? '...' : '';
        $suffix = $end < mb_strlen($text) ? '...' : '';

        return $prefix.$context.$suffix;
    }

    /**
     * Highlight matches in multiple fields of a search result
     *
     * @param  array  $item  The search result item
     * @param  string  $query  The search query
     * @param  array  $fields  Fields to highlight
     * @return array Item with highlighted fields
     */
    public function highlightResultFields(array $item, string $query, array $fields = ['title', 'excerpt']): array
    {
        $highlighted = $item;

        foreach ($fields as $field) {
            if (isset($item[$field]) && is_string($item[$field])) {
                $highlighted["{$field}_highlighted"] = $this->highlightMatches($item[$field], $query);

                // For excerpt, also extract context
                if ($field === 'excerpt' || $field === 'content') {
                    $highlighted["{$field}_context"] = $this->extractContext($item[$field], $query);
                    $highlighted["{$field}_highlighted"] = $this->highlightMatches(
                        $highlighted["{$field}_context"],
                        $query
                    );
                }
            }
        }

        return $highlighted;
    }

    /**
     * Check if item passes filters
     */
    protected function passesFilters(array $item, array $filters): bool
    {
        if (isset($filters['category']) && $item['category'] !== $filters['category']) {
            return false;
        }

        if (isset($filters['author']) && $item['author'] !== $filters['author']) {
            return false;
        }

        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $publishedAt = $item['published_at'] ?? null;
            if ($publishedAt) {
                $date = strtotime($publishedAt);
                if (isset($filters['date_from']) && $date < strtotime($filters['date_from'])) {
                    return false;
                }
                if (isset($filters['date_to']) && $date > strtotime($filters['date_to'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate search query
     */
    protected function validateQuery(string $query): void
    {
        $maxLength = config('fuzzy-search.limits.max_query_length', 200);

        if (empty(trim($query))) {
            throw new InvalidQueryException('Search query cannot be empty');
        }

        if (strlen($query) > $maxLength) {
            throw new InvalidQueryException("Search query cannot exceed {$maxLength} characters");
        }

        if (! preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $query)) {
            throw new InvalidQueryException('Search query contains invalid characters');
        }
    }

    /**
     * Fallback to basic database search
     */
    protected function fallbackSearch(string $query, array $filters, int $limit): Collection
    {
        $queryBuilder = Post::published()
            ->with(['user', 'category', 'tags'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            });

        if (isset($filters['category'])) {
            $queryBuilder->whereHas('category', function ($q) use ($filters) {
                $q->where('name', $filters['category']);
            });
        }

        if (isset($filters['author'])) {
            $queryBuilder->whereHas('user', function ($q) use ($filters) {
                $q->where('name', $filters['author']);
            });
        }

        if (isset($filters['date_from'])) {
            $queryBuilder->where('published_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $queryBuilder->where('published_at', '<=', $filters['date_to']);
        }

        $posts = $queryBuilder->limit($limit)->get();

        return $posts->map(function ($post) {
            return SearchResult::fromPost($post, 50.0, []);
        });
    }

    /**
     * Exact search without fuzzy matching
     */
    protected function exactSearch(string $query, array $filters, int $limit): Collection
    {
        return $this->fallbackSearch($query, $filters, $limit);
    }

    /**
     * Get cache key for search results
     */
    protected function getCacheKey(string $type, string $query, array $options): string
    {
        $optionsHash = md5(json_encode($options));

        return "{$this->cachePrefix}:results:{$type}:".md5($query).":{$optionsHash}";
    }
}

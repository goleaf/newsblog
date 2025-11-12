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

    protected bool $phoneticEnabled;

    protected float $phoneticWeight;

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
        $this->phoneticEnabled = config('fuzzy-search.phonetic_enabled', false);
        $this->phoneticWeight = config('fuzzy-search.phonetic_weight', 0.3);
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

        try {
            $this->validateQuery($query);

            $threshold = $threshold ?? $this->threshold;
            $limit = $limit ?? 15;

            // Check cache for identical queries (Requirement 10.1)
            $options = [
                'threshold' => $threshold,
                'limit' => $limit,
                'filters' => $filters,
            ];
            $cacheKey = $this->getCacheKey('posts', $query, $options);

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                Log::debug('Search result cache hit', ['query' => $query]);
                $this->analyticsService->logCacheHit('posts', $query);

                return Cache::get($cacheKey);
            }

            // Log cache miss
            if ($this->cacheEnabled) {
                $this->analyticsService->logCacheMiss('posts', $query);
            }

            $startTime = microtime(true);

            $index = $this->indexService->getIndex('posts');
            $results = $this->performSimpleFuzzySearch($query, $index, $threshold, $filters);

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Log slow queries (>1 second)
            if ($executionTime > 1000) {
                $this->analyticsService->logSlowQuery($query, $executionTime, [
                    'search_type' => 'posts',
                    'result_count' => $results->count(),
                ]);
            }

            $results = $results->take($limit);

            // Cache results for 10 minutes (Requirement 10.3)
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $results, $this->cacheTtl);
                Log::debug('Search results cached', [
                    'query' => $query,
                    'ttl' => $this->cacheTtl,
                    'result_count' => $results->count(),
                ]);
            }

            if ($logSearch) {
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

        // Pre-filter candidates by status and date before fuzzy matching
        $candidates = $this->preFilterCandidates($index, $filters);

        // Limit candidate set to 1000 items for performance
        $candidates = array_slice($candidates, 0, 1000);

        foreach ($candidates as $item) {
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

            // Check cache for identical queries (Requirement 10.1)
            $cacheKey = $this->getCacheKey('posts', $query, $options);

            if ($this->cacheEnabled && Cache::has($cacheKey)) {
                Log::debug('Search result cache hit', ['query' => $query]);
                $this->analyticsService->logCacheHit('posts', $query);

                return Cache::get($cacheKey);
            }

            // Log cache miss
            if ($this->cacheEnabled) {
                $this->analyticsService->logCacheMiss('posts', $query);
            }

            $startTime = microtime(true);

            $index = $this->indexService->getIndex('posts');
            $results = $this->performFuzzySearch($query, $index, $threshold, $filters);

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Log slow queries (>1 second)
            if ($executionTime > 1000) {
                $this->analyticsService->logSlowQuery($query, $executionTime, [
                    'search_type' => 'posts',
                    'result_count' => $results->count(),
                ]);
                throw new SearchTimeoutException('Search exceeded 1 second', $executionTime);
            }

            $results = $results->take($limit);

            // Cache results for 10 minutes (Requirement 10.3)
            if ($this->cacheEnabled) {
                Cache::put($cacheKey, $results, $this->cacheTtl);
                Log::debug('Search results cached', [
                    'query' => $query,
                    'ttl' => $this->cacheTtl,
                    'result_count' => $results->count(),
                ]);
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
     * Uses query prefix as cache key with 1-hour TTL (Requirements 10.1, 10.2)
     */
    public function getSuggestions(string $query, int $limit = 5): array
    {
        $minLength = config('fuzzy-search.limits.suggestion_min_length', 3);

        if (strlen($query) < $minLength) {
            return [];
        }

        // Use query prefix as cache key (Requirement 10.2)
        $cacheKey = $this->getSuggestionCacheKey($query);

        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            Log::debug('Suggestion cache hit', ['query' => $query]);
            $this->analyticsService->logCacheHit('suggestions', $query);

            return Cache::get($cacheKey);
        }

        // Log cache miss
        if ($this->cacheEnabled) {
            $this->analyticsService->logCacheMiss('suggestions', $query);
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

            // Cache suggestions with 1-hour TTL (Requirement 10.2)
            if ($this->cacheEnabled) {
                $suggestionTtl = config('fuzzy-search.cache.suggestion_ttl', 3600);
                Cache::put($cacheKey, $result, $suggestionTtl);
                Log::debug('Suggestions cached', [
                    'query' => $query,
                    'ttl' => $suggestionTtl,
                    'count' => count($result),
                ]);
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

                        // Check if any field has a phonetic match
                        $isPhonetic = false;
                        foreach ($fields as $field) {
                            if (isset($item[$field])) {
                                $fieldValue = is_array($item[$field])
                                    ? implode(' ', $item[$field])
                                    : $item[$field];
                                if ($this->isPhoneticMatch($queryLower, mb_strtolower($fieldValue))) {
                                    $isPhonetic = true;
                                    break;
                                }
                            }
                        }

                        $highlights['is_phonetic'] = $isPhonetic;

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
     * Check if phonetic matching is enabled
     */
    public function isPhoneticEnabled(): bool
    {
        return $this->phoneticEnabled;
    }

    /**
     * Check if a match is likely phonetic
     * Returns true if the query and text sound similar but are spelled differently
     */
    public function isPhoneticMatch(string $query, string $text): bool
    {
        if (! $this->phoneticEnabled) {
            return false;
        }

        $query = mb_strtolower(trim($query));
        $text = mb_strtolower(trim($text));

        // Not phonetic if exact match or contains
        if ($query === $text || str_contains($text, $query)) {
            return false;
        }

        // Check if phonetic codes match
        $queryWords = explode(' ', $query);
        $textWords = explode(' ', $text);

        foreach ($queryWords as $queryWord) {
            if (strlen($queryWord) < 3) {
                continue;
            }

            $queryPhonetic = metaphone($queryWord);

            foreach ($textWords as $textWord) {
                if (strlen($textWord) < 3) {
                    continue;
                }

                $textPhonetic = metaphone($textWord);

                // If phonetic codes match, it's a phonetic match
                if ($queryPhonetic === $textPhonetic) {
                    return true;
                }
            }
        }

        return false;
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

                    // Check if this is a phonetic match
                    $isPhonetic = $this->isPhoneticMatch($queryLower, mb_strtolower($item['title']));
                    if (! $isPhonetic && isset($item['excerpt'])) {
                        $isPhonetic = $this->isPhoneticMatch($queryLower, mb_strtolower($item['excerpt']));
                    }

                    $highlights = [
                        'title' => $highlightedItem['title_highlighted'] ?? $item['title'],
                        'excerpt' => $highlightedItem['excerpt_highlighted'] ?? $item['excerpt'] ?? '',
                        'excerpt_context' => $highlightedItem['excerpt_context'] ?? $item['excerpt'] ?? '',
                        'is_phonetic' => $isPhonetic,
                    ];

                    $results->push(SearchResult::fromPost($post, $score, $highlights));
                }
            }
        }

        return $results->sortByDesc('relevanceScore')->values();
    }

    /**
     * Public method to calculate fuzzy match score for external use (e.g., spam detection).
     */
    public function calculateFuzzyScore(string $query, string $text): float
    {
        return $this->calculateScore($query, $text);
    }

    /**
     * Calculate fuzzy match score using Levenshtein distance and phonetic matching
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

        // Apply phonetic matching if enabled and no good fuzzy match found
        if ($this->phoneticEnabled && $maxWordScore < $this->threshold) {
            $phoneticScore = $this->calculatePhoneticScore($query, $text);
            // Phonetic matches are weighted lower than exact/fuzzy matches
            $phoneticScore = $phoneticScore * $this->phoneticWeight;
            $maxWordScore = max($maxWordScore, $phoneticScore);
        }

        return $maxWordScore;
    }

    /**
     * Calculate phonetic match score using Metaphone algorithm
     */
    protected function calculatePhoneticScore(string $query, string $text): float
    {
        $queryWords = explode(' ', $query);
        $textWords = explode(' ', $text);

        $maxPhoneticScore = 0;

        foreach ($queryWords as $queryWord) {
            // Skip very short words for phonetic matching
            if (strlen($queryWord) < 3) {
                continue;
            }

            $queryPhonetic = metaphone($queryWord);

            foreach ($textWords as $textWord) {
                if (strlen($textWord) < 3) {
                    continue;
                }

                $textPhonetic = metaphone($textWord);

                // Exact phonetic match
                if ($queryPhonetic === $textPhonetic) {
                    $maxPhoneticScore = max($maxPhoneticScore, 100.0);

                    continue;
                }

                // Calculate Levenshtein distance on phonetic codes
                if (! empty($queryPhonetic) && ! empty($textPhonetic)) {
                    $phoneticDistance = levenshtein($queryPhonetic, $textPhonetic);
                    $phoneticScore = max(0, 100 - ($phoneticDistance * 30));

                    if ($phoneticScore > $maxPhoneticScore) {
                        $maxPhoneticScore = $phoneticScore;
                    }
                }
            }
        }

        return $maxPhoneticScore;
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
            return e($text);
        }

        $highlightEnabled = config('fuzzy-search.highlighting.enabled', true);
        if (! $highlightEnabled) {
            return e($text);
        }

        $tag = config('fuzzy-search.highlighting.tag', 'mark');
        $class = config('fuzzy-search.highlighting.class', 'search-highlight');

        // First escape the text to prevent XSS
        $escapedText = e($text);

        $queryLower = mb_strtolower($query);
        $queryWords = array_filter(explode(' ', $queryLower));

        // Escape special regex characters in query words
        $queryWords = array_map(function ($word) {
            return preg_quote($word, '/');
        }, $queryWords);

        // Build regex pattern for all query words (match on escaped text)
        // Note: After escaping, HTML entities like &lt; appear, so we need to match the escaped version
        $pattern = '/\b('.implode('|', $queryWords).')\b/iu';

        // Replace matches with highlighted version
        // The matches are already escaped since we're working with escaped text
        $highlighted = preg_replace_callback($pattern, function ($matches) use ($tag, $class) {
            return "<{$tag} class=\"{$class}\">{$matches[0]}</{$tag}>";
        }, $escapedText);

        return $highlighted ?? $escapedText;
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
            return e($text);
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

        // If no match found, return truncated text (HTML escaped)
        if ($position === false) {
            $truncated = mb_substr($text, 0, $contextLength).(mb_strlen($text) > $contextLength ? '...' : '');

            return e($truncated);
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

        // HTML escape the context before returning
        return e($prefix.$context.$suffix);
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
     * Pre-filter candidates by status and date before fuzzy matching
     * This improves performance by reducing the candidate set
     */
    protected function preFilterCandidates(array $index, array $filters = []): array
    {
        $candidates = [];

        foreach ($index as $item) {
            // Filter by status - only include published posts
            if (isset($item['status']) && $item['status'] !== 'published') {
                continue;
            }

            // Filter by published_at - only include posts that are published
            if (isset($item['published_at'])) {
                $publishedAt = strtotime($item['published_at']);
                if ($publishedAt === false || $publishedAt > time()) {
                    continue;
                }
            }

            // Apply additional filters
            if (! $this->passesFilters($item, $filters)) {
                continue;
            }

            $candidates[] = $item;
        }

        return $candidates;
    }

    /**
     * Check if item passes filters
     */
    protected function passesFilters(array $item, array $filters): bool
    {
        if (isset($filters['category']) && $item['category'] !== $filters['category']) {
            return false;
        }

        if (isset($filters['author']) && ($item['author_id'] ?? null) != $filters['author']) {
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
            $queryBuilder->where('user_id', $filters['author']);
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
     * Get cache key for search results (Requirement 10.2)
     * Generates unique key from query and filters
     */
    protected function getCacheKey(string $type, string $query, array $options): string
    {
        $optionsHash = md5(json_encode($options));

        return "{$this->cachePrefix}:results:{$type}:".md5($query).":{$optionsHash}";
    }

    /**
     * Get cache key for suggestions (Requirement 10.2)
     * Uses query prefix as cache key
     */
    protected function getSuggestionCacheKey(string $query): string
    {
        return "{$this->cachePrefix}:suggestions:".md5($query);
    }

    /**
     * Clear all search result caches
     * This is useful when content is updated and cached results should be invalidated
     */
    public function clearResultCache(): void
    {
        // Note: Without cache tags, we can't efficiently clear all result caches
        // Individual caches will expire via TTL (10 minutes)
        // For immediate invalidation, the SearchIndexService handles index cache clearing
        Log::info('Search result caches will expire via TTL', [
            'ttl' => $this->cacheTtl,
        ]);
    }

    /**
     * Clear suggestion cache for a specific query or all suggestions
     */
    public function clearSuggestionCache(?string $query = null): void
    {
        if ($query !== null) {
            $cacheKey = $this->getSuggestionCacheKey($query);
            Cache::forget($cacheKey);
            Log::debug('Suggestion cache cleared', ['query' => $query]);
        } else {
            // Without cache tags, we can't efficiently clear all suggestion caches
            // Individual caches will expire via TTL (1 hour)
            Log::info('Suggestion caches will expire via TTL', [
                'ttl' => config('fuzzy-search.cache.suggestion_ttl', 3600),
            ]);
        }
    }
}

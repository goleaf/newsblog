<?php

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\SearchResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SearchRequest;
use App\Models\Post;
use App\Services\FuzzySearchService;
use App\Services\SearchAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Search
 *
 * API endpoints for searching posts, tags, and categories.
 */
class SearchController extends Controller
{
    public function __construct(
        protected FuzzySearchService $fuzzySearchService,
        protected SearchAnalyticsService $analyticsService
    ) {}

    /**
     * Search Posts
     *
     * Search for posts using fuzzy matching algorithm.
     *
     * @queryParam q string required The search query. Example: laravel
     * @queryParam threshold integer Minimum relevance score (0-100). Example: 60
     * @queryParam limit integer Maximum number of results (1-100). Example: 15
     * @queryParam category string Filter by category slug. Example: technology
     * @queryParam author string Filter by author name. Example: John Doe
     * @queryParam date_from date Filter posts published after this date. Example: 2024-01-01
     * @queryParam date_to date Filter posts published before this date. Example: 2024-12-31
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "type": "post",
     *       "title": "Example Post",
     *       "excerpt": "This is an example...",
     *       "url": "/post/example-post",
     *       "relevance_score": 85.5,
     *       "highlights": {
     *         "title": "Example <mark>Post</mark>",
     *         "excerpt": "This is an example..."
     *       },
     *       "metadata": {
     *         "slug": "example-post",
     *         "published_at": "2024-01-01T00:00:00.000000Z",
     *         "author": "John Doe",
     *         "category": "Technology"
     *       }
     *     }
     *   ],
     *   "meta": {
     *     "query": "laravel",
     *     "count": 1,
     *     "fuzzy_enabled": true
     *   }
     * }
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $query = $request->validated()['q'];
        $startTime = microtime(true);

        // Build filters from request
        $filters = [];
        if ($request->filled('category')) {
            $filters['category'] = $request->category;
        }
        if ($request->filled('author')) {
            $filters['author'] = $request->author;
        }
        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->date_from;
        }
        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->date_to;
        }

        $threshold = $request->validated()['threshold'] ?? null;
        $limit = $request->validated()['limit'] ?? 15;

        try {
            // Optional engine override: use Scout if requested
            if ($request->string('engine')->lower() === 'scout') {
                $builder = Post::search($query)
                    ->query(function ($q) use ($filters, $request) {
                        if (! empty($filters['category'])) {
                            $q->whereHas('category', function ($sub) use ($filters) {
                                $sub->where('slug', $filters['category']);
                            });
                        }
                        if (! empty($filters['author'])) {
                            $q->whereHas('user', function ($sub) use ($filters) {
                                $sub->where('name', 'like', '%'.$filters['author'].'%');
                            });
                        }
                        if (! empty($filters['date_from'])) {
                            $q->where('published_at', '>=', $filters['date_from']);
                        }
                        if (! empty($filters['date_to'])) {
                            $q->where('published_at', '<=', $filters['date_to']);
                        }

                        // Sorting within the Eloquent query stage
                        $sort = strtolower((string) $request->input('sort', 'relevance'));
                        if ($sort === 'date') {
                            $q->orderByDesc('published_at');
                        } elseif ($sort === 'popularity') {
                            $q->orderByDesc('view_count');
                        }
                    });

                // Pagination support
                $perPage = (int) max(1, min((int) $request->input('per_page', $limit), 50));
                $page = (int) max(1, (int) $request->input('page', 1));

                $paginator = $builder->paginate($perPage, 'page', $page);

                $items = collect($paginator->items());
                $results = $items->values()->map(function ($post, $idx) use ($page, $perPage) {
                    $pos = (($page - 1) * $perPage) + $idx;
                    $score = max(1, 100 - $pos);

                    return SearchResult::fromPost($post, (float) $score);
                });

                return response()->json([
                    'success' => true,
                    'data' => $results->map(fn ($r) => $r->toArray()),
                    'meta' => [
                        'query' => $query,
                        'count' => $paginator->total(),
                        'fuzzy_enabled' => false,
                        'pagination' => [
                            'current_page' => $paginator->currentPage(),
                            'per_page' => $paginator->perPage(),
                            'last_page' => $paginator->lastPage(),
                            'total' => $paginator->total(),
                        ],
                    ],
                ]);
            } else {
                // Use fuzzy search service (default)
                $results = $this->fuzzySearchService->searchPosts($query, [
                    'threshold' => $threshold,
                    'limit' => $limit,
                    'filters' => $filters,
                ]);
            }

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Async query logging
            if (config('fuzzy-search.analytics.log_queries', true)) {
                $resultCount = $results->count();
                $fuzzyEnabled = $request->string('engine')->lower() !== 'scout' && $this->fuzzySearchService->isEnabled('posts');
                $analyticsService = $this->analyticsService;

                dispatch(function () use ($query, $resultCount, $executionTime, $filters, $fuzzyEnabled, $analyticsService) {
                    $analyticsService->logQuery(
                        query: $query,
                        resultCount: $resultCount,
                        executionTime: $executionTime,
                        metadata: [
                            'search_type' => 'posts',
                            'fuzzy_enabled' => $fuzzyEnabled,
                            'filters' => $filters,
                        ]
                    );
                })->afterResponse();
            }

            return response()->json([
                'success' => true,
                'data' => $results->map(fn ($result) => $result->toArray()),
                'meta' => [
                    'query' => $query,
                    'count' => $results->count(),
                    'fuzzy_enabled' => $this->fuzzySearchService->isEnabled('posts'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed. Please try again.',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get Search Suggestions
     *
     * Get autocomplete suggestions for a search query.
     *
     * @queryParam q string required The search query (minimum 3 characters). Example: lara
     * @queryParam limit integer Maximum number of suggestions (1-10). Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     "Laravel 11 Release",
     *     "Laravel Best Practices",
     *     "Laravel Performance Tips"
     *   ],
     *   "meta": {
     *     "query": "lara",
     *     "count": 3
     *   }
     * }
     */
    public function suggestions(SearchRequest $request): JsonResponse
    {
        $query = $request->validated()['q'];
        $limit = $request->validated()['limit'] ?? 5;

        try {
            $suggestions = $this->fuzzySearchService->getSuggestions($query, $limit);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'meta' => [
                    'query' => $query,
                    'count' => count($suggestions),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Suggestions error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions.',
                'data' => [],
            ], 500);
        }
    }
}

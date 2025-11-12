<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\AdvancedSearchService;
use App\Services\FuzzySearchService;
use App\Services\SearchAnalyticsService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService,
        protected FuzzySearchService $fuzzySearchService,
        protected SearchAnalyticsService $analyticsService,
        protected AdvancedSearchService $advancedSearchService
    ) {}

    /**
     * Display search results.
     *
     * Implements Requirements 8.1-8.5 and 39.1-39.5:
     * - Multi-field search (title, content, excerpt)
     * - Relevance-based sorting (exact title matches first)
     * - Search result highlighting
     * - Pagination support (15 results per page)
     * - Advanced filters (date range, author, category with subcategories, tags)
     */
    public function index(SearchRequest $request): View|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $query = $validated['q'] ?? '';

        // Build filters from validated request
        $filters = array_filter([
            'category' => $validated['category'] ?? null,
            'author' => $validated['author'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'tags' => $validated['tags'] ?? null,
        ]);

        // Get filter options for the view
        $authors = $this->advancedSearchService->getAuthorsWithPosts();
        $categories = $this->advancedSearchService->getCategories();
        $tags = $this->advancedSearchService->getTagsWithPosts();
        $activeFilterCount = $this->advancedSearchService->countActiveFilters($filters);

        if (empty($query) && empty($filters)) {
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                15,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('search', [
                'posts' => $emptyPaginator,
                'query' => '',
                'filters' => $filters,
                'authors' => $authors,
                'categories' => $categories,
                'tags' => $tags,
                'activeFilterCount' => $activeFilterCount,
            ]);
        }

        $startTime = microtime(true);

        $fuzzyEnabled = $this->fuzzySearchService->isEnabled('posts');
        $avgRelevanceScore = null;
        $spellingSuggestion = null;

        // Use advanced search service for filtering
        $posts = $this->advancedSearchService->search($query, $filters, 15);

        // Async query logging
        $executionTime = (microtime(true) - $startTime) * 1000;
        if (config('fuzzy-search.analytics.log_queries', true)) {
            $resultCount = $posts->count();
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

        // Return JSON for AJAX requests (Requirements 27.1-27.5)
        if ($request->wantsJson() || $request->ajax()) {
            $html = '';
            foreach ($posts as $post) {
                $html .= view('partials.search-post-card', [
                    'post' => $post,
                    'fuzzyEnabled' => $fuzzyEnabled,
                ])->render();
            }

            return response()->json([
                'html' => $html,
                'currentPage' => $posts->currentPage(),
                'lastPage' => $posts->lastPage(),
                'hasMorePages' => $posts->hasMorePages(),
            ]);
        }

        return view('search', compact(
            'posts',
            'query',
            'filters',
            'authors',
            'categories',
            'tags',
            'activeFilterCount',
            'fuzzyEnabled',
            'avgRelevanceScore',
            'spellingSuggestion'
        ));
    }

    /**
     * Get search suggestions for autocomplete.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->getSuggestions($query, 5);

        return response()->json($suggestions);
    }
}

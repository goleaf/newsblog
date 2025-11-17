<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\AdvancedSearchService;
use App\Services\FilterService;
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
        protected AdvancedSearchService $advancedSearchService,
        protected FilterService $filterService
    ) {}

    /**
     * Search using Laravel Scout (Meilisearch).
     * This method provides full-text search with advanced filtering.
     *
     * Requirements: 6.1, 6.2, 6.3, 6.5
     */
    public function scoutSearch(SearchRequest $request): View|\Illuminate\Http\JsonResponse
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
            'sort' => $request->input('sort', 'newest'),
        ]);

        // Get filter options for the view
        $authors = $this->advancedSearchService->getAuthorsWithPosts();
        $categories = $this->advancedSearchService->getCategories();
        $tags = $this->advancedSearchService->getTagsWithPosts();
        $activeFilterCount = $this->filterService->countActiveFilters($filters);
        $sortOptions = $this->filterService->getSortingOptions();

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

        // Use Scout for search if driver is meilisearch
        $useScout = config('scout.driver') === 'meilisearch';

        if ($useScout && ! empty($query)) {
            $posts = $this->searchWithScout($query, $filters, 15);
        } else {
            // Fallback to existing search implementation
            $posts = $this->advancedSearchService->search($query, $filters, 15);
        }

        // Enhance results with highlighting
        if (! empty($query) && $posts->total() > 0) {
            $posts->getCollection()->transform(function ($post) use ($query) {
                $post->highlighted_title = $this->highlightMatches($post->title, $query);

                if (! empty($post->excerpt)) {
                    $post->highlighted_excerpt = $this->highlightMatches($post->excerpt, $query);
                } elseif (! empty($post->content)) {
                    $excerpt = \Illuminate\Support\Str::limit(strip_tags($post->content), 200);
                    $post->highlighted_excerpt = $this->highlightMatches($excerpt, $query);
                }

                return $post;
            });
        }

        // Log query for analytics (Requirement 6.5)
        $executionTime = (microtime(true) - $startTime) * 1000;
        $searchLogId = null;

        if (! empty($query)) {
            $resultCount = $posts->total();

            $searchLog = \App\Models\SearchLog::create([
                'query' => $query,
                'result_count' => $resultCount,
                'execution_time' => $executionTime,
                'search_type' => 'posts',
                'fuzzy_enabled' => false,
                'threshold' => null,
                'filters' => $filters,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);

            $searchLogId = $searchLog->id;
        }

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->ajax()) {
            $html = '';
            foreach ($posts as $post) {
                $html .= view('partials.search-post-card', [
                    'post' => $post,
                    'fuzzyEnabled' => false,
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
            'sortOptions',
            'searchLogId'
        ));
    }

    /**
     * Search using Laravel Scout with filters.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function searchWithScout(string $query, array $filters = [], int $perPage = 15)
    {
        $searchBuilder = \App\Models\Post::search($query);

        // Apply filters using Meilisearch filter syntax
        $filterClauses = [];

        if (isset($filters['category'])) {
            if (is_numeric($filters['category'])) {
                $category = \App\Models\Category::find($filters['category']);
                if ($category) {
                    $filterClauses[] = "category = '{$category->name}'";
                }
            } else {
                $category = \App\Models\Category::where('slug', $filters['category'])->first();
                if ($category) {
                    $filterClauses[] = "category = '{$category->name}'";
                }
            }
        }

        if (isset($filters['author'])) {
            $author = \App\Models\User::find($filters['author']);
            if ($author) {
                $filterClauses[] = "author = '{$author->name}'";
            }
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $tagFilters = [];
            foreach ($filters['tags'] as $tagId) {
                $tag = \App\Models\Tag::find($tagId);
                if ($tag) {
                    $tagFilters[] = "tags = '{$tag->name}'";
                }
            }
            if (! empty($tagFilters)) {
                $filterClauses[] = '('.implode(' OR ', $tagFilters).')';
            }
        }

        if (isset($filters['date_from'])) {
            $timestamp = strtotime($filters['date_from']);
            $filterClauses[] = "published_at >= {$timestamp}";
        }

        if (isset($filters['date_to'])) {
            $timestamp = strtotime($filters['date_to']);
            $filterClauses[] = "published_at <= {$timestamp}";
        }

        // Apply all filters
        if (! empty($filterClauses)) {
            $searchBuilder->where(implode(' AND ', $filterClauses));
        }

        // Apply sorting (Requirement 6.2)
        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $searchBuilder->orderBy('published_at', 'asc');
                break;
            case 'popular':
                $searchBuilder->orderBy('view_count', 'desc');
                break;
            case 'reading_time_asc':
                $searchBuilder->orderBy('reading_time', 'asc');
                break;
            case 'reading_time_desc':
                $searchBuilder->orderBy('reading_time', 'desc');
                break;
            case 'newest':
            default:
                $searchBuilder->orderBy('published_at', 'desc');
                break;
        }

        // Paginate results
        return $searchBuilder->paginate($perPage);
    }

    /**
     * Highlight matching terms in text.
     */
    protected function highlightMatches(string $text, string $query): string
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
                '<mark class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">$1</mark>',
                $escapedText
            );
        }

        return $escapedText;
    }

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
            'sort' => $request->input('sort', 'newest'),
        ]);

        // Get filter options for the view
        $authors = $this->advancedSearchService->getAuthorsWithPosts();
        $categories = $this->advancedSearchService->getCategories();
        $tags = $this->advancedSearchService->getTagsWithPosts();
        $activeFilterCount = $this->filterService->countActiveFilters($filters);
        $sortOptions = $this->filterService->getSortingOptions();

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

        // Use advanced search service for filtering (covers tags, category tree)
        $posts = $this->advancedSearchService->search($query, $filters, 15);

        // Ensure sort order in the rendered collection matches requested sort
        // This is a safety net so the HTML reflects the expected order
        $requestedSort = $filters['sort'] ?? 'newest';
        $posts->setCollection(match ($requestedSort) {
            'popular' => $posts->getCollection()->sortByDesc('view_count')->values(),
            'oldest' => $posts->getCollection()->sortBy('published_at')->values(),
            default => $posts->getCollection()->sortByDesc('published_at')->values(),
        });

        // Get spelling suggestion if no results found (Requirements 2.4, 2.5)
        if (! empty($query) && $posts->total() === 0 && $fuzzyEnabled) {
            $spellingSuggestion = $this->fuzzySearchService->getSpellingSuggestion($query);
        }

        // Enhance results with fuzzy search highlighting and context extraction
        if (! empty($query) && $fuzzyEnabled && $posts->total() > 0) {
            $posts->getCollection()->transform(function ($post) use ($query) {
                // Use FuzzySearchService for better highlighting
                $post->highlighted_title = $this->fuzzySearchService->highlightMatches($post->title, $query);

                // Extract context snippet with highlighting
                if (! empty($post->excerpt)) {
                    $post->excerpt_context = $this->fuzzySearchService->extractContext($post->excerpt, $query, 200);
                    $post->highlighted_excerpt = $this->fuzzySearchService->highlightMatches($post->excerpt_context, $query);
                } elseif (! empty($post->content)) {
                    $post->excerpt_context = $this->fuzzySearchService->extractContext(strip_tags($post->content), $query, 200);
                    $post->highlighted_excerpt = $this->fuzzySearchService->highlightMatches($post->excerpt_context, $query);
                }

                return $post;
            });
        }

        // Log query and get search_log_id for click tracking (Requirement 16.2)
        $executionTime = (microtime(true) - $startTime) * 1000;
        $searchLogId = null;

        if (config('fuzzy-search.analytics.log_queries', true) && ! empty($query)) {
            $resultCount = $posts->total();

            // Create search log synchronously to get ID for click tracking
            $searchLog = \App\Models\SearchLog::create([
                'query' => $query,
                'result_count' => $resultCount,
                'execution_time' => $executionTime,
                'search_type' => 'posts',
                'fuzzy_enabled' => $fuzzyEnabled,
                'threshold' => null,
                'filters' => $filters,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'user_id' => auth()->id(),
            ]);

            $searchLogId = $searchLog->id;
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
            'sortOptions',
            'fuzzyEnabled',
            'avgRelevanceScore',
            'spellingSuggestion',
            'searchLogId'
        ));
    }

    /**
     * Get search suggestions for autocomplete.
     * Implements Requirements 2.1, 2.2 (autocomplete with debounced search)
     * Implements Requirement 8: Autocomplete method with debouncing
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q', '');
        $minLength = config('fuzzy-search.limits.suggestion_min_length', 3);

        if (strlen($query) < $minLength) {
            return response()->json([]);
        }

        // Use SearchService autocomplete method which supports FTS5
        $suggestions = $this->searchService->autocomplete($query, 5);

        return response()->json($suggestions->toArray());
    }
}

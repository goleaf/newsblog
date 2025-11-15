<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\FuzzySearchService;
use App\Services\PostService;
use App\Services\RelatedPostsService;
use App\Services\SearchAnalyticsService;
use App\Services\SearchService;
use App\Services\SeriesNavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function __construct(
        protected FuzzySearchService $fuzzySearchService,
        protected SearchAnalyticsService $analyticsService,
        protected PostService $postService,
        protected RelatedPostsService $relatedPostsService,
        protected SearchService $searchService,
        protected SeriesNavigationService $seriesNavigationService,
        protected PostViewController $postViewController,
        protected \App\Services\CacheService $cacheService
    ) {}

    public function show($slug, Request $request)
    {
        // Cache post for 30 minutes (Requirement 20.1, 6.8)
        $post = Cache::remember("post.{$slug}", 1800, function () use ($slug) {
            return Post::where('slug', $slug)
                ->published()
                ->with([
                    'user:id,name,bio,avatar_url',
                    'category:id,name,slug,icon,color_code',
                    'categories:id,name,slug',
                    'tags:id,name,slug',
                    'reactions' => function ($query) {
                        $query->select('post_id', 'type', \DB::raw('count(*) as count'))
                            ->groupBy('post_id', 'type');
                    },
                    'bookmarks' => function ($query) {
                        $query->select('post_id', \DB::raw('count(*) as count'))
                            ->groupBy('post_id');
                    },
                ])
                ->firstOrFail();
        });

        // Load top-level comments with nested replies up to 3 levels (Requirements 23.1-23.5)
        // Level 1 (depth 0) -> Level 2 (depth 1) -> Level 3 (depth 2)
        // Load parent chain for depth calculation
        // Lazy load comments to improve initial page load (Requirement 6.8)
        $post->load(['comments' => function ($query) {
            $query->where('status', 'approved')
                ->whereNull('parent_id')
                ->with(['replies' => function ($q) {
                    $q->where('status', 'approved')
                        ->with(['parent', 'replies' => function ($q2) {
                            $q2->where('status', 'approved')
                                ->with(['parent.parent'])
                                ->orderBy('created_at', 'asc');
                        }])
                        ->orderBy('created_at', 'asc');
                }])
                ->orderBy('created_at', 'desc');
        }]);

        // Track view with session-based duplicate prevention (Requirements 15.1, 15.2)
        $this->postViewController->trackView($post, $request);

        // Get related posts using the RelatedPostsService (Requirements 22.1-22.5)
        $relatedPosts = $this->relatedPostsService->getRelatedPosts($post, 5);

        // Get series navigation data (Requirements 37.3-37.5)
        $seriesData = $this->seriesNavigationService->getPostSeriesWithNavigation($post);

        return view('posts.show', compact('post', 'relatedPosts', 'seriesData'));
    }

    public function category($slug, Request $request)
    {
        // Cache category model (Requirement 12.3)
        $category = $this->cacheService->cacheModel('category', $slug, \App\Services\CacheService::TTL_LONG, function () use ($slug) {
            return Category::where('slug', $slug)
                ->active()
                ->with(['parent:id,name,slug', 'children' => function ($query) {
                    $query->active()
                        ->withCount(['posts' => function ($q) {
                            $q->published();
                        }])
                        ->orderBy('display_order')
                        ->orderBy('name');
                }])
                ->select(['id', 'name', 'slug', 'description', 'parent_id', 'icon', 'color_code', 'meta_title', 'meta_description'])
                ->firstOrFail();
        });

        // Build query with filters and sorting (Requirements 26.1-26.5)
        $query = Post::published()
            ->where('category_id', $category->id)
            ->with(['user:id,name', 'category:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id']);

        // Apply sorting (Requirement 26.2, 26.3)
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'popular' => $query->orderBy('view_count', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        // Apply date filters (Requirement 26.4)
        $dateFilter = $request->get('date_filter');
        if ($dateFilter) {
            match ($dateFilter) {
                'today' => $query->whereDate('published_at', today()),
                'week' => $query->where('published_at', '>=', now()->subWeek()),
                'month' => $query->where('published_at', '>=', now()->subMonth()),
                default => null,
            };
        }

        // Cache query results for category pages (Requirement 12.1, 12.2)
        $page = $request->get('page', 1);
        $filters = [
            'sort' => $sort,
            'date_filter' => $dateFilter,
            'page' => $page,
        ];

        // Only cache first page without filters for better hit rate
        if ($page == 1 && empty($dateFilter) && $sort === 'latest') {
            $posts = $this->cacheService->cacheCategoryPage($category->id, $filters, function () use ($query) {
                return $query->paginate(12)->withQueryString();
            });
        } else {
            $posts = $query->paginate(12)->withQueryString();
        }

        // Return JSON for AJAX requests (Requirements 26.1, 27.1-27.5)
        if ($request->wantsJson() || $request->ajax()) {
            $html = '';
            foreach ($posts as $post) {
                $html .= view('partials.post-card', compact('post'))->render();
            }

            return response()->json([
                'html' => $html,
                'currentPage' => $posts->currentPage(),
                'lastPage' => $posts->lastPage(),
                'hasMorePages' => $posts->hasMorePages(),
            ]);
        }

        return view('categories.show', compact('category', 'posts'));
    }

    public function tag($slug, Request $request)
    {
        // Cache tag model (Requirement 12.3)
        $tag = $this->cacheService->cacheModel('tag', $slug, \App\Services\CacheService::TTL_LONG, function () use ($slug) {
            return Tag::where('slug', $slug)
                ->select(['id', 'name', 'slug'])
                ->firstOrFail();
        });

        // Build query with filters and sorting (Requirements 26.1-26.5)
        $query = $tag->posts()
            ->published()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->select(['posts.id', 'posts.title', 'posts.slug', 'posts.excerpt', 'posts.featured_image', 'posts.published_at', 'posts.reading_time', 'posts.view_count', 'posts.user_id', 'posts.category_id']);

        // Apply sorting (Requirement 26.2, 26.3)
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'popular' => $query->orderBy('posts.view_count', 'desc'),
            'oldest' => $query->orderBy('posts.published_at', 'asc'),
            default => $query->orderBy('posts.published_at', 'desc'),
        };

        // Apply date filters (Requirement 26.4)
        $dateFilter = $request->get('date_filter');
        if ($dateFilter) {
            match ($dateFilter) {
                'today' => $query->whereDate('posts.published_at', today()),
                'week' => $query->where('posts.published_at', '>=', now()->subWeek()),
                'month' => $query->where('posts.published_at', '>=', now()->subMonth()),
                default => null,
            };
        }

        // Cache query results for tag pages (Requirement 12.1, 12.2)
        $page = $request->get('page', 1);
        $filters = [
            'sort' => $sort,
            'date_filter' => $dateFilter,
            'page' => $page,
        ];

        // Only cache first page without filters for better hit rate
        if ($page == 1 && empty($dateFilter) && $sort === 'latest') {
            $posts = $this->cacheService->cacheTagPage($tag->id, $filters, function () use ($query) {
                return $query->paginate(12)->withQueryString();
            });
        } else {
            $posts = $query->paginate(12)->withQueryString();
        }

        // Return JSON for AJAX requests (Requirements 26.1, 27.1-27.5)
        if ($request->wantsJson() || $request->ajax()) {
            $html = '';
            foreach ($posts as $post) {
                $html .= view('partials.post-card', compact('post'))->render();
            }

            return response()->json([
                'html' => $html,
                'currentPage' => $posts->currentPage(),
                'lastPage' => $posts->lastPage(),
                'hasMorePages' => $posts->hasMorePages(),
            ]);
        }

        return view('tags.show', compact('tag', 'posts'));
    }

    public function search(SearchRequest $request)
    {
        $validated = $request->validated();
        $query = $validated['q'] ?? '';

        if (empty($query)) {
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                12,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            return view('search', ['posts' => $emptyPaginator, 'query' => '']);
        }

        $startTime = microtime(true);

        // Build filters from validated request
        $filters = array_filter([
            'category' => $validated['category'] ?? null,
            'author' => $validated['author'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
        ]);

        $fuzzyEnabled = $this->fuzzySearchService->isEnabled('posts');
        $avgRelevanceScore = null;

        // Use fuzzy search if enabled, otherwise fallback to basic search
        if ($fuzzyEnabled) {
            try {
                $results = $this->fuzzySearchService->search(
                    query: $query,
                    limit: 100, // Get more results for pagination
                    filters: $filters,
                    logSearch: false // We'll log async below
                );

                // Attach highlights to posts
                $results = $results->map(function ($post) use ($query) {
                    $post->highlighted_title = $this->fuzzySearchService->highlightMatches($post->title, $query);
                    $post->highlighted_excerpt = $this->fuzzySearchService->highlightMatches($post->excerpt ?? '', $query);
                    // Set default relevance score (will be calculated in view if needed)
                    $post->relevance_score = 100;

                    return $post;
                });

                // Calculate average relevance score
                if ($results->isNotEmpty()) {
                    $avgRelevanceScore = $results->avg('relevance_score');
                }

                // Results are already Post models, just paginate them
                $currentPage = $request->get('page', 1);
                $perPage = 12;
                $items = $results->forPage($currentPage, $perPage);
                $posts = new \Illuminate\Pagination\LengthAwarePaginator(
                    $items,
                    $results->count(),
                    $perPage,
                    $currentPage,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } catch (\Exception $e) {
                Log::warning('Fuzzy search failed, falling back to basic search', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);

                $posts = $this->fallbackSearch($query, $filters);
            }
        } else {
            $posts = $this->fallbackSearch($query, $filters);
        }

        // Get spelling suggestion if average relevance is low
        $spellingSuggestion = null;
        if ($fuzzyEnabled && $avgRelevanceScore !== null && $avgRelevanceScore < 85 && $posts->isNotEmpty()) {
            $suggestions = $this->fuzzySearchService->getSuggestions($query, 1);
            if (! empty($suggestions) && $suggestions[0] !== $query) {
                $spellingSuggestion = $suggestions[0];
            }
        }

        // Async query logging
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        if (config('fuzzy-search.analytics.log_queries', true)) {
            $resultCount = $posts->count();
            $fuzzyEnabled = $this->fuzzySearchService->isEnabled('posts');
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

        return view('search', compact('posts', 'query', 'fuzzyEnabled', 'avgRelevanceScore', 'spellingSuggestion'));
    }

    /**
     * Fallback to basic database search using SearchService
     */
    protected function fallbackSearch(string $query, array $filters = [])
    {
        // Use SearchService for basic full-text search with relevance-based sorting
        return $this->searchService->search($query, $filters, 12);
    }
}

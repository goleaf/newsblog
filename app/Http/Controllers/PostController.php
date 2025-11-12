<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use App\Services\FuzzySearchService;
use App\Services\PostService;
use App\Services\SearchAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function __construct(
        protected FuzzySearchService $fuzzySearchService,
        protected SearchAnalyticsService $analyticsService,
        protected PostService $postService
    ) {}

    public function show($slug)
    {
        $post = Cache::remember("post.{$slug}", 3600, function () use ($slug) {
            return Post::where('slug', $slug)
                ->published()
                ->with(['user', 'category', 'tags', 'reactions', 'comments' => function ($query) {
                    $query->where('status', 'approved')->orderBy('created_at', 'desc');
                }])
                ->firstOrFail();
        });

        // Track view (don't cache this)
        PostView::create([
            'post_id' => $post->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now(),
        ]);

        $post->incrementViewCount();

        $relatedPosts = $this->postService->getRelatedPosts($post, 4);

        return view('posts.show', compact('post', 'relatedPosts'));
    }

    public function category($slug)
    {
        $category = Cache::remember("category.{$slug}", 3600, function () use ($slug) {
            return Category::where('slug', $slug)->active()->firstOrFail();
        });

        $posts = Post::published()
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12);

        return view('categories.show', compact('category', 'posts'));
    }

    public function tag($slug)
    {
        $tag = Cache::remember("tag.{$slug}", 3600, function () use ($slug) {
            return Tag::where('slug', $slug)->firstOrFail();
        });

        $posts = $tag->posts()->published()->latest()->paginate(12);

        return view('tags.show', compact('tag', 'posts'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        // Sanitize query input
        $query = trim(strip_tags($query));

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
     * Fallback to basic database search
     */
    protected function fallbackSearch(string $query, array $filters = [])
    {
        $queryBuilder = Post::published()
            ->with(['user', 'category', 'tags'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%");
            });

        if (isset($filters['category'])) {
            $queryBuilder->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
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

        $posts = $queryBuilder->latest()->paginate(12);

        // Add highlighting to fallback results
        $posts->getCollection()->transform(function ($post) use ($query) {
            $post->highlighted_title = $this->fuzzySearchService->highlightMatches($post->title, $query);
            $post->highlighted_excerpt = $this->fuzzySearchService->highlightMatches($post->excerpt ?? '', $query);

            return $post;
        });

        return $posts;
    }
}

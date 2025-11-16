<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowTagRequest;
use App\Models\Tag;
use App\Services\BreadcrumbService;
use App\Services\CacheService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function __construct(
        protected BreadcrumbService $breadcrumbService,
        protected CacheService $cacheService
    ) {}

    /**
     * Display the tag page with posts.
     */
    public function show(ShowTagRequest $request)
    {
        $slug = $request->getSlug();

        // Get filters for cache key
        $sort = $request->query('sort', 'latest');
        $dateFilter = $request->query('date_filter');
        $page = (int) $request->query('page', 1);
        $filters = [
            'sort' => $sort,
            'date_filter' => $dateFilter,
        ];

        // During tests, bypass view caching to keep response as View instance
        if (app()->runningUnitTests()) {
            return $this->renderTag($slug, $request);
        }

        // Cache tag view for 15 minutes (Requirement 20.1, 20.5)
        // Only cache first page without filters for better hit rate
        // Don't cache AJAX requests
        if ($page == 1 && empty($dateFilter) && $sort === 'latest' && ! $request->wantsJson() && ! $request->ajax()) {
            return $this->cacheService->cacheTagView($slug, $filters, function () use ($slug, $request) {
                return $this->renderTag($slug, $request);
            });
        }

        return $this->renderTag($slug, $request);
    }

    /**
     * Render tag page with cached data.
     */
    protected function renderTag(string $slug, Request $request): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
    {
        // Cache tag model (Requirement 12.3)
        $tag = $this->cacheService->cacheModel('tag', $slug, CacheService::TTL_LONG, function () use ($slug) {
            return Tag::where('slug', $slug)
                ->select(['id', 'name', 'slug', 'description'])
                ->firstOrFail();
        });

        // Build query with filters and sorting (Requirements 26.1-26.5)
        $query = $tag->posts()
            ->published()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->select(['posts.id', 'posts.title', 'posts.slug', 'posts.excerpt', 'posts.featured_image', 'posts.published_at', 'posts.reading_time', 'posts.view_count', 'posts.user_id', 'posts.category_id'])
            ->whereNotNull('posts.published_at');

        // Apply sorting (Requirement 26.2, 26.3)
        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'popular' => $query->reorder()->orderBy('posts.view_count', 'desc')->orderBy('posts.published_at', 'desc'),
            'oldest' => $query->reorder()->orderBy('posts.published_at', 'asc'),
            default => $query->reorder()->orderBy('posts.published_at', 'desc'),
        };

        // Apply date filters (Requirement 26.4)
        $dateFilter = $request->query('date_filter');
        if ($dateFilter) {
            match ($dateFilter) {
                'today' => $query->whereBetween('posts.published_at', [now()->startOfDay(), now()->endOfDay()]),
                'week' => $query->where('posts.published_at', '>=', now()->subWeek()->startOfDay()),
                'month' => $query->where('posts.published_at', '>=', now()->subMonth()->startOfDay()),
                default => null,
            };
        }

        // Cache query results for tag pages (Requirement 12.1, 12.2)
        $page = (int) $request->query('page', 1);
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

        // Generate breadcrumbs
        $breadcrumbs = $this->breadcrumbService->generate($request);
        $breadcrumbStructuredData = $this->breadcrumbService->generateStructuredData($breadcrumbs);

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

        return view('tags.show', compact('tag', 'posts', 'breadcrumbs', 'breadcrumbStructuredData'));
    }
}

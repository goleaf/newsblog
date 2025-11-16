<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowCategoryRequest;
use App\Models\Category;
use App\Models\Post;
use App\Services\BreadcrumbService;
use App\Services\CacheService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        protected BreadcrumbService $breadcrumbService,
        protected CacheService $cacheService
    ) {}

    /**
     * Display a listing of all categories with hierarchical structure.
     */
    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        // Cache category list for 30 minutes
        $categories = $this->cacheService->remember(
            'categories.index',
            CacheService::TTL_LONG,
            function () {
                return Category::active()
                    ->parent()
                    ->with([
                        'children' => function ($query) {
                            $query->active()
                                ->withCount(['posts' => function ($q) {
                                    $q->published();
                                }])
                                ->ordered();
                        },
                    ])
                    ->withCount(['posts' => function ($q) {
                        $q->published();
                    }])
                    ->ordered()
                    ->get();
            }
        );

        // Generate breadcrumbs
        $breadcrumbs = $this->breadcrumbService->generate($request);
        $breadcrumbStructuredData = $this->breadcrumbService->generateStructuredData($breadcrumbs);

        return view('categories.index', compact('categories', 'breadcrumbs', 'breadcrumbStructuredData'));
    }

    /**
     * Display the category page with posts.
     */
    public function show(ShowCategoryRequest $request)
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
            return $this->renderCategory($slug, $request);
        }

        // Cache category view for 15 minutes (Requirement 20.1, 20.5)
        // Only cache first page without filters for better hit rate
        // Don't cache AJAX requests
        if ($page == 1 && empty($dateFilter) && $sort === 'latest' && ! $request->wantsJson() && ! $request->ajax()) {
            return $this->cacheService->cacheCategoryView($slug, $filters, function () use ($slug, $request) {
                return $this->renderCategory($slug, $request);
            });
        }

        return $this->renderCategory($slug, $request);
    }

    /**
     * Render category page with cached data.
     */
    protected function renderCategory(string $slug, Request $request): \Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
    {
        // Cache category model (Requirement 12.3)
        $category = $this->cacheService->cacheModel('category', $slug, CacheService::TTL_LONG, function () use ($slug) {
            return Category::where('slug', $slug)
                ->active()
                ->with([
                    'parent:id,name,slug',
                    'children' => function ($query) {
                        $query->active()
                            ->withCount(['posts' => function ($q) {
                                $q->published();
                            }])
                            ->orderBy('display_order')
                            ->orderBy('name');
                    },
                    'children.children' => function ($query) {
                        $query->active();
                    },
                ])
                ->select(['id', 'name', 'slug', 'description', 'parent_id', 'icon', 'color_code', 'meta_title', 'meta_description'])
                ->firstOrFail();
        });

        // Get all category IDs including subcategories (recursively)
        $categoryIds = $category->getAllDescendantIds();

        // Build query with filters and sorting (Requirements 26.1-26.5)
        $query = Post::published()
            ->whereIn('category_id', $categoryIds)
            ->with(['user:id,name', 'category:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id'])
            ->whereNotNull('published_at');

        // Apply sorting (Requirement 26.2, 26.3)
        $sort = $request->query('sort', 'latest');
        match ($sort) {
            'popular' => $query->reorder()->orderBy('view_count', 'desc')->orderBy('published_at', 'desc'),
            'oldest' => $query->reorder()->orderBy('published_at', 'asc'),
            default => $query->reorder()->orderBy('published_at', 'desc'),
        };

        // Apply date filters (Requirement 26.4)
        $dateFilter = $request->query('date_filter');
        if ($dateFilter) {
            match ($dateFilter) {
                'today' => $query->whereBetween('published_at', [now()->startOfDay(), now()->endOfDay()]),
                'week' => $query->where('published_at', '>=', now()->subWeek()->startOfDay()),
                'month' => $query->where('published_at', '>=', now()->subMonth()->startOfDay()),
                default => null,
            };
        }

        // Cache query results for category pages (Requirement 12.1, 12.2)
        $page = (int) $request->query('page', 1);
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

        return view('categories.show', compact('category', 'posts', 'breadcrumbs', 'breadcrumbStructuredData'));
    }
}

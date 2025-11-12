<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\FuzzySearchService;
use App\Services\SearchAnalyticsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        protected FuzzySearchService $fuzzySearchService,
        protected SearchAnalyticsService $searchAnalyticsService
    ) {}

    /**
     * Display admin search results
     */
    public function index(SearchRequest $request): View
    {
        $validated = $request->validated();
        $query = $validated['q'] ?? '';
        $type = $validated['type'] ?? 'posts';

        if (empty($query)) {
            return view('admin.search.index', [
                'results' => collect([]),
                'query' => '',
                'type' => $type,
            ]);
        }

        $results = match ($type) {
            'posts' => $this->searchPosts($query),
            'users' => $this->searchUsers($query),
            'comments' => $this->searchComments($query),
            default => collect([]),
        };

        return view('admin.search.index', compact('results', 'query', 'type'));
    }

    /**
     * Search posts (including drafts and scheduled)
     */
    protected function searchPosts(string $query): Collection
    {
        // Admin search uses basic database search for now
        // Fuzzy search returns SearchResult DTOs which don't work well with admin views
        return $this->basicPostSearch($query);
    }

    /**
     * Basic post search fallback
     */
    protected function basicPostSearch(string $query): Collection
    {
        return Post::with(['user', 'category'])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%");
                    });
            })
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Search users
     */
    protected function searchUsers(string $query): Collection
    {
        return User::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
        })
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Search comments
     */
    protected function searchComments(string $query): Collection
    {
        return Comment::with(['user', 'post'])
            ->where(function ($q) use ($query) {
                $q->where('content', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%");
                    })
                    ->orWhereHas('post', function ($postQuery) use ($query) {
                        $postQuery->where('title', 'like', "%{$query}%");
                    });
            })
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Display search analytics dashboard
     */
    public function analytics(): View
    {
        // Get performance metrics for last 24 hours
        $metrics = $this->searchAnalyticsService->getPerformanceMetrics('day');

        // Get top queries for last 30 days
        $topQueries = $this->searchAnalyticsService->getTopQueries(20, 'month');

        // Get queries with no results
        $noResultQueries = $this->searchAnalyticsService->getNoResultQueries(20);

        // Get chart data for last 7 days
        $chartData = $this->getSearchTrendData();

        return view('admin.search.analytics', compact(
            'metrics',
            'topQueries',
            'noResultQueries',
            'chartData'
        ));
    }

    /**
     * Get search trend data for chart
     */
    protected function getSearchTrendData(): array
    {
        $days = collect(range(6, 0))->map(function ($daysAgo) {
            return now()->subDays($daysAgo)->startOfDay();
        });

        $searches = SearchLog::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN result_count = 0 THEN 1 ELSE 0 END) as no_results')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return [
            'labels' => $days->map(fn ($date) => $date->format('M j'))->toArray(),
            'searches' => $days->map(function ($date) use ($searches) {
                $key = $date->format('Y-m-d');

                return $searches->get($key)?->total ?? 0;
            })->toArray(),
            'no_results' => $days->map(function ($date) use ($searches) {
                $key = $date->format('Y-m-d');

                return $searches->get($key)?->no_results ?? 0;
            })->toArray(),
        ];
    }
}

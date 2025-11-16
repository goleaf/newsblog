<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EngagementMetric;
use App\Models\Post;
use App\Models\PostView;
use App\Services\SearchAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct(
        protected SearchAnalyticsService $searchAnalytics
    ) {
    }

    /**
     * Display analytics dashboard.
     * Requirement: 16.3
     */
    public function index(Request $request)
    {
        $period = $request->input('period', 'week');

        // View statistics
        $viewStats = $this->getViewStatistics($period);

        // Engagement metrics
        $engagementStats = $this->getEngagementStatistics($period);

        // Search analytics
        $searchStats = $this->searchAnalytics->getPerformanceMetrics($period);
        $topQueries = $this->searchAnalytics->getTopQueries(10, $period);
        $noResultQueries = $this->searchAnalytics->getNoResultQueries(10);
        $clickThroughRate = $this->searchAnalytics->getClickThroughRate($period);

        // Top performing posts
        $topPosts = $this->getTopPosts($period);

        // Popular categories by views
        $popularCategories = $this->getPopularCategories($period);

        // Traffic sources breakdown
        $trafficSources = $this->getTrafficSources($period);

        return view('admin.analytics.index', compact(
            'viewStats',
            'engagementStats',
            'searchStats',
            'topQueries',
            'noResultQueries',
            'clickThroughRate',
            'topPosts',
            'popularCategories',
            'trafficSources',
            'period'
        ));
    }

    /**
     * Get view statistics for the period
     */
    protected function getViewStatistics(string $period): array
    {
        $query = PostView::query();
        $query = $this->applyPeriodFilter($query, $period);

        $stats = $query->selectRaw('
            COUNT(*) as total_views,
            COUNT(DISTINCT session_id) as unique_visitors,
            COUNT(DISTINCT post_id) as posts_viewed
        ')->first();

        // Get views over time for chart
        $viewsOverTime = PostView::query()
            ->when($period, fn ($q) => $this->applyPeriodFilter($q, $period))
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_views' => $stats->total_views ?? 0,
            'unique_visitors' => $stats->unique_visitors ?? 0,
            'posts_viewed' => $stats->posts_viewed ?? 0,
            'views_over_time' => $viewsOverTime,
        ];
    }

    /**
     * Get popular categories by views for the period
     */
    protected function getPopularCategories(string $period, int $limit = 5): \Illuminate\Support\Collection
    {
        $query = DB::table('post_views')
            ->join('posts', 'post_views.post_id', '=', 'posts.id')
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as views'))
            ->groupBy('categories.name')
            ->orderByDesc('views')
            ->limit($limit);

        $query = match ($period) {
            'day' => $query->where('post_views.viewed_at', '>=', now()->subDay()),
            'week' => $query->where('post_views.viewed_at', '>=', now()->subWeek()),
            'month' => $query->where('post_views.viewed_at', '>=', now()->subMonth()),
            'year' => $query->where('post_views.viewed_at', '>=', now()->subYear()),
            default => $query->where('post_views.viewed_at', '>=', now()->subWeek()),
        };

        return $query->get();
    }

    /**
     * Get traffic sources breakdown for the period
     */
    protected function getTrafficSources(string $period): array
    {
        $baseQuery = DB::table('post_views')
            ->select('referer', DB::raw('COUNT(*) as count'));

        $baseQuery = match ($period) {
            'day' => $baseQuery->where('viewed_at', '>=', now()->subDay()),
            'week' => $baseQuery->where('viewed_at', '>=', now()->subWeek()),
            'month' => $baseQuery->where('viewed_at', '>=', now()->subMonth()),
            'year' => $baseQuery->where('viewed_at', '>=', now()->subYear()),
            default => $baseQuery->where('viewed_at', '>=', now()->subWeek()),
        };

        $rows = $baseQuery->groupBy('referer')->get();

        $sources = [
            'direct' => 0,
            'search' => 0,
            'social' => 0,
            'referral' => 0,
        ];

        foreach ($rows as $row) {
            $referer = $row->referer ?? '';
            $count = (int) $row->count;

            if (empty($referer)) {
                $sources['direct'] += $count;
                continue;
            }

            $host = parse_url($referer, PHP_URL_HOST) ?: '';
            $host = strtolower($host);

            if ($host === '') {
                $sources['direct'] += $count;
            } elseif (str_contains($host, 'google.') || str_contains($host, 'bing.') || str_contains($host, 'duckduckgo.') || str_contains($host, 'yahoo.')) {
                $sources['search'] += $count;
            } elseif (str_contains($host, 'twitter.') || str_contains($host, 'x.com') || str_contains($host, 'facebook.') || str_contains($host, 'instagram.') || str_contains($host, 'linkedin.')) {
                $sources['social'] += $count;
            } else {
                $sources['referral'] += $count;
            }
        }

        return $sources;
    }

    /**
     * Get engagement statistics for the period
     */
    protected function getEngagementStatistics(string $period): array
    {
        $query = EngagementMetric::query();
        $query = $this->applyPeriodFilter($query, $period);

        $stats = $query->selectRaw('
            AVG(time_on_page) as avg_time_on_page,
            AVG(scroll_depth) as avg_scroll_depth,
            SUM(CASE WHEN clicked_bookmark THEN 1 ELSE 0 END) as bookmark_clicks,
            SUM(CASE WHEN clicked_share THEN 1 ELSE 0 END) as share_clicks,
            SUM(CASE WHEN clicked_reaction THEN 1 ELSE 0 END) as reaction_clicks,
            SUM(CASE WHEN clicked_comment THEN 1 ELSE 0 END) as comment_clicks,
            SUM(CASE WHEN clicked_related_post THEN 1 ELSE 0 END) as related_post_clicks,
            COUNT(*) as total_sessions
        ')->first();

        return [
            'avg_time_on_page' => round($stats->avg_time_on_page ?? 0),
            'avg_scroll_depth' => round($stats->avg_scroll_depth ?? 0),
            'bookmark_clicks' => $stats->bookmark_clicks ?? 0,
            'share_clicks' => $stats->share_clicks ?? 0,
            'reaction_clicks' => $stats->reaction_clicks ?? 0,
            'comment_clicks' => $stats->comment_clicks ?? 0,
            'related_post_clicks' => $stats->related_post_clicks ?? 0,
            'total_sessions' => $stats->total_sessions ?? 0,
            'engagement_rate' => $stats->total_sessions > 0
                ? round((($stats->bookmark_clicks + $stats->share_clicks + $stats->reaction_clicks + $stats->comment_clicks) / $stats->total_sessions) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get top performing posts for the period
     */
    protected function getTopPosts(string $period, int $limit = 10): \Illuminate\Support\Collection
    {
        $query = Post::query()
            ->with(['category', 'user'])
            ->published();

        if ($period !== 'all') {
            $query->whereHas('views', function ($q) use ($period) {
                $this->applyPeriodFilter($q, $period);
            });
        }

        return $query->withCount(['views' => function ($q) use ($period) {
            if ($period !== 'all') {
                $this->applyPeriodFilter($q, $period);
            }
        }])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Apply period filter to query
     */
    protected function applyPeriodFilter($query, string $period)
    {
        $date = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };

        return $query->where('created_at', '>=', $date);
    }
}

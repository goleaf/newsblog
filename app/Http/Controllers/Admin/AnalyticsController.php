<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EngagementMetric;
use App\Models\Post;
use App\Models\PostView;
use App\Services\AnalyticsService;
use App\Services\SearchAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analytics,
        protected SearchAnalyticsService $searchAnalytics
    ) {}

    /**
     * Display analytics dashboard overview.
     * Requirements: 8.1, 8.2
     */
    public function index(Request $request)
    {
        $period = $request->input('period', 'week');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // User metrics
        $userMetrics = $this->analytics->calculateUserMetrics($startDate, $endDate);
        $engagementStats = $userMetrics['engagement'] ?? ['avg_session_duration' => 0];

        // Traffic metrics and sources breakdown
        $trafficMetrics = $this->analytics->calculateTrafficMetrics($startDate, $endDate);
        $trafficSources = $this->getTrafficSources($period);

        // Top articles
        $topArticles = $this->analytics->getTopArticles(10, $startDate, $endDate);
        $topPosts = $topArticles; // alias for tests expecting 'topPosts'

        // Aggregated stats and view stats
        $aggregatedStats = $this->analytics->aggregateStats('daily', $startDate, $endDate);
        $viewStats = $this->getViewStatistics($period);

        // Search analytics
        $searchStats = $this->searchAnalytics->getPerformanceMetrics($period);
        $topQueries = $this->searchAnalytics->getTopQueries(10, $period);

        return view('admin.analytics.index', compact(
            'userMetrics',
            'trafficMetrics',
            'trafficSources',
            'topArticles', 'topPosts',
            'aggregatedStats',
            'viewStats',
            'searchStats',
            'topQueries',
            'period'
        ))->with('engagementStats', $engagementStats);
    }

    /**
     * Get article performance metrics.
     * Requirements: 8.1, 8.2, 8.3
     */
    public function articlePerformance(Request $request)
    {
        $period = $request->input('period', 'month');
        $postId = $request->input('post_id');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        if ($postId) {
            // Single article metrics
            $metrics = $this->analytics->calculateArticleMetrics($postId, $startDate, $endDate);

            return response()->json($metrics);
        }

        // Top articles
        $topArticles = $this->analytics->getTopArticles(50, $startDate, $endDate);

        return view('admin.analytics.articles', compact('topArticles', 'period'));
    }

    /**
     * Get traffic sources breakdown.
     * Requirements: 8.1, 8.4
     */
    public function trafficSources(Request $request)
    {
        $period = $request->input('period', 'month');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $trafficMetrics = $this->analytics->calculateTrafficMetrics($startDate, $endDate);

        return view('admin.analytics.traffic', compact('trafficMetrics', 'period'));
    }

    /**
     * Get user engagement metrics.
     * Requirements: 8.1, 8.2
     */
    public function userEngagement(Request $request)
    {
        $period = $request->input('period', 'month');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $userMetrics = $this->analytics->calculateUserMetrics($startDate, $endDate);
        $aggregatedStats = $this->analytics->aggregateStats('daily', $startDate, $endDate);

        return view('admin.analytics.engagement', compact('userMetrics', 'aggregatedStats', 'period'));
    }

    /**
     * Export analytics data.
     * Requirements: 8.5
     */
    public function export(Request $request): StreamedResponse
    {
        $period = $request->input('period', 'month');
        $format = $request->input('format', 'csv');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $data = [
            'user_metrics' => $this->analytics->calculateUserMetrics($startDate, $endDate),
            'traffic_metrics' => $this->analytics->calculateTrafficMetrics($startDate, $endDate),
            'top_articles' => $this->analytics->getTopArticles(50, $startDate, $endDate),
            'aggregated_stats' => $this->analytics->aggregateStats('daily', $startDate, $endDate),
        ];

        if ($format === 'csv') {
            return $this->exportCsv($data, $startDate, $endDate);
        }

        return $this->exportJson($data, $startDate, $endDate);
    }

    /**
     * Export data as CSV.
     */
    private function exportCsv(array $data, Carbon $startDate, Carbon $endDate): StreamedResponse
    {
        $filename = "analytics_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.csv";

        return response()->stream(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // User Metrics Section
            fputcsv($handle, ['User Metrics']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Daily Active Users', $data['user_metrics']['active_users']['daily']]);
            fputcsv($handle, ['Monthly Active Users', $data['user_metrics']['active_users']['monthly']]);
            fputcsv($handle, ['New Registrations', $data['user_metrics']['registrations']['new_in_period']]);
            fputcsv($handle, ['7-Day Retention Rate', $data['user_metrics']['retention']['rate_7_day'].'%']);
            fputcsv($handle, []);

            // Traffic Sources Section
            fputcsv($handle, ['Traffic Sources']);
            fputcsv($handle, ['Source', 'Count', 'Percentage']);
            foreach ($data['traffic_metrics']['sources'] as $source) {
                fputcsv($handle, [$source['source'], $source['count'], $source['percentage'].'%']);
            }
            fputcsv($handle, []);

            // Top Articles Section
            fputcsv($handle, ['Top Articles']);
            fputcsv($handle, ['Title', 'Views', 'Comments', 'Reactions', 'Engagement Score']);
            foreach ($data['top_articles'] as $article) {
                fputcsv($handle, [
                    $article['title'],
                    $article['views'],
                    $article['comments'],
                    $article['reactions'],
                    $article['engagement_score'],
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export data as JSON.
     */
    private function exportJson(array $data, Carbon $startDate, Carbon $endDate): StreamedResponse
    {
        $filename = "analytics_{$startDate->format('Y-m-d')}_to_{$endDate->format('Y-m-d')}.json";

        return response()->stream(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get start and end dates for a period.
     */
    private function getPeriodDates(string $period): array
    {
        $endDate = now();
        $startDate = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        return [$startDate, $endDate];
    }

    /**
     * Get view statistics for the period
     */
    protected function getViewStatistics(string $period): array
    {
        $query = PostView::query();
        // PostView uses 'viewed_at' instead of 'created_at'
        $query = $this->applyPeriodFilter($query, $period, 'viewed_at');

        $stats = $query->selectRaw('
            COUNT(*) as total_views,
            COUNT(DISTINCT session_id) as unique_visitors,
            COUNT(DISTINCT post_id) as posts_viewed
        ')->first();

        // Get views over time for chart
        $viewsOverTime = PostView::query()
            ->when($period, fn ($q) => $this->applyPeriodFilter($q, $period, 'viewed_at'))
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
                // Filter by PostView's 'viewed_at'
                $this->applyPeriodFilter($q, $period, 'viewed_at');
            });
        }

        return $query->withCount(['views' => function ($q) use ($period) {
            if ($period !== 'all') {
                $this->applyPeriodFilter($q, $period, 'viewed_at');
            }
        }])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Apply period filter to query
     */
    protected function applyPeriodFilter($query, string $period, string $column = 'created_at')
    {
        $date = match ($period) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subWeek(),
        };

        return $query->where($column, '>=', $date);
    }
}

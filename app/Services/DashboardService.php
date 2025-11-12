<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get all dashboard metrics with caching
     */
    public function getMetrics(): array
    {
        return Cache::remember('dashboard.metrics', 600, function () {
            return [
                'total_posts' => $this->getTotalPosts(),
                'posts_comparison' => $this->getPostsComparison(),
                'views_today' => $this->getViewsToday(),
                'views_week' => $this->getViewsWeek(),
                'views_month' => $this->getViewsMonth(),
                'pending_comments' => $this->getPendingCommentsCount(),
                'top_posts' => $this->getTopPosts(),
                'posts_chart_data' => $this->getPostsChartData(),
            ];
        });
    }

    /**
     * Get total published posts count
     */
    private function getTotalPosts(): int
    {
        return Post::where('status', 'published')->count();
    }

    /**
     * Get 30-day post comparison statistics
     */
    private function getPostsComparison(): array
    {
        $currentPeriodStart = now()->subDays(30);
        $previousPeriodStart = now()->subDays(60);
        $previousPeriodEnd = now()->subDays(30);

        $currentCount = Post::where('status', 'published')
            ->where('published_at', '>=', $currentPeriodStart)
            ->count();

        $previousCount = Post::where('status', 'published')
            ->whereBetween('published_at', [$previousPeriodStart, $previousPeriodEnd])
            ->count();

        $percentageChange = $previousCount > 0
            ? (($currentCount - $previousCount) / $previousCount) * 100
            : ($currentCount > 0 ? 100 : 0);

        return [
            'current' => $currentCount,
            'previous' => $previousCount,
            'percentage' => round($percentageChange, 1),
            'is_increase' => $percentageChange >= 0,
        ];
    }

    /**
     * Get view count for today
     */
    private function getViewsToday(): int
    {
        return PostView::whereDate('viewed_at', today())->count();
    }

    /**
     * Get view count for the last 7 days
     */
    private function getViewsWeek(): int
    {
        return PostView::where('viewed_at', '>=', now()->subDays(7))->count();
    }

    /**
     * Get view count for the last 30 days
     */
    private function getViewsMonth(): int
    {
        return PostView::where('viewed_at', '>=', now()->subDays(30))->count();
    }

    /**
     * Get pending comments count
     */
    private function getPendingCommentsCount(): int
    {
        return Comment::where('status', 'pending')->count();
    }

    /**
     * Get top 10 posts by view count
     */
    private function getTopPosts(): array
    {
        return Post::published()
            ->select('id', 'title', 'slug', 'view_count', 'published_at')
            ->orderByDesc('view_count')
            ->limit(10)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'view_count' => $post->view_count,
                    'published_at' => $post->published_at->format('M d, Y'),
                ];
            })
            ->toArray();
    }

    /**
     * Get posts published chart data for the last 30 days
     */
    private function getPostsChartData(): array
    {
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();

        $posts = Post::where('status', 'published')
            ->whereBetween('published_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(published_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Generate all dates in the range
        $dates = [];
        $counts = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dates[] = $currentDate->format('M d');
            $counts[] = $posts->get($dateStr)?->count ?? 0;
            $currentDate->addDay();
        }

        return [
            'labels' => $dates,
            'data' => $counts,
        ];
    }

    /**
     * Clear dashboard metrics cache
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard.metrics');
    }
}

<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\EngagementMetric;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Reaction;
use App\Models\SocialShare;
use App\Models\TrafficSource;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Calculate article metrics (views, reading time, engagement)
     */
    public function calculateArticleMetrics(int $postId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "analytics:article:{$postId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 900, function () use ($postId, $startDate, $endDate) {
            $post = Post::find($postId);
            if (! $post) {
                return [];
            }

            // Views metrics
            $viewsQuery = PostView::where('post_id', $postId)
                ->whereBetween('viewed_at', [$startDate, $endDate]);

            $totalViews = $viewsQuery->count();
            $uniqueViews = $viewsQuery->distinct('session_id')->count('session_id');

            // Engagement metrics
            $engagementQuery = EngagementMetric::where('post_id', $postId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            $avgTimeOnPage = $engagementQuery->avg('time_on_page') ?? 0;
            $avgScrollDepth = $engagementQuery->avg('scroll_depth') ?? 0;

            $engagementCounts = $engagementQuery->selectRaw('
                SUM(clicked_bookmark) as bookmarks,
                SUM(clicked_share) as shares,
                SUM(clicked_reaction) as reactions,
                SUM(clicked_comment) as comment_clicks
            ')->first();

            // Comments
            $commentsCount = Comment::where('post_id', $postId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Reactions
            $reactionsCount = Reaction::where('post_id', $postId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Social shares
            $socialSharesCount = SocialShare::where('post_id', $postId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // Calculate engagement score
            $engagementScore = $this->calculateEngagementScore([
                'views' => $totalViews,
                'comments' => $commentsCount,
                'reactions' => $reactionsCount,
                'shares' => $socialSharesCount,
                'avg_time' => $avgTimeOnPage,
                'avg_scroll' => $avgScrollDepth,
            ]);

            return [
                'post_id' => $postId,
                'post_title' => $post->title,
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'views' => [
                    'total' => $totalViews,
                    'unique' => $uniqueViews,
                ],
                'engagement' => [
                    'avg_time_on_page' => round($avgTimeOnPage, 2),
                    'avg_scroll_depth' => round($avgScrollDepth, 2),
                    'bookmarks' => (int) ($engagementCounts->bookmarks ?? 0),
                    'shares' => (int) ($engagementCounts->shares ?? 0),
                    'reactions' => (int) ($engagementCounts->reactions ?? 0),
                    'comment_clicks' => (int) ($engagementCounts->comment_clicks ?? 0),
                ],
                'interactions' => [
                    'comments' => $commentsCount,
                    'reactions' => $reactionsCount,
                    'social_shares' => $socialSharesCount,
                ],
                'engagement_score' => round($engagementScore, 2),
            ];
        });
    }

    /**
     * Calculate user metrics (DAU, MAU, retention)
     */
    public function calculateUserMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "analytics:users:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 900, function () use ($startDate, $endDate) {
            // Daily Active Users (last 24 hours)
            $dau = PostView::where('viewed_at', '>=', now()->subDay())
                ->distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id');

            // Monthly Active Users (last 30 days)
            $mau = PostView::where('viewed_at', '>=', now()->subDays(30))
                ->distinct('user_id')
                ->whereNotNull('user_id')
                ->count('user_id');

            // New registrations in period
            $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();

            // Total registered users
            $totalUsers = User::count();

            // Calculate retention rate (users who returned after 7 days)
            $cohortDate = now()->subDays(7);
            $cohortUsers = User::whereDate('created_at', $cohortDate->toDateString())->pluck('id');

            $returnedUsers = PostView::whereIn('user_id', $cohortUsers)
                ->where('viewed_at', '>', $cohortDate->addDay())
                ->distinct('user_id')
                ->count('user_id');

            $retentionRate = $cohortUsers->count() > 0
                ? ($returnedUsers / $cohortUsers->count()) * 100
                : 0;

            // Average session duration
            $avgSessionDuration = EngagementMetric::whereBetween('created_at', [$startDate, $endDate])
                ->avg('time_on_page') ?? 0;

            return [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'active_users' => [
                    'daily' => $dau,
                    'monthly' => $mau,
                ],
                'registrations' => [
                    'new_in_period' => $newUsers,
                    'total' => $totalUsers,
                ],
                'retention' => [
                    'rate_7_day' => round($retentionRate, 2),
                    'cohort_size' => $cohortUsers->count(),
                    'returned_users' => $returnedUsers,
                ],
                'engagement' => [
                    'avg_session_duration' => round($avgSessionDuration, 2),
                ],
            ];
        });
    }

    /**
     * Calculate traffic metrics (sources, devices, locations)
     */
    public function calculateTrafficMetrics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "analytics:traffic:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 900, function () use ($startDate, $endDate) {
            // Traffic sources
            $trafficSources = TrafficSource::whereBetween('created_at', [$startDate, $endDate])
                ->select('source', DB::raw('COUNT(*) as count'))
                ->groupBy('source')
                ->orderByDesc('count')
                ->get()
                ->map(function ($item) {
                    return [
                        'source' => $item->source ?? 'direct',
                        'count' => $item->count,
                    ];
                });

            $totalTraffic = $trafficSources->sum('count');

            $sourcesWithPercentage = $trafficSources->map(function ($item) use ($totalTraffic) {
                return [
                    'source' => $item['source'],
                    'count' => $item['count'],
                    'percentage' => $totalTraffic > 0 ? round(($item['count'] / $totalTraffic) * 100, 2) : 0,
                ];
            });

            // Device breakdown (from user agent)
            $deviceStats = PostView::whereBetween('viewed_at', [$startDate, $endDate])
                ->select('user_agent', DB::raw('COUNT(*) as count'))
                ->get()
                ->groupBy(function ($item) {
                    return $this->detectDevice($item->user_agent);
                })
                ->map(function ($group) {
                    return $group->sum('count');
                });

            $totalViews = $deviceStats->sum();

            $devices = $deviceStats->map(function ($count, $device) use ($totalViews) {
                return [
                    'device' => $device,
                    'count' => $count,
                    'percentage' => $totalViews > 0 ? round(($count / $totalViews) * 100, 2) : 0,
                ];
            })->values();

            // Top referrers
            $topReferrers = PostView::whereBetween('viewed_at', [$startDate, $endDate])
                ->whereNotNull('referer')
                ->where('referer', '!=', '')
                ->select('referer', DB::raw('COUNT(*) as count'))
                ->groupBy('referer')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'referer' => $this->cleanReferrer($item->referer),
                        'count' => $item->count,
                    ];
                });

            return [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'sources' => $sourcesWithPercentage->toArray(),
                'devices' => $devices->toArray(),
                'top_referrers' => $topReferrers->toArray(),
                'total_traffic' => $totalTraffic,
            ];
        });
    }

    /**
     * Aggregate daily/weekly/monthly stats
     */
    public function aggregateStats(string $period = 'daily', ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "analytics:aggregate:{$period}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 1800, function () use ($period, $startDate, $endDate) {
            $dateFormat = match ($period) {
                'daily' => '%Y-%m-%d',
                'weekly' => '%Y-%u',
                'monthly' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            $driver = DB::getDriverName();
            $dateExpr = fn (string $column) => $driver === 'sqlite'
                ? DB::raw("strftime('{$dateFormat}', {$column}) as period")
                : DB::raw("DATE_FORMAT({$column}, '{$dateFormat}') as period");

            // Views aggregation
            $viewsData = PostView::whereBetween('viewed_at', [$startDate, $endDate])
                ->select(
                    $dateExpr('viewed_at'),
                    DB::raw('COUNT(*) as total_views'),
                    DB::raw('COUNT(DISTINCT session_id) as unique_views')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            // Comments aggregation
            $commentsData = Comment::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    $dateExpr('created_at'),
                    DB::raw('COUNT(*) as total_comments')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            // New users aggregation
            $usersData = User::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    $dateExpr('created_at'),
                    DB::raw('COUNT(*) as new_users')
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get()
                ->keyBy('period');

            // Combine all data
            $aggregated = $viewsData->map(function ($item) use ($commentsData, $usersData) {
                return [
                    'period' => $item->period,
                    'views' => [
                        'total' => $item->total_views,
                        'unique' => $item->unique_views,
                    ],
                    'comments' => $commentsData->get($item->period)?->total_comments ?? 0,
                    'new_users' => $usersData->get($item->period)?->new_users ?? 0,
                ];
            });

            return [
                'period_type' => $period,
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                ],
                'data' => $aggregated->toArray(),
            ];
        });
    }

    /**
     * Get top performing articles
     */
    public function getTopArticles(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $cacheKey = "analytics:top_articles:{$limit}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 900, function () use ($limit, $startDate, $endDate) {
            $articles = Post::published()
                ->withCount([
                    'views as views_count' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('viewed_at', [$startDate, $endDate]);
                    },
                    'comments as comments_count' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    },
                    'reactions as reactions_count' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    },
                ])
                ->orderByDesc('views_count')
                ->limit($limit)
                ->get()
                ->map(function ($post) {
                    $engagementScore = $this->calculateEngagementScore([
                        'views' => $post->views_count,
                        'comments' => $post->comments_count,
                        'reactions' => $post->reactions_count,
                        'shares' => 0,
                        'avg_time' => 0,
                        'avg_scroll' => 0,
                    ]);

                    return [
                        'id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'views' => $post->views_count,
                        'comments' => $post->comments_count,
                        'reactions' => $post->reactions_count,
                        'engagement_score' => round($engagementScore, 2),
                        'published_at' => $post->published_at?->toDateString(),
                    ];
                });

            return $articles->toArray();
        });
    }

    /**
     * Calculate engagement score
     */
    private function calculateEngagementScore(array $metrics): float
    {
        $views = $metrics['views'] ?? 0;
        $comments = $metrics['comments'] ?? 0;
        $reactions = $metrics['reactions'] ?? 0;
        $shares = $metrics['shares'] ?? 0;
        $avgTime = $metrics['avg_time'] ?? 0;
        $avgScroll = $metrics['avg_scroll'] ?? 0;

        if ($views === 0) {
            return 0;
        }

        // Weighted engagement score
        $score = (
            ($comments * 10) +
            ($reactions * 5) +
            ($shares * 15) +
            (($avgTime / 60) * 2) + // Time in minutes
            ($avgScroll / 10) // Scroll depth percentage
        ) / $views * 100;

        return min($score, 100); // Cap at 100
    }

    /**
     * Detect device type from user agent
     */
    private function detectDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Clean referrer URL
     */
    private function cleanReferrer(string $referrer): string
    {
        $parsed = parse_url($referrer);

        return $parsed['host'] ?? $referrer;
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(): void
    {
        Cache::tags(['analytics'])->flush();
    }
}

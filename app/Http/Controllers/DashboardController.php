<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\PostView;
use App\Models\Reaction;
use App\Models\SearchLog;
use App\Services\DashboardService;
use App\Services\SearchAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly SearchAnalyticsService $searchAnalyticsService
    ) {}

    /**
     * Display the dashboard
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Admin/Editor dashboard
        if ($user->role === 'admin' || $user->role === 'editor') {
            $metrics = $this->dashboardService->getMetrics();
            $searchStats = $this->getSearchStats();

            return view('dashboard', [
                'metrics' => $metrics,
                'searchStats' => $searchStats,
                'stats' => null,
                'recentBookmarks' => null,
                'recentComments' => null,
                'recentReactions' => null,
            ]);
        }

        // Regular user dashboard
        $stats = $this->getUserStats($user);
        $recentBookmarks = $this->getRecentBookmarks($user);
        $recentComments = $this->getRecentComments($user);
        $recentReactions = $this->getRecentReactions($user);
        $readingHistory = $this->getReadingHistory($user);
        $notificationSummary = $this->getNotificationSummary($user);

        return view('dashboard', [
            'stats' => $stats,
            'recentBookmarks' => $recentBookmarks,
            'recentComments' => $recentComments,
            'recentReactions' => $recentReactions,
            'readingHistory' => $readingHistory,
            'notificationSummary' => $notificationSummary,
            'metrics' => null,
            'searchStats' => null,
        ]);
    }

    /**
     * Get user statistics
     */
    private function getUserStats($user): array
    {
        return [
            'bookmarks_count' => Bookmark::where('user_id', $user->id)->count(),
            'comments_count' => Comment::where('user_id', $user->id)->count(),
            'reactions_count' => Reaction::where('user_id', $user->id)->count(),
            'total_reading_time' => Bookmark::where('user_id', $user->id)
                ->with('post')
                ->get()
                ->sum(fn ($bookmark) => $bookmark->post->reading_time ?? 0),
        ];
    }

    /**
     * Get recent bookmarks
     */
    private function getRecentBookmarks($user)
    {
        return Bookmark::where('user_id', $user->id)
            ->with('post.category')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get recent comments
     */
    private function getRecentComments($user)
    {
        return Comment::where('user_id', $user->id)
            ->with('post')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get recent reactions
     */
    private function getRecentReactions($user)
    {
        return Reaction::where('user_id', $user->id)
            ->with('post')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get reading history for the user limited to 100 most recent views.
     */
    private function getReadingHistory($user)
    {
        return PostView::query()
            ->where('user_id', $user->id)
            ->with(['post:id,slug,title,featured_image,image_alt_text,published_at'])
            ->orderByDesc('viewed_at')
            ->limit(100)
            ->get();
    }

    /**
     * Get notification summary for the authenticated user.
     */
    private function getNotificationSummary($user): array
    {
        $unreadCount = Notification::where('user_id', $user->id)->unread()->count();
        $recent = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'message', 'type', 'action_url', 'read_at', 'created_at']);

        return [
            'unread_count' => $unreadCount,
            'recent' => $recent,
        ];
    }

    /**
     * Get search statistics for admin dashboard
     */
    private function getSearchStats(): array
    {
        $popularQueries = SearchLog::select('query', \DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'total_today' => SearchLog::whereDate('created_at', today())->count(),
            'recent_searches' => SearchLog::latest()->limit(10)->get(),
            'popular_queries' => $popularQueries,
        ];
    }
}

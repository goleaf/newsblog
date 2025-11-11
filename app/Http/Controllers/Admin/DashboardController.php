<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Newsletter;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_posts' => Post::count(),
            'published_posts' => Post::published()->count(),
            'draft_posts' => Post::where('status', 'draft')->count(),
            'total_views' => Post::sum('view_count'),
            'today_views' => DB::table('post_views')
                ->whereDate('viewed_at', today())
                ->count(),
            'total_comments' => Comment::count(),
            'pending_comments' => Comment::pending()->count(),
            'approved_comments' => Comment::approved()->count(),
            'total_subscribers' => Newsletter::subscribed()->count(),
            'verified_subscribers' => Newsletter::verified()->count(),
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
        ];

        $recentPosts = Post::with('user', 'category')
            ->latest()
            ->take(5)
            ->get();

        $popularPosts = Post::published()
            ->orderBy('view_count', 'desc')
            ->take(10)
            ->get();

        $recentComments = Comment::with('post', 'user')
            ->latest()
            ->take(10)
            ->get();

        $postsChart = Post::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $categoriesStats = Category::withCount(['posts' => function ($query) {
            $query->published();
        }])
            ->orderBy('posts_count', 'desc')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentPosts',
            'popularPosts',
            'recentComments',
            'postsChart',
            'categoriesStats'
        ));
    }
}

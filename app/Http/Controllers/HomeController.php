<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    public function index(Request $request)
    {
        // Cache individual data components with appropriate TTLs (Requirement 12.1, 12.2)
        $featuredPosts = Cache::remember('home.featured', CacheService::TTL_LONG, function () {
            return Post::published()
                ->featured()
                ->with(['user:id,name', 'category:id,name,slug'])
                ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id'])
                ->latest()
                ->take(3)
                ->get();
        });

        $trendingPosts = Cache::remember('home.trending', CacheService::TTL_MEDIUM, function () {
            return Post::published()
                ->trending()
                ->with(['user:id,name', 'category:id,name,slug'])
                ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id'])
                ->latest()
                ->take(6)
                ->get();
        });

        // Get sort parameter
        $sort = request('sort', 'newest');

        // Build query for recent posts with sorting
        $query = Post::published()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id']);

        // Apply sorting
        switch ($sort) {
            case 'popular':
                $query->orderBy('view_count', 'desc');
                break;
            case 'trending':
                $query->trending();
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        // Paginate results (12 per page)
        $recentPosts = $query->paginate(12)->withQueryString();

        $categories = Cache::remember('home.categories', CacheService::TTL_LONG, function () {
            return Category::active()
                ->parents()
                ->ordered()
                ->withCount('posts')
                ->select(['id', 'name', 'slug', 'description', 'icon', 'color_code'])
                ->take(8)
                ->get();
        });

        return view('home', compact('featuredPosts', 'trendingPosts', 'recentPosts', 'categories'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index()
    {
        $featuredPosts = Cache::remember('home.featured', 3600, function () {
            return Post::published()->featured()->latest()->take(3)->get();
        });

        $trendingPosts = Cache::remember('home.trending', 1800, function () {
            return Post::published()->trending()->latest()->take(6)->get();
        });

        $recentPosts = Cache::remember('home.recent', 600, function () {
            return Post::published()->recent()->take(10)->get();
        });

        $categories = Cache::remember('home.categories', 3600, function () {
            return Category::active()->parents()->ordered()->withCount('posts')->take(8)->get();
        });

        return view('home', compact('featuredPosts', 'trendingPosts', 'recentPosts', 'categories'));
    }
}


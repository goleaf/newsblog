<?php

namespace App\Http\Controllers;

use App\Models\Series;
use App\Services\SeriesNavigationService;

class SeriesController extends Controller
{
    public function __construct(
        private SeriesNavigationService $seriesNavigationService
    ) {}

    /**
     * Display a listing of all series.
     */
    public function index()
    {
        $series = Series::withCount('posts')
            ->with(['posts' => function ($query) {
                $query->where('status', 'published')
                    ->select('posts.id', 'posts.featured_image', 'posts.reading_time')
                    ->orderBy('post_series.order')
                    ->limit(1);
            }])
            ->latest()
            ->paginate(15);

        // Calculate total reading time for each series
        $series->each(function ($item) {
            $item->total_reading_time = $item->posts()
                ->where('status', 'published')
                ->sum('reading_time');
            
            // Get first post's featured image as series thumbnail
            $firstPost = $item->posts->first();
            $item->thumbnail = $firstPost?->featured_image_url;
        });

        return view('series.index', compact('series'));
    }

    /**
     * Display the specified series.
     */
    public function show(string $slug)
    {
        $series = Series::where('slug', $slug)->firstOrFail();

        $series->load(['posts' => function ($query) {
            $query->where('status', 'published')
                ->with(['user', 'category']);
        }]);

        // Calculate total reading time
        $totalReadingTime = $series->posts->sum('reading_time');
        
        // Get user's read posts for this series (from localStorage or session)
        $readPosts = [];
        if (auth()->check()) {
            // For authenticated users, we'll track in localStorage via JavaScript
            // The controller just provides the data structure
            $readPosts = session()->get('series_progress.' . $series->id, []);
        }
        
        // Calculate completion percentage
        $totalPosts = $series->posts->count();
        $readCount = count($readPosts);
        $completionPercentage = $totalPosts > 0 ? round(($readCount / $totalPosts) * 100) : 0;

        // Get related series (other series, limit to 3)
        $relatedSeries = Series::where('id', '!=', $series->id)
            ->withCount('posts')
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'posts_count' => $item->posts_count,
                ];
            });

        return view('series.show', compact('series', 'totalReadingTime', 'readPosts', 'completionPercentage', 'relatedSeries'));
    }
}

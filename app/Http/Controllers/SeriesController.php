<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
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
                $query->where('status', PostStatus::Published)
                    ->select('posts.id', 'posts.series_id', 'posts.featured_image', 'posts.reading_time', 'posts.order_in_series')
                    ->orderBy('order_in_series')
                    ->limit(1);
            }])
            ->latest()
            ->paginate(15);

        // Prepare thumbnail and total reading time per series item
        $series->each(function ($item) {
            // Compute total reading time from published posts to ensure accuracy
            $item->total_reading_time = $item->posts()
                ->where('status', PostStatus::Published)
                ->sum('reading_time');

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
            $query->where('status', PostStatus::Published)
                ->with(['user', 'category'])
                ->orderBy('order_in_series');
        }]);

        // Calculate total reading time
        $totalReadingTime = $series->posts->sum('reading_time');

        // Get user's read posts for this series (from localStorage or session)
        $readPosts = [];
        if (auth()->check()) {
            // For authenticated users, we'll track in localStorage via JavaScript
            // The controller just provides the data structure
            $readPosts = session()->get('series_progress.'.$series->id, []);
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

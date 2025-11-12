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
        $series = Series::withCount('posts')->latest()->paginate(15);

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

        return view('series.show', compact('series'));
    }
}

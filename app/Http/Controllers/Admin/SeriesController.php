<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $series = Series::withCount('posts')->latest()->paginate(15);

        return view('admin.series.index', compact('series'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.series.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:series,slug',
            'description' => 'nullable|string',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $series = Series::create($validated);

        return redirect()->route('admin.series.edit', $series)
            ->with('success', 'Series created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Series $series)
    {
        $series->load(['posts' => function ($query) {
            $query->with(['user', 'category']);
        }]);

        return view('admin.series.show', compact('series'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Series $series)
    {
        $series->load(['posts' => function ($query) {
            $query->with(['user', 'category']);
        }]);

        $availablePosts = Post::whereDoesntHave('series', function ($query) use ($series) {
            $query->where('series_id', $series->id);
        })->where('status', 'published')->get();

        return view('admin.series.edit', compact('series', 'availablePosts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:series,slug,'.$series->id,
            'description' => 'nullable|string',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $series->update($validated);

        return redirect()->route('admin.series.edit', $series)
            ->with('success', 'Series updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Series $series)
    {
        $series->delete();

        return redirect()->route('admin.series.index')
            ->with('success', 'Series deleted successfully.');
    }

    /**
     * Add a post to the series.
     */
    public function addPost(Request $request, Series $series)
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'order' => 'nullable|integer|min:0',
        ]);

        $order = $validated['order'] ?? $series->posts()->count();

        $series->posts()->attach($validated['post_id'], ['order' => $order]);

        return redirect()->route('admin.series.edit', $series)
            ->with('success', 'Post added to series successfully.');
    }

    /**
     * Remove a post from the series.
     */
    public function removePost(Series $series, Post $post)
    {
        $series->posts()->detach($post->id);

        return redirect()->route('admin.series.edit', $series)
            ->with('success', 'Post removed from series successfully.');
    }

    /**
     * Update the order of posts in the series.
     */
    public function updateOrder(Request $request, Series $series)
    {
        $validated = $request->validate([
            'posts' => 'required|array',
            'posts.*.id' => 'required|exists:posts,id',
            'posts.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['posts'] as $postData) {
            $series->posts()->updateExistingPivot($postData['id'], ['order' => $postData['order']]);
        }

        return response()->json(['success' => true]);
    }
}

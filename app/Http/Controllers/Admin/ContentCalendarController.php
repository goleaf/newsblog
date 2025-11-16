<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\GetPostsForDateRequest;
use App\Http\Requests\Admin\Calendar\ShowCalendarRequest;
use App\Http\Requests\Admin\Calendar\UpdatePostDateRequest;
use App\Models\Post;
use Carbon\Carbon;

class ContentCalendarController extends Controller
{
    /**
     * Display the content calendar view.
     */
    public function index(ShowCalendarRequest $request)
    {
        // Get the requested month and year, default to current
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        // Create a Carbon instance for the first day of the month
        $date = Carbon::create($year, $month, 1);

        // Get posts for the entire month
        $posts = Post::query()
            ->with(['user', 'category'])
            ->whereYear('published_at', $year)
            ->whereMonth('published_at', $month)
            ->orWhere(function ($query) use ($year, $month) {
                $query->whereYear('scheduled_at', $year)
                    ->whereMonth('scheduled_at', $month);
            })
            ->get()
            ->groupBy(function ($post) {
                // Group by the date (published_at or scheduled_at)
                $date = $post->status === 'scheduled' && $post->scheduled_at
                    ? $post->scheduled_at
                    : $post->published_at;

                return $date ? $date->format('Y-m-d') : null;
            })
            ->filter(fn ($group, $key) => $key !== null);

        return view('admin.calendar.index', compact('date', 'posts'));
    }

    /**
     * Get posts for a specific date (AJAX endpoint).
     */
    public function getPostsForDate(GetPostsForDateRequest $request)
    {
        $date = $request->input('date');

        $posts = Post::query()
            ->with(['user', 'category'])
            ->where(function ($query) use ($date) {
                $query->whereDate('published_at', $date)
                    ->orWhereDate('scheduled_at', $date);
            })
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'status' => $post->status,
                    'author' => $post->user->name,
                    'category' => $post->category->name ?? 'Uncategorized',
                    'published_at' => $post->published_at?->format('Y-m-d H:i'),
                    'scheduled_at' => $post->scheduled_at?->format('Y-m-d H:i'),
                    'edit_url' => '/nova/resources/posts/'.$post->id.'/edit',
                ];
            });

        return response()->json($posts);
    }

    /**
     * Update post date via drag-and-drop.
     */
    public function updatePostDate(UpdatePostDateRequest $request, Post $post)
    {
        $validated = $request->validated();

        $newDate = Carbon::parse($validated['date']);

        // Determine which field to update based on post status
        if ($post->status === 'scheduled') {
            // Update scheduled_at for scheduled posts
            $post->scheduled_at = $newDate->setTime(
                $post->scheduled_at?->hour ?? 9,
                $post->scheduled_at?->minute ?? 0
            );
        } elseif ($post->status === 'published') {
            // Update published_at for published posts
            $post->published_at = $newDate->setTime(
                $post->published_at?->hour ?? 9,
                $post->published_at?->minute ?? 0
            );
        } else {
            // For draft posts, set scheduled_at and change status to scheduled
            $post->scheduled_at = $newDate->setTime(9, 0);
            $post->status = 'scheduled';
        }

        $post->save();

        return response()->json([
            'success' => true,
            'message' => __('calendar.post_date_updated'),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status,
                'published_at' => $post->published_at?->format('Y-m-d H:i'),
                'scheduled_at' => $post->scheduled_at?->format('Y-m-d H:i'),
            ],
        ]);
    }
}

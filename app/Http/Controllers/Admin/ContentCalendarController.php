<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Calendar\GetPostsForDateRequest;
use App\Http\Requests\Admin\Calendar\ShowCalendarRequest;
use App\Http\Requests\Admin\Calendar\UpdatePostDateRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;

class ContentCalendarController extends Controller
{
    /**
     * Display the content calendar view.
     */
    public function index(ShowCalendarRequest $request)
    {
        // Get the requested period and filters
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $view = $request->input('view', 'month');
        $authorId = $request->input('author');
        $categoryId = $request->input('category');

        // Determine the date anchor and range based on view
        if ($view === 'week') {
            $anchor = $request->filled('date') ? Carbon::parse($request->input('date')) : now();
            $date = $anchor->copy();
            $rangeStart = $anchor->copy()->startOfWeek();
            $rangeEnd = $anchor->copy()->endOfWeek();
        } elseif ($view === 'day') {
            $anchor = $request->filled('date') ? Carbon::parse($request->input('date')) : now();
            $date = $anchor->copy();
            $rangeStart = $anchor->copy()->startOfDay();
            $rangeEnd = $anchor->copy()->endOfDay();
        } else { // month
            $date = Carbon::create($year, $month, 1);
            $rangeStart = $date->copy()->startOfMonth();
            $rangeEnd = $date->copy()->endOfMonth();
            $view = 'month';
        }

        // Build base query within range
        $query = Post::query()
            ->with(['user', 'category'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('published_at', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('scheduled_at', [$rangeStart, $rangeEnd]);
            })
            ->when($authorId, fn ($q) => $q->where('user_id', $authorId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

        $posts = $query->get()
            ->groupBy(function ($post) {
                $date = $post->status === 'scheduled' && $post->scheduled_at
                    ? $post->scheduled_at
                    : $post->published_at;
                return $date ? $date->format('Y-m-d') : null;
            })
            ->filter(fn ($group, $key) => $key !== null);

        // Load filter options
        $authors = User::query()
            ->whereIn('role', ['admin', 'editor', 'author'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        // Simple stats for the header
        $totalPosts = $posts->flatten(1)->count();
        $publishedCount = $posts->flatten(1)->where('status', PostStatus::Published)->count();
        $scheduledCount = $posts->flatten(1)->where('status', PostStatus::Scheduled)->count();
        $gapDays = $this->countGapDays($rangeStart, $rangeEnd, $posts);

        return view('admin.calendar.index', compact(
            'date', 'posts', 'authors', 'categories', 'authorId', 'categoryId', 'view',
            'totalPosts', 'publishedCount', 'scheduledCount', 'rangeStart', 'rangeEnd', 'gapDays'
        ));
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
        if ($post->status === PostStatus::Scheduled) {
            // Update scheduled_at for scheduled posts
            $post->scheduled_at = $newDate->setTime(
                $post->scheduled_at?->hour ?? 9,
                $post->scheduled_at?->minute ?? 0
            );
        } elseif ($post->status === PostStatus::Published) {
            // Update published_at for published posts
            $post->published_at = $newDate->setTime(
                $post->published_at?->hour ?? 9,
                $post->published_at?->minute ?? 0
            );
        } else {
            // For draft posts, set scheduled_at and change status to scheduled
            $post->scheduled_at = $newDate->setTime(9, 0);
            $post->status = PostStatus::Scheduled;
        }

        $post->save();

        // Notify assigned author about schedule change
        try {
            app(\App\Services\NotificationService::class)->create(
                user: $post->user,
                type: 'post_rescheduled',
                title: 'Post rescheduled',
                message: '"'.$post->title.'" has been rescheduled to '.$newDate->toDateString(),
                actionUrl: url('/nova/resources/posts/'.$post->id.'/edit'),
                icon: 'calendar-days',
                data: [
                    'post_id' => $post->id,
                    'new_date' => $newDate->toDateString(),
                ]
            );
        } catch (\Throwable $e) {
            // Ignore notification failures to not block scheduling
        }

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

    /**
     * Export calendar as iCal (ICS) for the given month and filters.
     */
    public function exportIcs(ShowCalendarRequest $request)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $view = $request->input('view', 'month');
        $authorId = $request->input('author');
        $categoryId = $request->input('category');

        if ($view === 'week') {
            $anchor = $request->filled('date') ? Carbon::parse($request->input('date')) : now();
            $rangeStart = $anchor->copy()->startOfWeek();
            $rangeEnd = $anchor->copy()->endOfWeek();
        } elseif ($view === 'day') {
            $anchor = $request->filled('date') ? Carbon::parse($request->input('date')) : now();
            $rangeStart = $anchor->copy()->startOfDay();
            $rangeEnd = $anchor->copy()->endOfDay();
        } else {
            $date = Carbon::create($year, $month, 1);
            $rangeStart = $date->copy()->startOfMonth();
            $rangeEnd = $date->copy()->endOfMonth();
        }

        $posts = Post::query()
            ->with(['user', 'category'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                $q->whereBetween('published_at', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('scheduled_at', [$rangeStart, $rangeEnd]);
            })
            ->when($authorId, fn ($q) => $q->where('user_id', $authorId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->get();

        $site = parse_url(config('app.url', 'https://newsblog.local'), PHP_URL_HOST) ?: 'newsblog.local';

        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//'.$site.'//Content Calendar//EN';

        foreach ($posts as $post) {
            $dt = $post->status === PostStatus::Scheduled && $post->scheduled_at
                ? $post->scheduled_at
                : $post->published_at;

            if (! $dt) {
                continue;
            }

            $startUtc = $dt->copy()->utc();
            $endUtc = $startUtc->copy()->addHour();

            $summary = $this->escapeIcsText($post->title);
            $statusText = $post->status->value ?? (string) $post->status;
            $categoryName = $post->category->name ?? 'Uncategorized';
            $authorName = $post->user->name ?? 'Unknown';
            $desc = $this->escapeIcsText("Status: {$statusText}\\nCategory: {$categoryName}\\nAuthor: {$authorName}");
            $url = $post->published_at ? route('post.show', $post->slug) : url('/');

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:post-'.$post->id.'@'.$site;
            $lines[] = 'DTSTAMP:'.$startUtc->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.$startUtc->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.$endUtc->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$summary;
            $lines[] = 'DESCRIPTION:'.$desc;
            $lines[] = 'URL:'.$url;
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';
        $body = implode("\r\n", $lines)."\r\n";

        return response($body, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="content-calendar.ics"',
        ]);
    }

    private function escapeIcsText(string $text): string
    {
        $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);

        return $text;
    }

    private function countGapDays(Carbon $start, Carbon $end, $grouped)
    {
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor <= $end) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }
        $gap = 0;
        foreach ($dates as $d) {
            if (! $grouped->has($d) || $grouped->get($d)->isEmpty()) {
                $gap++;
            }
        }
        return $gap;
    }
}

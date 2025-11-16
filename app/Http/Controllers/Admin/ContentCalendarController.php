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
        // Get the requested month and year, default to current
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $authorId = $request->input('author');
        $categoryId = $request->input('category');

        // Create a Carbon instance for the first day of the month
        $date = Carbon::create($year, $month, 1);

        // Build base query for posts within the month (published or scheduled)
        $query = Post::query()
            ->with(['user', 'category'])
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($q2) use ($year, $month) {
                    $q2->whereYear('published_at', $year)
                        ->whereMonth('published_at', $month);
                })->orWhere(function ($q3) use ($year, $month) {
                    $q3->whereYear('scheduled_at', $year)
                        ->whereMonth('scheduled_at', $month);
                });
            })
            ->when($authorId, fn ($q) => $q->where('user_id', $authorId))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId));

        $posts = $query
            ->get()
            ->groupBy(function ($post) {
                // Group by the date (published_at or scheduled_at)
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

        return view('admin.calendar.index', compact('date', 'posts', 'authors', 'categories', 'authorId', 'categoryId'));
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
        $authorId = $request->input('author');
        $categoryId = $request->input('category');

        $posts = Post::query()
            ->with(['user', 'category'])
            ->where(function ($q) use ($year, $month) {
                $q->where(function ($q2) use ($year, $month) {
                    $q2->whereYear('published_at', $year)
                        ->whereMonth('published_at', $month);
                })->orWhere(function ($q3) use ($year, $month) {
                    $q3->whereYear('scheduled_at', $year)
                        ->whereMonth('scheduled_at', $month);
                });
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
            'Content-Disposition' => 'attachment; filename="content-calendar-'.$year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.ics"',
        ]);
    }

    private function escapeIcsText(string $text): string
    {
        $text = str_replace(['\\', ';', ',', "\n", "\r"], ['\\\\', '\\;', '\\,', '\\n', ''], $text);

        return $text;
    }
}

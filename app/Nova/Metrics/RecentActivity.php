<?php

namespace App\Nova\Metrics;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Table;

class RecentActivity extends Table
{
    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Recent Activity';
    }

    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): mixed
    {
        $activities = [];

        // Get recent posts
        $recentPosts = Post::with('author')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($post) {
                return [
                    'type' => 'Post',
                    'description' => "{$post->author->name} published: {$post->title}",
                    'time' => $post->created_at->diffForHumans(),
                    'timestamp' => $post->created_at->timestamp,
                ];
            });

        // Get recent comments
        $recentComments = Comment::with(['user', 'post'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($comment) {
                return [
                    'type' => 'Comment',
                    'description' => "{$comment->user->name} commented on: {$comment->post->title}",
                    'time' => $comment->created_at->diffForHumans(),
                    'timestamp' => $comment->created_at->timestamp,
                ];
            });

        // Get recent users
        $recentUsers = User::latest()
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'User',
                    'description' => "New user registered: {$user->name}",
                    'time' => $user->created_at->diffForHumans(),
                    'timestamp' => $user->created_at->timestamp,
                ];
            });

        // Merge and sort by timestamp
        $activities = collect()
            ->merge($recentPosts)
            ->merge($recentComments)
            ->merge($recentUsers)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();

        return $activities->map(function ($activity) {
            return [
                $activity['type'],
                $activity['description'],
                $activity['time'],
            ];
        })->all();
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return now()->addMinutes(2);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'recent-activity';
    }
}

<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Article;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ActivityService
{
    /**
     * Record a user activity
     */
    public function record(User $user, string $verb, Model $subject, ?array $meta = null): Activity
    {
        return Activity::create([
            'actor_id' => $user->id,
            'verb' => $verb,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'meta' => $meta,
        ]);
    }

    /**
     * Record article publication activity
     */
    public function recordArticlePublished(Article $article): Activity
    {
        return $this->record(
            $article->author,
            'published_article',
            $article,
            [
                'title' => $article->title,
                'category' => $article->category->name ?? null,
            ]
        );
    }

    /**
     * Record post publication activity
     */
    public function recordPostPublished(Post $post): Activity
    {
        return $this->record(
            $post->user,
            'published_post',
            $post,
            [
                'title' => $post->title,
                'category' => $post->category->name ?? null,
            ]
        );
    }

    /**
     * Record comment activity
     */
    public function recordComment(Comment $comment): Activity
    {
        $subject = $comment->post ?? $comment->article;

        return $this->record(
            $comment->user,
            'commented',
            $subject,
            [
                'comment_id' => $comment->id,
                'content_preview' => substr(strip_tags($comment->content), 0, 100),
            ]
        );
    }

    /**
     * Record bookmark activity
     */
    public function recordBookmark(Bookmark $bookmark): Activity
    {
        $subject = $bookmark->post ?? $bookmark->article;

        return $this->record(
            $bookmark->user,
            'bookmarked',
            $subject,
            [
                'title' => $subject->title,
            ]
        );
    }

    /**
     * Record follow activity
     */
    public function recordFollow(Follow $follow): Activity
    {
        return $this->record(
            $follow->follower,
            'followed',
            $follow->followed,
            [
                'followed_name' => $follow->followed->name,
            ]
        );
    }

    /**
     * Generate activity feed for a specific user
     */
    public function getUserActivityFeed(User $user, int $limit = 20): Collection
    {
        return Activity::query()
            ->where('actor_id', $user->id)
            ->with(['subject'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($activity) => $this->formatActivity($activity));
    }

    /**
     * Generate activity feed for users that the given user follows
     */
    public function getFollowingActivityFeed(User $user, int $limit = 20): Collection
    {
        $followingIds = Follow::where('follower_id', $user->id)
            ->pluck('followed_id');

        return Activity::query()
            ->whereIn('actor_id', $followingIds)
            ->with(['subject'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($activity) => $this->formatActivity($activity));
    }

    /**
     * Filter activities by type
     */
    public function filterByType(Collection $activities, string $type): Collection
    {
        return $activities->filter(fn ($activity) => $activity['verb'] === $type);
    }

    /**
     * Aggregate similar activities
     * Groups activities of the same type by the same user within a time window
     */
    public function aggregateSimilarActivities(Collection $activities, int $timeWindowMinutes = 60): Collection
    {
        $aggregated = collect();
        $grouped = $activities->groupBy(function ($activity) use ($timeWindowMinutes) {
            $timestamp = floor($activity['created_at']->timestamp / ($timeWindowMinutes * 60));

            return $activity['actor_id'].'_'.$activity['verb'].'_'.$timestamp;
        });

        foreach ($grouped as $group) {
            if ($group->count() > 1) {
                $first = $group->first();
                $aggregated->push([
                    'id' => $first['id'],
                    'actor_id' => $first['actor_id'],
                    'actor_name' => $first['actor_name'],
                    'actor_avatar' => $first['actor_avatar'],
                    'verb' => $first['verb'],
                    'subject_type' => $first['subject_type'],
                    'subjects' => $group->pluck('subject')->toArray(),
                    'count' => $group->count(),
                    'created_at' => $first['created_at'],
                    'is_aggregated' => true,
                ]);
            } else {
                $aggregated->push($group->first());
            }
        }

        return $aggregated->sortByDesc('created_at')->values();
    }

    /**
     * Format activity for display
     */
    protected function formatActivity(Activity $activity): array
    {
        $actor = User::find($activity->actor_id);

        return [
            'id' => $activity->id,
            'actor_id' => $activity->actor_id,
            'actor_name' => $actor?->name,
            'actor_avatar' => $actor?->avatar,
            'verb' => $activity->verb,
            'subject_type' => $activity->subject_type,
            'subject' => $this->formatSubject($activity->subject, $activity->verb),
            'meta' => $activity->meta,
            'created_at' => $activity->created_at,
            'is_aggregated' => false,
        ];
    }

    /**
     * Format subject based on type
     */
    protected function formatSubject($subject, string $verb): ?array
    {
        if (! $subject) {
            return null;
        }

        $formatted = [
            'id' => $subject->id,
            'type' => class_basename($subject),
        ];

        if ($subject instanceof Article || $subject instanceof Post) {
            $formatted['title'] = $subject->title;
            $formatted['slug'] = $subject->slug;
            $formatted['excerpt'] = $subject->excerpt;
            $formatted['url'] = $subject instanceof Article
                ? route('articles.show', $subject->slug)
                : route('post.show', $subject->slug);
        } elseif ($subject instanceof User) {
            $formatted['name'] = $subject->name;
            $formatted['avatar'] = $subject->avatar;
            $formatted['url'] = route('users.show', $subject);
        }

        return $formatted;
    }
}

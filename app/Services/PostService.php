<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Str;

class PostService
{
    /**
     * Generate a unique slug from the given title.
     */
    public function generateUniqueSlug(string $title, ?int $excludePostId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludePostId)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists.
     */
    protected function slugExists(string $slug, ?int $excludePostId = null): bool
    {
        $query = Post::where('slug', $slug);

        if ($excludePostId) {
            $query->where('id', '!=', $excludePostId);
        }

        return $query->exists();
    }

    /**
     * Calculate reading time based on word count (200 words per minute).
     */
    public function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return (int) ceil($wordCount / 200);
    }

    /**
     * Create a new post with automatic slug and reading time generation.
     */
    public function createPost(array $data): Post
    {
        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title']);
        } else {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        }

        // Calculate reading time if content is provided
        if (! empty($data['content']) && empty($data['reading_time'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }

        // Handle post scheduling
        $data = $this->handleScheduling($data);

        return Post::create($data);
    }

    /**
     * Update an existing post with automatic slug and reading time updates.
     */
    public function updatePost(Post $post, array $data): Post
    {
        // Update slug if title changed and slug not manually provided
        if (isset($data['title']) && $data['title'] !== $post->title && empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $post->id);
        } elseif (! empty($data['slug']) && $data['slug'] !== $post->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $post->id);
        }

        // Recalculate reading time if content changed
        if (isset($data['content']) && $data['content'] !== $post->content) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }

        // Handle post scheduling
        $data = $this->handleScheduling($data, $post);

        $post->update($data);

        return $post->fresh();
    }

    /**
     * Handle post scheduling logic and status management.
     */
    protected function handleScheduling(array $data, ?Post $post = null): array
    {
        // If scheduled_at is set and in the future, set status to scheduled
        if (! empty($data['scheduled_at'])) {
            $scheduledAt = is_string($data['scheduled_at'])
                ? \Carbon\Carbon::parse($data['scheduled_at'])
                : $data['scheduled_at'];

            if ($scheduledAt->isFuture()) {
                $data['status'] = 'scheduled';
                // Clear published_at for scheduled posts
                $data['published_at'] = null;
            } elseif ($scheduledAt->isPast() && (! $post || $post->status === 'scheduled')) {
                // If scheduled time is in the past and post is scheduled, publish it
                $data['status'] = 'published';
                $data['published_at'] = $scheduledAt;
            }
        }

        // If status is being set to published and no published_at, set it to now
        if (isset($data['status']) && $data['status'] === 'published') {
            if (empty($data['published_at']) && (! $post || ! $post->published_at)) {
                $data['published_at'] = now();
            }
            // Clear scheduled_at when publishing
            if (! isset($data['scheduled_at'])) {
                $data['scheduled_at'] = null;
            }
        }

        // If status is being set to draft or archived, clear published_at and scheduled_at
        if (isset($data['status']) && in_array($data['status'], ['draft', 'archived'])) {
            if (! isset($data['published_at'])) {
                $data['published_at'] = null;
            }
            if (! isset($data['scheduled_at'])) {
                $data['scheduled_at'] = null;
            }
        }

        return $data;
    }

    /**
     * Publish a post immediately.
     */
    public function publishPost(Post $post): Post
    {
        $post->update([
            'status' => 'published',
            'published_at' => now(),
            'scheduled_at' => null,
        ]);

        return $post->fresh();
    }

    /**
     * Schedule a post for future publication.
     */
    public function schedulePost(Post $post, \DateTimeInterface $scheduledAt): Post
    {
        $scheduledAt = \Carbon\Carbon::parse($scheduledAt);

        if ($scheduledAt->isPast()) {
            throw new \InvalidArgumentException('Scheduled time must be in the future.');
        }

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'published_at' => null,
        ]);

        return $post->fresh();
    }

    /**
     * Unpublish a post (set to draft).
     */
    public function unpublishPost(Post $post): Post
    {
        $post->update([
            'status' => 'draft',
            'published_at' => null,
            'scheduled_at' => null,
        ]);

        return $post->fresh();
    }

    /**
     * Archive a post.
     */
    public function archivePost(Post $post): Post
    {
        $post->update([
            'status' => 'archived',
        ]);

        return $post->fresh();
    }

    /**
     * Get posts that are scheduled and ready to be published.
     */
    public function getPostsReadyToPublish(): \Illuminate\Database\Eloquent\Collection
    {
        return Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
    }

    /**
     * Publish scheduled posts that are ready.
     */
    public function publishScheduledPosts(): int
    {
        $posts = $this->getPostsReadyToPublish();
        $count = 0;

        foreach ($posts as $post) {
            $post->update([
                'status' => 'published',
                'published_at' => $post->scheduled_at ?? now(),
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Duplicate a post.
     */
    public function duplicatePost(Post $post): Post
    {
        $newPost = $post->replicate();
        $newPost->title = $post->title.' (Copy)';
        $newPost->slug = $this->generateUniqueSlug($newPost->title);
        $newPost->status = 'draft';
        $newPost->published_at = null;
        $newPost->scheduled_at = null;
        $newPost->view_count = 0;
        $newPost->save();

        // Duplicate tags relationship
        $newPost->tags()->sync($post->tags->pluck('id'));

        return $newPost;
    }

    /**
     * Bulk update post status.
     */
    public function bulkUpdateStatus(array $postIds, string $status): int
    {
        $data = ['status' => $status];

        // Set appropriate timestamps based on status
        if ($status === 'published') {
            $data['published_at'] = now();
            $data['scheduled_at'] = null;
        } elseif (in_array($status, ['draft', 'archived'])) {
            $data['published_at'] = null;
            $data['scheduled_at'] = null;
        }

        return Post::whereIn('id', $postIds)->update($data);
    }

    /**
     * Get post statistics.
     */
    public function getPostStatistics(Post $post): array
    {
        return [
            'view_count' => $post->view_count,
            'comment_count' => $post->comments()->count(),
            'bookmark_count' => $post->bookmarks()->count(),
            'reaction_count' => $post->reactions()->count(),
            'reading_time' => $post->reading_time,
            'word_count' => str_word_count(strip_tags($post->content)),
        ];
    }
}

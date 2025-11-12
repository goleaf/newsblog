<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Get related posts with enhanced fuzzy text matching algorithm.
     *
     * Scoring weights:
     * - Category match: 30%
     * - Tag match: 30%
     * - Fuzzy text similarity: 30% (title, excerpt, content)
     * - Publication date proximity: 10%
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        $cacheKey = "post.{$post->id}.related";
        $cacheTtl = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () use ($post, $limit) {
            // Ensure relationships are loaded
            $post->loadMissing(['category', 'tags']);

            // Get all published posts except the current one
            $candidates = Post::published()
                ->where('id', '!=', $post->id)
                ->with(['category', 'tags'])
                ->get();

            // Score each candidate post
            $scored = $candidates->map(function ($candidate) use ($post) {
                $score = 0;

                // Category match: 30%
                if ($candidate->category_id === $post->category_id) {
                    $score += 30;
                }

                // Tag match: 30%
                $postTagIds = $post->tags->pluck('id');
                $candidateTagIds = $candidate->tags->pluck('id');
                $sharedTags = $postTagIds->intersect($candidateTagIds)->count();
                $totalTags = max($postTagIds->count(), 1);
                $tagScore = ($sharedTags / $totalTags) * 30;
                $score += min($tagScore, 30); // Cap at 30%

                // Fuzzy text similarity: 30%
                $textScore = $this->calculateTextSimilarity($post, $candidate);
                $score += $textScore * 0.3; // Apply 30% weight

                // Publication date proximity: 10%
                if ($post->published_at && $candidate->published_at) {
                    $daysDiff = abs($post->published_at->diffInDays($candidate->published_at));
                    // Score decreases linearly over 90 days, max 10 points
                    $dateScore = max(0, 10 - ($daysDiff / 90) * 10);
                    $score += $dateScore;
                }

                return [
                    'post' => $candidate,
                    'score' => $score,
                ];
            });

            // Sort by score descending and return top N posts
            return $scored
                ->sortByDesc('score')
                ->take($limit)
                ->pluck('post');
        });
    }

    /**
     * Calculate fuzzy text similarity score between two posts (0-100).
     */
    protected function calculateTextSimilarity(Post $post1, Post $post2): float
    {
        $scores = [];

        // Title similarity (weight: 50%)
        if ($post1->title && $post2->title) {
            $titleScore = $this->calculateFuzzyScore($post1->title, $post2->title);
            $scores[] = ['score' => $titleScore, 'weight' => 0.5];
        }

        // Excerpt similarity (weight: 30%)
        if ($post1->excerpt && $post2->excerpt) {
            $excerptScore = $this->calculateFuzzyScore($post1->excerpt, $post2->excerpt);
            $scores[] = ['score' => $excerptScore, 'weight' => 0.3];
        }

        // Content similarity (weight: 20%) - use first 500 chars for performance
        if ($post1->content && $post2->content) {
            $content1 = Str::limit(strip_tags($post1->content), 500);
            $content2 = Str::limit(strip_tags($post2->content), 500);
            $contentScore = $this->calculateFuzzyScore($content1, $content2);
            $scores[] = ['score' => $contentScore, 'weight' => 0.2];
        }

        // Calculate weighted average
        if (empty($scores)) {
            return 0;
        }

        $totalWeight = array_sum(array_column($scores, 'weight'));
        $weightedSum = array_sum(array_map(function ($item) {
            return $item['score'] * $item['weight'];
        }, $scores));

        return $totalWeight > 0 ? ($weightedSum / $totalWeight) : 0;
    }

    /**
     * Calculate fuzzy match score between two strings using multiple algorithms.
     */
    protected function calculateFuzzyScore(string $text1, string $text2): float
    {
        $text1 = mb_strtolower(trim($text1));
        $text2 = mb_strtolower(trim($text2));

        // Exact match
        if ($text1 === $text2) {
            return 100.0;
        }

        // One contains the other
        if (str_contains($text1, $text2) || str_contains($text2, $text1)) {
            return 95.0;
        }

        // Use similar_text for similarity percentage
        $similarity = 0;
        similar_text($text1, $text2, $similarity);

        // Use Levenshtein distance for additional scoring
        $maxLength = max(mb_strlen($text1), mb_strlen($text2));
        if ($maxLength > 0) {
            $distance = levenshtein($text1, $text2);
            $levenshteinScore = max(0, 100 - (($distance / $maxLength) * 100));
        } else {
            $levenshteinScore = 0;
        }

        // Combine both scores (weighted average)
        $combinedScore = ($similarity * 0.6) + ($levenshteinScore * 0.4);

        // Check for word-level matches
        $words1 = array_filter(explode(' ', $text1), function ($word) {
            return mb_strlen($word) >= 3;
        });
        $words2 = array_filter(explode(' ', $text2), function ($word) {
            return mb_strlen($word) >= 3;
        });

        if (! empty($words1) && ! empty($words2)) {
            $commonWords = array_intersect($words1, $words2);
            $wordMatchScore = (count($commonWords) / max(count($words1), count($words2))) * 100;
            // Boost score if there are common words
            $combinedScore = max($combinedScore, $wordMatchScore * 0.8);
        }

        return min(100, max(0, $combinedScore));
    }

    /**
     * Invalidate related posts cache for a specific post.
     */
    public function invalidateRelatedPostsCache(Post $post): void
    {
        Cache::forget("post.{$post->id}.related");
    }

    /**
     * Invalidate related posts cache for posts in the same category.
     */
    public function invalidateRelatedPostsCacheByCategory(int $categoryId): void
    {
        $postIds = Post::where('category_id', $categoryId)->pluck('id');
        foreach ($postIds as $postId) {
            Cache::forget("post.{$postId}.related");
        }
    }

    /**
     * Invalidate related posts cache for posts with specific tags.
     */
    public function invalidateRelatedPostsCacheByTags(array $tagIds): void
    {
        $postIds = Post::whereHas('tags', function ($query) use ($tagIds) {
            $query->whereIn('tags.id', $tagIds);
        })->pluck('id');

        foreach ($postIds as $postId) {
            Cache::forget("post.{$postId}.related");
        }
    }
}

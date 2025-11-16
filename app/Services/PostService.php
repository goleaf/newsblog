<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        private PostRevisionService $revisionService
    ) {}

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
        // Create a revision before updating (Requirement 36.1)
        $this->revisionService->createRevision($post);

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
                $data['status'] = PostStatus::Scheduled;
                // Clear published_at for scheduled posts
                $data['published_at'] = null;
            } elseif ($scheduledAt->isPast() && (! $post || $post->status === PostStatus::Scheduled)) {
                // If scheduled time is in the past and post is scheduled, publish it
                $data['status'] = PostStatus::Published;
                $data['published_at'] = $scheduledAt;
            }
        }

        // If status is being set to published and no published_at, set it to now
        if (isset($data['status']) && $data['status'] === PostStatus::Published) {
            if (empty($data['published_at']) && (! $post || ! $post->published_at)) {
                $data['published_at'] = now();
            }
            // Clear scheduled_at when publishing
            if (! isset($data['scheduled_at'])) {
                $data['scheduled_at'] = null;
            }
        }

        // If status is being set to draft or archived, clear published_at and scheduled_at
        if (isset($data['status']) && in_array($data['status'], [PostStatus::Draft, PostStatus::Archived], true)) {
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
            'status' => PostStatus::Published,
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
            'status' => PostStatus::Scheduled,
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
            'status' => PostStatus::Draft,
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
            'status' => PostStatus::Archived,
        ]);

        return $post->fresh();
    }

    /**
     * Get posts that are scheduled and ready to be published.
     */
    public function getPostsReadyToPublish(): \Illuminate\Database\Eloquent\Collection
    {
        return Post::where('status', PostStatus::Scheduled)
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
                'status' => PostStatus::Published,
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
        $newPost->status = PostStatus::Draft;
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
        if ($status === PostStatus::Published) {
            $data['published_at'] = now();
            $data['scheduled_at'] = null;
        } elseif (in_array($status, [PostStatus::Draft, PostStatus::Archived], true)) {
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
     * Scoring weights (Requirement 13):
     * - Category match: 25%
     * - Tag match: 25%
     * - Fuzzy title similarity: 30%
     * - Fuzzy excerpt similarity: 20%
     * - Publication date proximity: 0% (removed to make room for fuzzy matching)
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        $cacheKey = "post.{$post->id}.related";
        $cacheTtl = 3600; // 1 hour (Requirement 13.4)

        return Cache::remember($cacheKey, $cacheTtl, function () use ($post, $limit) {
            // Ensure relationships are loaded
            $post->loadMissing(['category', 'tags']);

            // Get all published posts except the current one
            $candidates = Post::published()
                ->where('id', '!=', $post->id)
                ->with(['category', 'tags'])
                ->get();

            // If no candidates, return empty collection
            if ($candidates->isEmpty()) {
                return collect();
            }

            // Get FuzzySearchService instance
            $fuzzySearchService = app(FuzzySearchService::class);

            // Score each candidate post
            $scored = $candidates->map(function ($candidate) use ($post, $fuzzySearchService) {
                $score = 0;

                // Category match: 25%
                if ($candidate->category_id === $post->category_id) {
                    $score += 25;
                }

                // Tag match: 25%
                $postTagIds = $post->tags->pluck('id');
                $candidateTagIds = $candidate->tags->pluck('id');
                $sharedTags = $postTagIds->intersect($candidateTagIds)->count();
                $totalTags = max($postTagIds->count(), 1);
                $tagScore = ($sharedTags / $totalTags) * 25;
                $score += min($tagScore, 25); // Cap at 25%

                // Fuzzy title similarity: 30% (Requirement 13.1)
                if ($post->title && $candidate->title) {
                    $titleScore = $fuzzySearchService->calculateFuzzyScore(
                        $post->title,
                        $candidate->title
                    );
                    // Apply 30% weight
                    $score += ($titleScore / 100) * 30;
                }

                // Fuzzy excerpt similarity: 20% (Requirement 13.2)
                if ($post->excerpt && $candidate->excerpt) {
                    $excerptScore = $fuzzySearchService->calculateFuzzyScore(
                        $post->excerpt,
                        $candidate->excerpt
                    );
                    // Apply 20% weight
                    $score += ($excerptScore / 100) * 20;
                }

                return [
                    'post' => $candidate,
                    'score' => $score,
                ];
            });

            // Sort by score descending
            $sorted = $scored->sortByDesc('score');

            // Get posts with score above threshold
            $threshold = 20; // Minimum 20% match required
            $relatedPosts = $sorted
                ->filter(function ($item) use ($threshold) {
                    return $item['score'] >= $threshold;
                })
                ->take($limit)
                ->pluck('post');

            // Fallback to category-based algorithm if no fuzzy matches found (Requirement 13.5)
            if ($relatedPosts->isEmpty() && $post->category_id) {
                $relatedPosts = Post::published()
                    ->where('id', '!=', $post->id)
                    ->where('category_id', $post->category_id)
                    ->orderBy('published_at', 'desc')
                    ->limit($limit)
                    ->get();
            }

            return $relatedPosts;
        });
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

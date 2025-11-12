<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RelatedPostsService
{
    /**
     * Get related posts for a given post using weighted scoring algorithm.
     *
     * Scoring weights (Requirements 22.1, 22.2, 22.3):
     * - Same category: 40%
     * - Shared tags: 40%
     * - Publication date proximity: 20%
     *
     * @param  Post  $post  The post to find related posts for
     * @param  int  $limit  Maximum number of related posts to return (Requirement 22.4)
     * @return Collection Collection of related posts
     */
    public function getRelatedPosts(Post $post, int $limit = 4): Collection
    {
        $cacheKey = "related_posts.{$post->id}";
        $cacheTtl = 3600; // 1 hour (Requirement 22.5)

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

            // Score each candidate post
            $scored = $candidates->map(function ($candidate) use ($post) {
                $score = 0;

                // Same category: 40% (Requirement 22.1)
                if ($candidate->category_id === $post->category_id) {
                    $score += 40;
                }

                // Shared tags: 40% (Requirement 22.2)
                $postTagIds = $post->tags->pluck('id');
                $candidateTagIds = $candidate->tags->pluck('id');
                $sharedTags = $postTagIds->intersect($candidateTagIds)->count();

                if ($postTagIds->count() > 0) {
                    $tagScore = ($sharedTags / $postTagIds->count()) * 40;
                    $score += $tagScore;
                }

                // Publication date proximity: 20% (Requirement 22.3)
                if ($post->published_at && $candidate->published_at) {
                    $daysDiff = abs($candidate->published_at->diffInDays($post->published_at));
                    // Score decreases as days difference increases
                    // Full 20% for same day, decreasing to 0% at 30+ days
                    $dateScore = max(0, 20 - ($daysDiff / 30) * 20);
                    $score += $dateScore;
                }

                return [
                    'post' => $candidate,
                    'score' => $score,
                ];
            });

            // Sort by score descending and take the top results
            return $scored->sortByDesc('score')
                ->take($limit)
                ->pluck('post');
        });
    }

    /**
     * Invalidate related posts cache for a specific post.
     *
     * @param  Post  $post  The post whose cache should be invalidated
     */
    public function invalidateCache(Post $post): void
    {
        Cache::forget("related_posts.{$post->id}");
    }

    /**
     * Invalidate related posts cache for posts in the same category.
     *
     * @param  int  $categoryId  The category ID
     */
    public function invalidateCacheByCategory(int $categoryId): void
    {
        $postIds = Post::where('category_id', $categoryId)->pluck('id');

        foreach ($postIds as $postId) {
            Cache::forget("related_posts.{$postId}");
        }
    }

    /**
     * Invalidate related posts cache for posts with specific tags.
     *
     * @param  array  $tagIds  Array of tag IDs
     */
    public function invalidateCacheByTags(array $tagIds): void
    {
        $postIds = Post::whereHas('tags', function ($query) use ($tagIds) {
            $query->whereIn('tags.id', $tagIds);
        })->pluck('id');

        foreach ($postIds as $postId) {
            Cache::forget("related_posts.{$postId}");
        }
    }
}

<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Series;

class SeriesNavigationService
{
    /**
     * Get navigation data for a post within a series.
     */
    public function getNavigation(Post $post, Series $series): array
    {
        // Fetch ordered post IDs within the series (one-to-many)
        $postIds = $series->posts()
            ->orderBy('order_in_series')
            ->pluck('id')
            ->toArray();
        $currentIndex = array_search($post->id, $postIds);

        if ($currentIndex === false) {
            return [
                'previous' => null,
                'next' => null,
                'current_position' => 0,
                'total_posts' => count($postIds),
                'all_posts' => collect(),
            ];
        }

        // Load all posts for the series
        $allPosts = Post::whereIn('id', $postIds)
            ->select('id', 'title', 'slug', 'featured_image', 'reading_time')
            ->get();

        // Sort by order using array_search without storing closure in Collection
        $sortedPosts = [];
        foreach ($postIds as $postId) {
            $post = $allPosts->firstWhere('id', $postId);
            if ($post) {
                $sortedPosts[] = $post;
            }
        }
        $allPosts = collect($sortedPosts);

        $previousPost = null;
        $nextPost = null;

        if ($currentIndex > 0) {
            $previousPost = $allPosts[$currentIndex - 1];
        }

        if ($currentIndex < count($postIds) - 1) {
            $nextPost = $allPosts[$currentIndex + 1];
        }

        return [
            'previous' => $previousPost,
            'next' => $nextPost,
            'current_position' => $currentIndex + 1,
            'total_posts' => count($postIds),
            'all_posts' => $allPosts,
        ];
    }

    /**
     * Get all series for a post with navigation data.
     */
    public function getPostSeriesWithNavigation(Post $post): array
    {
        if (! $post->series) {
            return [];
        }

        return [[
            'series' => $post->series,
            'navigation' => $this->getNavigation($post, $post->series),
        ]];
    }
}

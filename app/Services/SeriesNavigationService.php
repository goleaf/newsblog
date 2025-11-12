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
        $posts = $series->posts()->pluck('posts.id', 'post_series.order')->toArray();

        // Sort by order
        ksort($posts);

        $postIds = array_values($posts);
        $currentIndex = array_search($post->id, $postIds);

        if ($currentIndex === false) {
            return [
                'previous' => null,
                'next' => null,
                'current_position' => 0,
                'total_posts' => count($postIds),
            ];
        }

        $previousPost = null;
        $nextPost = null;

        if ($currentIndex > 0) {
            $previousPost = Post::find($postIds[$currentIndex - 1]);
        }

        if ($currentIndex < count($postIds) - 1) {
            $nextPost = Post::find($postIds[$currentIndex + 1]);
        }

        return [
            'previous' => $previousPost,
            'next' => $nextPost,
            'current_position' => $currentIndex + 1,
            'total_posts' => count($postIds),
        ];
    }

    /**
     * Get all series for a post with navigation data.
     */
    public function getPostSeriesWithNavigation(Post $post): array
    {
        $seriesData = [];

        foreach ($post->series as $series) {
            $seriesData[] = [
                'series' => $series,
                'navigation' => $this->getNavigation($post, $series),
            ];
        }

        return $seriesData;
    }
}

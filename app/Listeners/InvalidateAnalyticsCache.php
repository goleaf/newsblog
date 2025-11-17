<?php

namespace App\Listeners;

use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Cache;

class InvalidateAnalyticsCache
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    /**
     * Handle post view events.
     */
    public function handlePostView($event): void
    {
        // Invalidate relevant caches when a post is viewed
        $this->invalidatePostCache($event->postId ?? null);
        $this->invalidateUserMetricsCache();
        $this->invalidateTrafficMetricsCache();
    }

    /**
     * Handle comment created events.
     */
    public function handleCommentCreated($event): void
    {
        $this->invalidatePostCache($event->comment->post_id ?? null);
        $this->invalidateUserMetricsCache();
    }

    /**
     * Handle reaction created events.
     */
    public function handleReactionCreated($event): void
    {
        $this->invalidatePostCache($event->reaction->post_id ?? null);
    }

    /**
     * Handle user registered events.
     */
    public function handleUserRegistered($event): void
    {
        $this->invalidateUserMetricsCache();
    }

    /**
     * Invalidate post-specific caches.
     */
    private function invalidatePostCache(?int $postId): void
    {
        if ($postId) {
            // Clear all article metrics for this post
            Cache::forget("analytics:article:{$postId}:*");
        }

        // Clear top articles cache
        Cache::forget('analytics:top_articles:*');
    }

    /**
     * Invalidate user metrics caches.
     */
    private function invalidateUserMetricsCache(): void
    {
        Cache::forget('analytics:users:*');
    }

    /**
     * Invalidate traffic metrics caches.
     */
    private function invalidateTrafficMetricsCache(): void
    {
        Cache::forget('analytics:traffic:*');
    }

    /**
     * Clear all analytics caches.
     */
    public function clearAll(): void
    {
        $this->analytics->clearCache();
    }
}

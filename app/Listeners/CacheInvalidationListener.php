<?php

namespace App\Listeners;

use App\Services\CacheService;
use App\Services\CategoryService;
use App\Services\PostService;
use App\Services\SearchService;
use App\Services\UserService;
use Illuminate\Support\Facades\Cache;

class CacheInvalidationListener
{
    public function __construct(
        protected CacheService $cacheService,
        protected PostService $postService,
        protected CategoryService $categoryService,
        protected UserService $userService,
        protected SearchService $searchService
    ) {}

    /**
     * Handle model saved events.
     */
    public function handleModelSaved(object $event): void
    {
        $model = $event->model ?? $event;

        // Invalidate caches based on model type
        match (get_class($model)) {
            \App\Models\Post::class => $this->invalidatePostCaches($model),
            \App\Models\Category::class => $this->invalidateCategoryCaches($model),
            \App\Models\Tag::class => $this->invalidateTagCaches($model),
            \App\Models\User::class => $this->invalidateUserCaches($model),
            \App\Models\Comment::class => $this->invalidateCommentCaches($model),
            default => null,
        };
    }

    /**
     * Handle model deleted events.
     */
    public function handleModelDeleted(object $event): void
    {
        $this->handleModelSaved($event);
    }

    /**
     * Invalidate post-related caches.
     */
    protected function invalidatePostCaches($post): void
    {
        // Invalidate popular and trending posts
        $this->postService->invalidatePopularPostsCache();

        // Invalidate related posts
        $this->postService->invalidateRelatedPostsCache($post);

        // Invalidate category caches if post has category
        if ($post->category_id) {
            $this->categoryService->invalidateCache();
        }
    }

    /**
     * Invalidate category-related caches.
     */
    protected function invalidateCategoryCaches($category): void
    {
        $this->categoryService->invalidateCache();
        $this->categoryService->invalidateCategoryCache($category);
    }

    /**
     * Invalidate tag-related caches.
     */
    protected function invalidateTagCaches($tag): void
    {
        Cache::forget("tag.id.{$tag->id}");
        Cache::forget("tag.slug.{$tag->slug}");
        Cache::forget('tags.popular');
    }

    /**
     * Invalidate user-related caches.
     */
    protected function invalidateUserCaches($user): void
    {
        $this->userService->invalidateUserCache($user);
        $this->userService->invalidateActiveAuthorsCache();
    }

    /**
     * Invalidate comment-related caches.
     */
    protected function invalidateCommentCaches($comment): void
    {
        // Invalidate post cache if comment belongs to a post
        if ($comment->post_id) {
            Cache::forget("post.{$comment->post_id}.comments");
        }
    }
}

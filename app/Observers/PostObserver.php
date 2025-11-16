<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\CacheService;
use App\Services\PostService;
use App\Services\SearchIndexService;

class PostObserver
{
    public function __construct(
        protected SearchIndexService $searchIndexService,
        protected PostService $postService,
        protected CacheService $cacheService
    ) {}

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->indexPost($post);

        // Invalidate category menu cache (affects post counts)
        if ($post->status === 'published') {
            \Cache::forget('category_menu');
        }

        // Invalidate homepage and category caches (Requirement 20.5)
        $this->cacheService->invalidateHomepage();
        if ($post->category_id) {
            $this->cacheService->invalidateCategory($post->category_id);
        }
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Reload with relationships for indexing
        $post->load(['user', 'category', 'tags']);

        $this->searchIndexService->updatePost($post);

        // Invalidate category menu cache if status or category changed (affects post counts)
        if ($post->isDirty(['status', 'category_id'])) {
            \Cache::forget('category_menu');
        }

        // Invalidate related posts cache if relevant fields changed
        $relatedPostsRelevantFields = ['title', 'excerpt', 'content', 'category_id', 'status', 'published_at'];
        if ($post->isDirty($relatedPostsRelevantFields)) {
            $this->postService->invalidateRelatedPostsCache($post);

            // Invalidate post view cache (Requirement 20.5)
            $this->cacheService->invalidatePostBySlug($post->slug);

            // Invalidate homepage cache (Requirement 20.5)
            $this->cacheService->invalidateHomepage();

            // Also invalidate cache for posts in the same category if category changed
            if ($post->isDirty('category_id')) {
                $oldCategoryId = $post->getOriginal('category_id');
                if ($oldCategoryId) {
                    $this->postService->invalidateRelatedPostsCacheByCategory($oldCategoryId);
                    $this->cacheService->invalidateCategory($oldCategoryId);
                }
                if ($post->category_id) {
                    $this->postService->invalidateRelatedPostsCacheByCategory($post->category_id);
                    $this->cacheService->invalidateCategory($post->category_id);
                }
            } elseif ($post->category_id) {
                // Invalidate current category cache even if not changed
                $this->cacheService->invalidateCategory($post->category_id);
            }
        }
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->searchIndexService->removePost($post->id);

        // Invalidate view caches (Requirement 20.5)
        $this->cacheService->invalidatePostBySlug($post->slug);
        $this->cacheService->invalidateHomepage();

        // Invalidate related posts cache for posts that might have been related to this one
        if ($post->category_id) {
            $this->postService->invalidateRelatedPostsCacheByCategory($post->category_id);
            $this->cacheService->invalidateCategory($post->category_id);
        }
        // Load tags before accessing them (in case they're not loaded)
        $post->load('tags');
        if ($post->tags->count() > 0) {
            $tagIds = $post->tags->pluck('id')->toArray();
            $this->postService->invalidateRelatedPostsCacheByTags($tagIds);
            foreach ($tagIds as $tagId) {
                $this->cacheService->invalidateTag($tagId);
            }
        }
    }

    /**
     * Handle the Post "restored" event.
     */
    public function restored(Post $post): void
    {
        $this->indexPost($post);
    }

    /**
     * Handle the Post "force deleted" event.
     */
    public function forceDeleted(Post $post): void
    {
        $this->searchIndexService->removePost($post->id);
    }

    /**
     * Index post if it's published
     */
    protected function indexPost(Post $post): void
    {
        // Reload with relationships for indexing
        $post->load(['user', 'category', 'tags']);

        $this->searchIndexService->indexPost($post);
    }
}

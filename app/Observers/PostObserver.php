<?php

namespace App\Observers;

use App\Models\Post;
use App\Services\PostService;
use App\Services\SearchIndexService;

class PostObserver
{
    public function __construct(
        protected SearchIndexService $searchIndexService,
        protected PostService $postService
    ) {}

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->indexPost($post);
    }

    /**
     * Handle the Post "updated" event.
     */
    public function updated(Post $post): void
    {
        // Reload with relationships for indexing
        $post->load(['user', 'category', 'tags']);

        $this->searchIndexService->updatePost($post);

        // Invalidate related posts cache if relevant fields changed
        $relatedPostsRelevantFields = ['title', 'excerpt', 'content', 'category_id', 'status', 'published_at'];
        if ($post->isDirty($relatedPostsRelevantFields)) {
            $this->postService->invalidateRelatedPostsCache($post);

            // Also invalidate cache for posts in the same category if category changed
            if ($post->isDirty('category_id')) {
                $oldCategoryId = $post->getOriginal('category_id');
                if ($oldCategoryId) {
                    $this->postService->invalidateRelatedPostsCacheByCategory($oldCategoryId);
                }
                if ($post->category_id) {
                    $this->postService->invalidateRelatedPostsCacheByCategory($post->category_id);
                }
            }
        }
    }

    /**
     * Handle the Post "deleted" event.
     */
    public function deleted(Post $post): void
    {
        $this->searchIndexService->removePost($post->id);

        // Invalidate related posts cache for posts that might have been related to this one
        if ($post->category_id) {
            $this->postService->invalidateRelatedPostsCacheByCategory($post->category_id);
        }
        // Load tags before accessing them (in case they're not loaded)
        $post->load('tags');
        if ($post->tags->count() > 0) {
            $tagIds = $post->tags->pluck('id')->toArray();
            $this->postService->invalidateRelatedPostsCacheByTags($tagIds);
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

<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\PostService;
use App\Services\SearchIndexService;

class TagObserver
{
    public function __construct(
        protected SearchIndexService $searchIndexService,
        protected PostService $postService
    ) {}

    /**
     * Handle the Tag "created" event.
     */
    public function created(Tag $tag): void
    {
        // No action needed - posts will be indexed when tag is attached
    }

    /**
     * Handle the Tag "updated" event.
     */
    public function updated(Tag $tag): void
    {
        // Invalidate tags index cache when tag is updated
        $this->searchIndexService->invalidateTagsCache();

        // Update indexes for all related posts when tag name/slug changes
        if ($tag->isDirty(['name', 'slug'])) {
            $this->updateRelatedPosts($tag);
        }
    }

    /**
     * Handle the Tag "deleting" event.
     */
    public function deleting(Tag $tag): void
    {
        // Update indexes for all related posts before tag is deleted
        // Use deleting instead of deleted to access relationships before deletion
        $this->updateRelatedPosts($tag);
    }

    /**
     * Handle the Tag "deleted" event.
     */
    public function deleted(Tag $tag): void
    {
        // Tag is already deleted, relationships are no longer accessible
        // Index updates were handled in deleting() event
    }

    /**
     * Handle the Tag "restored" event.
     */
    public function restored(Tag $tag): void
    {
        // Update indexes for all related posts when tag is restored
        $this->updateRelatedPosts($tag);
    }

    /**
     * Handle the Tag "force deleted" event.
     */
    public function forceDeleted(Tag $tag): void
    {
        // Force deleted - same as deleted, handled in deleting()
    }

    /**
     * Update search indexes for all posts related to this tag
     */
    protected function updateRelatedPosts(Tag $tag): void
    {
        // Invalidate related posts cache for all posts with this tag
        $this->postService->invalidateRelatedPostsCacheByTags([$tag->id]);

        $tag->posts()
            ->published()
            ->with(['user', 'category', 'tags'])
            ->chunk(100, function ($posts) {
                foreach ($posts as $post) {
                    $this->searchIndexService->updatePost($post);
                }
            });
    }
}

<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\PostService;
use App\Services\SearchIndexService;

class CategoryObserver
{
    public function __construct(
        protected SearchIndexService $searchIndexService,
        protected PostService $postService
    ) {}

    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        // No action needed - posts will be indexed when category is assigned
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        // Invalidate categories index cache when category is updated
        $this->searchIndexService->invalidateCategoriesCache();

        // Update indexes for all related posts when category name/slug/description changes
        if ($category->isDirty(['name', 'slug', 'description'])) {
            $this->updateRelatedPosts($category);
        }
    }

    /**
     * Handle the Category "deleting" event.
     */
    public function deleting(Category $category): void
    {
        // Update indexes for all related posts before category is deleted
        // Use deleting instead of deleted to access relationships before deletion
        $this->updateRelatedPosts($category);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        // Category is already deleted, relationships are no longer accessible
        // Index updates were handled in deleting() event
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        // Update indexes for all related posts when category is restored
        $this->updateRelatedPosts($category);
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        // Force deleted - same as deleted, handled in deleting()
    }

    /**
     * Update search indexes for all posts related to this category
     */
    protected function updateRelatedPosts(Category $category): void
    {
        // Invalidate related posts cache for all posts in this category
        $this->postService->invalidateRelatedPostsCacheByCategory($category->id);

        $category->posts()
            ->published()
            ->with(['user', 'category', 'tags'])
            ->chunk(100, function ($posts) {
                foreach ($posts as $post) {
                    $this->searchIndexService->updatePost($post);
                }
            });
    }
}

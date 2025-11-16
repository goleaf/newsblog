<?php

namespace App\Observers;

use App\Enums\PostStatus;
use App\Jobs\SendPostPublishedNotification;
use App\Models\Post;
use App\Services\AltTextValidator;
use App\Services\CacheService;
use App\Services\NotificationService;
use App\Services\PostService;
use App\Services\SearchIndexService;

class PostObserver
{
    public function __construct(
        protected SearchIndexService $searchIndexService,
        protected PostService $postService,
        protected CacheService $cacheService,
        protected NotificationService $notificationService,
        protected AltTextValidator $altTextValidator,
    ) {}

    /**
     * Handle the Post "creating" event.
     */
    public function creating(Post $post): void
    {
        // Generate unique slug if not provided
        if (empty($post->slug) && ! empty($post->title)) {
            $post->slug = $this->postService->generateUniqueSlug($post->title);
        }
    }

    /**
     * Handle the Post "saving" event.
     */
    public function saving(Post $post): void
    {
        // Calculate reading time if content is provided and reading_time is empty or content changed
        if (! empty($post->content)) {
            if (empty($post->reading_time) || $post->isDirty('content')) {
                $post->reading_time = $this->postService->calculateReadingTime($post->content);
            }

            // Accessibility: alt text validation on images within content (allow save with warnings)
            $report = $this->altTextValidator->scanHtml($post->content);
            if ($report->missingAltCount > 0) {
                // Best-effort user feedback via session warning
                if (function_exists('session')) {
                    session()->flash('warnings.alt_text', [
                        'missing' => $report->missingAltCount,
                        'total' => $report->totalImages,
                        'issues' => $report->issues,
                    ]);
                }
                \Log::warning('Alt text missing on images in post content', [
                    'post_id' => $post->id,
                    'missing' => $report->missingAltCount,
                    'total' => $report->totalImages,
                ]);
            }
        }
    }

    /**
     * Handle the Post "created" event.
     */
    public function created(Post $post): void
    {
        $this->indexPost($post);

        // Check if post is published and send notifications
        if ($post->status === PostStatus::Published) {
            $this->handlePostPublished($post);
        }

        // Invalidate category menu cache (affects post counts)
        if ($post->status === PostStatus::Published) {
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

        // Check if post was just published (status changed to published)
        if ($post->isDirty('status')) {
            $oldStatus = $post->getOriginal('status');
            $newStatus = $post->status;

            // Check if status changed from non-published to published
            if ($newStatus === PostStatus::Published && $oldStatus !== PostStatus::Published->value) {
                $this->handlePostPublished($post);
            }
        }

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
        // Remove from search index
        $this->searchIndexService->removePost($post->id);

        // Invalidate view caches (Requirement 20.5)
        $this->cacheService->invalidatePostBySlug($post->slug);
        $this->cacheService->invalidateHomepage();

        // Invalidate category menu cache
        \Cache::forget('category_menu');

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

        // Cleanup: Remove related data if needed
        // Note: Comments, views, bookmarks, etc. are handled by database foreign key constraints
        // or their own observers, so we don't need to manually delete them here
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
     * Handle post published event - send notifications and emails.
     */
    protected function handlePostPublished(Post $post): void
    {
        // Ensure relationships are loaded
        $post->loadMissing(['user', 'category']);

        // Queue email notification to post author
        if ($post->user && $post->user->email) {
            try {
                SendPostPublishedNotification::dispatch($post);
            } catch (\Exception $e) {
                // Log error but don't fail the operation
                \Log::error('Failed to queue post published email', [
                    'post_id' => $post->id,
                    'user_id' => $post->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create in-app notification for post author
        if ($post->user) {
            try {
                $this->notificationService->notifyPostPublished($post->user, $post);
            } catch (\Exception $e) {
                // Log error but don't fail the operation
                \Log::error('Failed to create post published notification', [
                    'post_id' => $post->id,
                    'user_id' => $post->user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
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

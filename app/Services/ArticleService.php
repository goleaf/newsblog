<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleService
{
    public function __construct(
        protected ImageProcessingService $imageProcessingService,
        protected CacheService $cacheService
    ) {}

    /**
     * Create a new article with author assignment.
     * Requirements: 1.3, 1.4
     */
    public function create(array $data, User $author): Article
    {
        // Assign author
        $data['user_id'] = $author->id;

        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title']);
        }

        // Calculate reading time
        if (! empty($data['content'])) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }

        // Process and store featured image
        if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
            $data['featured_image'] = $this->processFeaturedImage($data['featured_image']);
        }

        // Handle tags separately
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        // Handle status and published_at
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Create article
        $article = Article::create($data);

        // Attach tags
        if (! empty($tags)) {
            $article->tags()->sync($tags);
        }

        return $article;
    }

    /**
     * Update article with cache invalidation.
     * Requirements: 1.3, 1.4
     */
    public function update(Article $article, array $data): Article
    {
        // Update slug if title changed
        if (isset($data['title']) && $data['title'] !== $article->title && empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['title'], $article->id);
        }

        // Recalculate reading time if content changed
        if (isset($data['content']) && $data['content'] !== $article->content) {
            $data['reading_time'] = $this->calculateReadingTime($data['content']);
        }

        // Process and store featured image
        if (isset($data['featured_image']) && $data['featured_image'] instanceof UploadedFile) {
            // Delete old image if exists
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $data['featured_image'] = $this->processFeaturedImage($data['featured_image']);
        }

        // Handle tags separately
        $tags = $data['tags'] ?? null;
        unset($data['tags']);

        // Handle status and published_at
        if (isset($data['status']) && $data['status'] === 'published' && empty($article->published_at) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Update article
        $article->update($data);

        // Sync tags if provided
        if ($tags !== null) {
            $article->tags()->sync($tags);
        }

        // Invalidate caches
        $this->invalidateArticleCache($article);

        return $article->fresh();
    }

    /**
     * Publish article with notifications.
     * Requirements: 1.4
     */
    public function publish(Article $article): Article
    {
        $article->update([
            'status' => PostStatus::Published,
            'published_at' => now(),
        ]);

        // Invalidate caches
        $this->invalidateArticleCache($article);

        // TODO: Dispatch notification job when notification system is implemented
        // dispatch(new SendPostPublishedNotification($article));

        return $article->fresh();
    }

    /**
     * Unpublish article.
     * Requirements: 1.4
     */
    public function unpublish(Article $article): Article
    {
        $article->update([
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);

        // Invalidate caches
        $this->invalidateArticleCache($article);

        return $article->fresh();
    }

    /**
     * Calculate reading time based on word count (200 words per minute).
     * Requirements: 4.1
     */
    public function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return (int) ceil($wordCount / 200);
    }

    /**
     * Process and store featured image.
     * Requirements: 15.1
     */
    protected function processFeaturedImage(UploadedFile $image): string
    {
        // Use ImageProcessingService to optimize and store image
        return $this->imageProcessingService->processAndStore(
            $image,
            'articles/featured',
            [
                'resize' => ['width' => 1200, 'height' => 630],
                'optimize' => true,
                'webp' => true,
            ]
        );
    }

    /**
     * Generate a unique slug from the given title.
     * Requirements: 19.4
     */
    protected function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists.
     */
    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Article::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Track article view.
     * Requirements: 4.3, 8.1, 8.2
     */
    public function trackView(Article $article, Request $request): void
    {
        $sessionKey = 'article_viewed_'.$article->id;

        // Prevent duplicate tracking within session
        if ($request->session()->has($sessionKey)) {
            return;
        }

        // Mark as viewed in session
        $request->session()->put($sessionKey, true);

        // Increment view count
        $article->increment('view_count');

        // TODO: Create detailed view record when ArticleView model is implemented
        // ArticleView::create([
        //     'article_id' => $article->id,
        //     'user_id' => auth()->id(),
        //     'session_id' => $request->session()->getId(),
        //     'ip_address' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        //     'referrer' => $request->header('referer'),
        //     'viewed_at' => now(),
        // ]);
    }

    /**
     * Invalidate article-related caches.
     * Requirements: 15.2
     */
    protected function invalidateArticleCache(Article $article): void
    {
        // Invalidate article cache
        $this->cacheService->invalidatePost($article->id);
        $this->cacheService->invalidatePost($article->slug);

        // Invalidate homepage cache
        $this->cacheService->invalidateHomepage();

        // Invalidate category cache
        if ($article->category_id) {
            $this->cacheService->invalidateCategory($article->category_id);
        }
    }
}

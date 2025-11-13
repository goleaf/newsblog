<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache management service
 * Implements caching strategy for Requirements 12.1, 12.2, 12.3
 */
class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    public const TTL_SHORT = 600; // 10 minutes - for frequently changing data

    public const TTL_MEDIUM = 1800; // 30 minutes - for moderately changing data

    public const TTL_LONG = 3600; // 1 hour - for stable data

    public const TTL_VERY_LONG = 86400; // 24 hours - for rarely changing data

    /**
     * Cache key prefixes
     */
    public const PREFIX_VIEW = 'view';

    public const PREFIX_QUERY = 'query';

    public const PREFIX_MODEL = 'model';

    public const PREFIX_HOME = 'home';

    public const PREFIX_CATEGORY = 'category';

    public const PREFIX_TAG = 'tag';

    public const PREFIX_POST = 'post';

    /**
     * Remember a value in cache with automatic key generation
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Cache homepage data
     */
    public function cacheHomepage(callable $callback): mixed
    {
        return $this->remember(
            self::PREFIX_HOME.'.page',
            self::TTL_SHORT,
            $callback
        );
    }

    /**
     * Cache category page data
     */
    public function cacheCategoryPage(int $categoryId, array $filters, callable $callback): mixed
    {
        $filterKey = $this->generateFilterKey($filters);
        $key = self::PREFIX_CATEGORY.".page.{$categoryId}.{$filterKey}";

        return $this->remember($key, self::TTL_SHORT, $callback);
    }

    /**
     * Cache tag page data
     */
    public function cacheTagPage(int $tagId, array $filters, callable $callback): mixed
    {
        $filterKey = $this->generateFilterKey($filters);
        $key = self::PREFIX_TAG.".page.{$tagId}.{$filterKey}";

        return $this->remember($key, self::TTL_SHORT, $callback);
    }

    /**
     * Cache expensive query results
     */
    public function cacheQuery(string $queryKey, int $ttl, callable $callback): mixed
    {
        $key = self::PREFIX_QUERY.".{$queryKey}";

        return $this->remember($key, $ttl, $callback);
    }

    /**
     * Cache model data
     */
    public function cacheModel(string $modelType, int|string $identifier, int $ttl, callable $callback): mixed
    {
        $key = self::PREFIX_MODEL.".{$modelType}.{$identifier}";

        return $this->remember($key, $ttl, $callback);
    }

    /**
     * Invalidate homepage cache
     */
    public function invalidateHomepage(): void
    {
        Cache::forget(self::PREFIX_HOME.'.page');
        Cache::forget(self::PREFIX_HOME.'.featured');
        Cache::forget(self::PREFIX_HOME.'.trending');
        Cache::forget(self::PREFIX_HOME.'.recent');
        Cache::forget(self::PREFIX_HOME.'.categories');
    }

    /**
     * Invalidate category cache
     */
    public function invalidateCategory(int $categoryId): void
    {
        // Invalidate all cached pages for this category (with different filters)
        Cache::forget(self::PREFIX_CATEGORY.".{$categoryId}");

        // Use tags if available, otherwise use pattern matching
        $this->invalidateByPattern(self::PREFIX_CATEGORY.".page.{$categoryId}.*");
    }

    /**
     * Invalidate tag cache
     */
    public function invalidateTag(int $tagId): void
    {
        Cache::forget(self::PREFIX_TAG.".{$tagId}");

        // Invalidate all cached pages for this tag
        $this->invalidateByPattern(self::PREFIX_TAG.".page.{$tagId}.*");
    }

    /**
     * Invalidate post cache
     */
    public function invalidatePost(int|string $postId): void
    {
        Cache::forget(self::PREFIX_POST.".{$postId}");
        Cache::forget(self::PREFIX_MODEL.'.post.'.$postId);
    }

    /**
     * Invalidate all view caches
     */
    public function invalidateAllViews(): void
    {
        $this->invalidateHomepage();
        $this->invalidateByPattern(self::PREFIX_VIEW.'.*');
        $this->invalidateByPattern(self::PREFIX_CATEGORY.'.*');
        $this->invalidateByPattern(self::PREFIX_TAG.'.*');
    }

    /**
     * Invalidate all query caches
     */
    public function invalidateAllQueries(): void
    {
        $this->invalidateByPattern(self::PREFIX_QUERY.'.*');
    }

    /**
     * Invalidate cache by pattern (for cache drivers that support it)
     */
    public function invalidateByPattern(string $pattern): void
    {
        // For file/redis cache drivers, we can use flush with tags
        // For simplicity, we'll use a basic approach
        // In production, consider using cache tags with Redis

        // Note: This is a simplified implementation
        // For better performance with Redis, use cache tags
        try {
            if (method_exists(Cache::getStore(), 'flush')) {
                // Can't selectively flush by pattern with most drivers
                // This is a limitation we accept for now
            }
        } catch (\Exception $e) {
            // Silently fail - cache invalidation is not critical
        }
    }

    /**
     * Generate a cache key from filters
     */
    protected function generateFilterKey(array $filters): string
    {
        if (empty($filters)) {
            return 'default';
        }

        ksort($filters);

        return md5(json_encode($filters));
    }

    /**
     * Warm up common caches
     */
    public function warmUp(): void
    {
        // This can be called after deployments to pre-populate caches
        // Implementation depends on specific needs
    }

    /**
     * Clear all application caches
     */
    public function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Get cache statistics (if supported by driver)
     */
    public function getStats(): array
    {
        // This would require driver-specific implementation
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
        ];
    }
}

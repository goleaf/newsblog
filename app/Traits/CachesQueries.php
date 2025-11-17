<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CachesQueries
{
    /**
     * Cache a query result with automatic TTL.
     *
     * @param  string  $key  Cache key
     * @param  string  $type  Cache type for TTL lookup (default, short, medium, long, etc.)
     * @param  callable  $callback  Query callback
     */
    public static function cacheQuery(string $key, string $type, callable $callback): mixed
    {
        $ttl = config("cache.ttl.{$type}", config('cache.ttl.default', 3600));

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget a cached query.
     *
     * @param  string  $key  Cache key
     */
    public static function forgetCachedQuery(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Forget multiple cached queries.
     *
     * @param  array  $keys  Array of cache keys
     */
    public static function forgetCachedQueries(array $keys): void
    {
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get cache key prefix for this model.
     */
    public static function getCacheKeyPrefix(): string
    {
        return strtolower(class_basename(static::class));
    }

    /**
     * Build a cache key for this model.
     *
     * @param  string  $suffix  Cache key suffix
     */
    public static function buildCacheKey(string $suffix): string
    {
        return static::getCacheKeyPrefix().'.'.$suffix;
    }
}

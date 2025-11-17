<?php

namespace App\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class CacheableBuilder extends Builder
{
    /**
     * Cache TTL in seconds.
     */
    protected ?int $cacheTtl = null;

    /**
     * Cache key.
     */
    protected ?string $cacheKey = null;

    /**
     * Cache type for TTL lookup.
     */
    protected string $cacheType = 'default';

    /**
     * Set cache TTL.
     *
     * @param  int  $seconds  TTL in seconds
     * @return $this
     */
    public function cacheTtl(int $seconds): static
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    /**
     * Set cache key.
     *
     * @param  string  $key  Cache key
     * @return $this
     */
    public function cacheKey(string $key): static
    {
        $this->cacheKey = $key;

        return $this;
    }

    /**
     * Set cache type for TTL lookup.
     *
     * @param  string  $type  Cache type (default, short, medium, long, etc.)
     * @return $this
     */
    public function cacheType(string $type): static
    {
        $this->cacheType = $type;

        return $this;
    }

    /**
     * Cache the query results.
     *
     * @param  int|null  $ttl  TTL in seconds (optional, uses cacheType if not provided)
     * @param  string|null  $key  Cache key (optional, auto-generated if not provided)
     * @return $this
     */
    public function remember(?int $ttl = null, ?string $key = null): static
    {
        if ($ttl !== null) {
            $this->cacheTtl = $ttl;
        }

        if ($key !== null) {
            $this->cacheKey = $key;
        }

        return $this;
    }

    /**
     * Execute the query and cache the results.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        if ($this->shouldCache()) {
            $key = $this->getCacheKey();
            $ttl = $this->getCacheTtl();

            return Cache::remember($key, $ttl, function () use ($columns) {
                return parent::get($columns);
            });
        }

        return parent::get($columns);
    }

    /**
     * Execute the query and cache the first result.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function first($columns = ['*'])
    {
        if ($this->shouldCache()) {
            $key = $this->getCacheKey().'.first';
            $ttl = $this->getCacheTtl();

            return Cache::remember($key, $ttl, function () use ($columns) {
                return parent::first($columns);
            });
        }

        return parent::first($columns);
    }

    /**
     * Determine if the query should be cached.
     */
    protected function shouldCache(): bool
    {
        return $this->cacheTtl !== null || $this->cacheKey !== null;
    }

    /**
     * Get the cache key.
     */
    protected function getCacheKey(): string
    {
        if ($this->cacheKey !== null) {
            return $this->cacheKey;
        }

        // Auto-generate cache key based on query
        $model = strtolower(class_basename($this->getModel()));
        $sql = $this->toSql();
        $bindings = $this->getBindings();

        return $model.'.'.md5($sql.serialize($bindings));
    }

    /**
     * Get the cache TTL.
     */
    protected function getCacheTtl(): int
    {
        if ($this->cacheTtl !== null) {
            return $this->cacheTtl;
        }

        return config("cache.ttl.{$this->cacheType}", config('cache.ttl.default', 3600));
    }
}

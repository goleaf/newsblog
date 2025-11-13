# Caching Strategy

## Overview

TechNewsHub implements a comprehensive multi-layer caching strategy to optimize performance and reduce database load. This document describes the caching implementation that fulfills Requirements 12.1, 12.2, and 12.3.

## Cache Layers

### 1. Query Result Caching (Requirement 12.1)

Expensive database queries are cached to reduce database load and improve response times.

**Implementation:**
- Homepage featured posts: 1 hour TTL
- Homepage trending posts: 30 minutes TTL
- Homepage recent posts: 10 minutes TTL
- Category listings: 1 hour TTL
- Tag listings: 1 hour TTL
- Related posts: 1 hour TTL (via RelatedPostsService)
- Dashboard metrics: 10 minutes TTL (via DashboardService)

**Example:**
```php
$featuredPosts = Cache::remember('home.featured', CacheService::TTL_LONG, function () {
    return Post::published()
        ->featured()
        ->with(['user:id,name', 'category:id,name,slug'])
        ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', ...])
        ->latest()
        ->take(3)
        ->get();
});
```

### 2. View Caching (Requirement 12.2)

Page-level caching for frequently accessed pages with appropriate TTLs.

**Cached Pages:**
- Homepage: 10 minutes TTL
- Category pages (first page, default filters): 10 minutes TTL
- Tag pages (first page, default filters): 10 minutes TTL
- Individual posts: 1 hour TTL

**Implementation:**
```php
// Category page caching
if ($page == 1 && empty($dateFilter) && $sort === 'latest') {
    $posts = $this->cacheService->cacheCategoryPage($category->id, $filters, function () use ($query) {
        return $query->paginate(12)->withQueryString();
    });
}
```

### 3. Model Caching (Requirement 12.3)

Frequently accessed models are cached to reduce database queries.

**Cached Models:**
- Post models (by slug): 1 hour TTL
- Category models (by slug): 1 hour TTL
- Tag models (by slug): 1 hour TTL
- Settings: 24 hours TTL (via Setting model)

**Example:**
```php
$category = $this->cacheService->cacheModel('category', $slug, CacheService::TTL_LONG, function () use ($slug) {
    return Category::where('slug', $slug)
        ->active()
        ->select(['id', 'name', 'slug', 'description', ...])
        ->firstOrFail();
});
```

## Cache TTL Constants

The `CacheService` defines standard TTL constants:

```php
const TTL_SHORT = 600;        // 10 minutes - frequently changing data
const TTL_MEDIUM = 1800;      // 30 minutes - moderately changing data
const TTL_LONG = 3600;        // 1 hour - stable data
const TTL_VERY_LONG = 86400;  // 24 hours - rarely changing data
```

## Cache Invalidation (Requirement 12.3)

Automatic cache invalidation ensures data consistency when content is updated.

### Post Events

**On Post Created:**
- Invalidates homepage cache
- Invalidates category cache
- Invalidates search index cache
- Invalidates related posts cache
- Regenerates sitemap

**On Post Updated:**
- Invalidates homepage cache
- Invalidates post cache (by ID and slug)
- Invalidates category cache (old and new if changed)
- Invalidates search index cache
- Invalidates related posts cache
- Regenerates sitemap (if status/slug changed)

**On Post Deleted:**
- Invalidates homepage cache
- Invalidates post cache
- Invalidates category cache
- Invalidates search index cache
- Invalidates related posts cache
- Regenerates sitemap

### Category Events

**On Category Created/Updated/Deleted:**
- Invalidates homepage cache
- Invalidates category cache
- Invalidates search index cache (if name/description changed)
- Regenerates sitemap

### Tag Events

**On Tag Updated/Deleted:**
- Invalidates tag cache
- Regenerates sitemap

## CacheService API

### Caching Methods

```php
// General caching
$cacheService->remember($key, $ttl, $callback);

// Specific page caching
$cacheService->cacheHomepage($callback);
$cacheService->cacheCategoryPage($categoryId, $filters, $callback);
$cacheService->cacheTagPage($tagId, $filters, $callback);

// Query caching
$cacheService->cacheQuery($queryKey, $ttl, $callback);

// Model caching
$cacheService->cacheModel($modelType, $identifier, $ttl, $callback);
```

### Invalidation Methods

```php
// Invalidate specific caches
$cacheService->invalidateHomepage();
$cacheService->invalidateCategory($categoryId);
$cacheService->invalidateTag($tagId);
$cacheService->invalidatePost($postId);

// Invalidate all caches of a type
$cacheService->invalidateAllViews();
$cacheService->invalidateAllQueries();

// Clear everything
$cacheService->clearAll();
```

## Artisan Commands

### Clear Application Caches

```bash
# Clear all application caches
php artisan cache:clear-app

# Clear specific cache types
php artisan cache:clear-app --type=views
php artisan cache:clear-app --type=queries
php artisan cache:clear-app --type=models
```

### Standard Laravel Cache Commands

```bash
# Clear all caches (including config, routes, views)
php artisan optimize:clear

# Clear only cache store
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear
```

## Query Optimization

In addition to caching, queries are optimized to reduce database load:

### Eager Loading

All queries use eager loading to prevent N+1 problems:

```php
Post::published()
    ->with(['user:id,name', 'category:id,name,slug'])
    ->select(['id', 'title', 'slug', ...])
    ->get();
```

### Select Only Needed Columns

Queries select only required columns to reduce memory usage:

```php
Category::active()
    ->select(['id', 'name', 'slug', 'description'])
    ->get();
```

### Index Usage

Database indexes are properly configured for:
- Post status and published_at (for published posts)
- Category and tag relationships
- User relationships
- Full-text search fields

## Performance Monitoring

Monitor cache effectiveness using:

1. **Cache Hit Rate**: Track how often cached data is used vs. regenerated
2. **Query Count**: Monitor database query count per request
3. **Response Time**: Measure page load times with and without cache
4. **Memory Usage**: Track cache memory consumption

## Best Practices

1. **Cache Warm-Up**: Pre-populate caches after deployments
2. **Cache Keys**: Use descriptive, consistent cache key naming
3. **TTL Selection**: Choose appropriate TTLs based on data volatility
4. **Invalidation**: Always invalidate related caches when data changes
5. **Testing**: Test cache invalidation in all scenarios
6. **Monitoring**: Monitor cache hit rates and adjust strategy as needed

## Cache Drivers

TechNewsHub supports multiple cache drivers:

- **File**: Default, suitable for single-server deployments
- **Redis**: Recommended for production, supports cache tags
- **Memcached**: Alternative high-performance option
- **Database**: Fallback option

Configure in `config/cache.php`:

```php
'default' => env('CACHE_DRIVER', 'file'),
```

## Production Recommendations

1. Use Redis for cache driver in production
2. Enable cache tags for better invalidation
3. Monitor cache size and set appropriate limits
4. Implement cache warming after deployments
5. Use separate Redis instances for cache and sessions
6. Configure cache key prefixes for multi-tenant setups

## Troubleshooting

### Cache Not Invalidating

1. Check model event listeners are firing
2. Verify CacheService is properly injected
3. Check cache driver configuration
4. Review cache key naming consistency

### High Memory Usage

1. Review TTL values (reduce if too long)
2. Limit cached data size (select fewer columns)
3. Implement cache size limits
4. Consider using Redis with eviction policies

### Stale Data

1. Verify cache invalidation on updates
2. Check TTL values (reduce if too long)
3. Review event listener implementation
4. Test cache invalidation in all scenarios

## Related Documentation

- [Performance Optimization](performance-optimization.md)
- [Database Schema](database-schema.md)
- [Deployment Quick Reference](deployment-quick-reference.md)

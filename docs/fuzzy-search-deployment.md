# Fuzzy Search Deployment Guide

This guide covers the deployment of the fuzzy search feature to production environments.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Configuration](#environment-configuration)
3. [Deployment Steps](#deployment-steps)
4. [Post-Deployment Verification](#post-deployment-verification)
5. [Monitoring](#monitoring)
6. [Rollback Procedure](#rollback-procedure)
7. [Troubleshooting](#troubleshooting)

## Prerequisites

Before deploying the fuzzy search feature, ensure:

- [ ] All migrations have been tested on staging
- [ ] Search index build has been tested with production-like data volumes
- [ ] Cache system (Redis/Memcached) is configured and operational
- [ ] Queue system is configured for async operations
- [ ] Monitoring tools are in place
- [ ] Database backup has been created

## Environment Configuration

### Required Environment Variables

Add the following variables to your `.env` file:

```bash
# Fuzzy Search Configuration
FUZZY_SEARCH_POSTS=true
FUZZY_SEARCH_TAGS=true
FUZZY_SEARCH_CATEGORIES=true
FUZZY_SEARCH_ADMIN=true
FUZZY_SEARCH_THRESHOLD=60
FUZZY_SEARCH_LEVENSHTEIN=2
FUZZY_SEARCH_PHONETIC=false
FUZZY_SEARCH_PHONETIC_WEIGHT=0.3
FUZZY_SEARCH_CACHE=true
FUZZY_SEARCH_CACHE_TTL=600
FUZZY_SEARCH_INDEX_TTL=86400
FUZZY_SEARCH_SUGGESTION_TTL=3600
FUZZY_SEARCH_ANALYTICS=true
```

### Configuration Recommendations by Environment

#### Production
```bash
FUZZY_SEARCH_CACHE=true
FUZZY_SEARCH_CACHE_TTL=600        # 10 minutes
FUZZY_SEARCH_INDEX_TTL=86400      # 24 hours
FUZZY_SEARCH_ANALYTICS=true
FUZZY_SEARCH_THRESHOLD=60         # Balanced relevance
```

#### Staging
```bash
FUZZY_SEARCH_CACHE=true
FUZZY_SEARCH_CACHE_TTL=300        # 5 minutes (faster testing)
FUZZY_SEARCH_INDEX_TTL=3600       # 1 hour (faster testing)
FUZZY_SEARCH_ANALYTICS=true
FUZZY_SEARCH_THRESHOLD=50         # More permissive for testing
```

#### Development
```bash
FUZZY_SEARCH_CACHE=false          # Disable for immediate results
FUZZY_SEARCH_ANALYTICS=true
FUZZY_SEARCH_THRESHOLD=50
```

### Cache Driver Configuration

The fuzzy search system requires a persistent cache driver. Configure one of the following:

#### Redis (Recommended for Production)
```bash
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379
```

#### Memcached
```bash
CACHE_STORE=memcached
MEMCACHED_HOST=127.0.0.1
```

#### Database (Fallback)
```bash
CACHE_STORE=database
```

**Note:** File cache is not recommended for production as it doesn't support cache tagging efficiently.

## Deployment Steps

### Step 1: Staging Deployment

1. Deploy code to staging environment
2. Run the staging deployment script:
   ```bash
   ./scripts/deploy-fuzzy-search-staging.sh
   ```
3. Build the search index:
   ```bash
   ./scripts/build-search-index-staging.sh
   ```
4. Verify functionality (see verification section below)

### Step 2: Production Deployment

1. **Create database backup:**
   ```bash
   php artisan db:backup
   ```

2. **Deploy application code:**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   npm run build
   ```

3. **Run migrations:**
   ```bash
   php artisan migrate --force
   ```

4. **Clear and rebuild cache:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. **Build search index:**
   ```bash
   php artisan search:rebuild-index --all
   ```

6. **Restart queue workers:**
   ```bash
   php artisan queue:restart
   ```

7. **Restart application (if using PHP-FPM):**
   ```bash
   sudo systemctl restart php8.4-fpm
   ```

### Step 3: Gradual Rollout (Optional)

For large production systems, consider a gradual rollout:

1. **Enable for admin users only:**
   ```bash
   FUZZY_SEARCH_POSTS=false
   FUZZY_SEARCH_ADMIN=true
   ```

2. **Monitor performance and errors for 24 hours**

3. **Enable for all users:**
   ```bash
   FUZZY_SEARCH_POSTS=true
   FUZZY_SEARCH_TAGS=true
   FUZZY_SEARCH_CATEGORIES=true
   ```

## Post-Deployment Verification

### 1. Verify Database Schema

```bash
php artisan tinker
```

```php
// Check tables exist
Schema::hasTable('search_logs');        // Should return true
Schema::hasTable('search_clicks');      // Should return true

// Check indexes
DB::select('SHOW INDEX FROM posts WHERE Key_name LIKE "%fulltext%"');
```

### 2. Verify Search Index

```bash
php artisan tinker
```

```php
$indexService = app(App\Services\SearchIndexService::class);
$stats = $indexService->getIndexStats();
print_r($stats);
```

Expected output:
```
Array
(
    [posts] => Array
        (
            [count] => 1234
            [last_updated] => 2025-11-12 10:30:00
        )
    [tags] => Array
        (
            [count] => 45
            [last_updated] => 2025-11-12 10:30:00
        )
    [categories] => Array
        (
            [count] => 12
            [last_updated] => 2025-11-12 10:30:00
        )
)
```

### 3. Test Search Functionality

```bash
php artisan tinker
```

```php
$searchService = app(App\Services\FuzzySearchService::class);

// Test basic search
$results = $searchService->searchPosts('laravel');
echo "Found: " . $results->count() . " results\n";

// Test fuzzy search with typo
$results = $searchService->searchPosts('laravle');
echo "Found: " . $results->count() . " results\n";

// Test suggestions
$suggestions = $searchService->getSuggestions('lar');
print_r($suggestions);
```

### 4. Verify Cache

```bash
php artisan tinker
```

```php
$cache = app('cache');
echo "Posts index cached: " . ($cache->has('fuzzy_search:index:posts') ? 'YES' : 'NO') . "\n";
echo "Tags index cached: " . ($cache->has('fuzzy_search:index:tags') ? 'YES' : 'NO') . "\n";
echo "Categories index cached: " . ($cache->has('fuzzy_search:index:categories') ? 'YES' : 'NO') . "\n";
```

### 5. Test Web Interface

1. Visit your site's search page
2. Perform a search with a common term
3. Perform a search with a typo (e.g., "laravle" instead of "laravel")
4. Verify results are highlighted
5. Test autocomplete suggestions
6. Check admin search functionality

### 6. Verify Analytics

```bash
php artisan search:analytics
```

Should display search statistics without errors.

## Monitoring

### Key Metrics to Monitor

1. **Search Performance**
   - Average response time (target: < 500ms)
   - 95th percentile response time (target: < 1s)
   - Cache hit rate (target: > 80%)

2. **Search Usage**
   - Total searches per day
   - Searches with no results (investigate if > 10%)
   - Most common queries

3. **System Resources**
   - Cache memory usage
   - Database query count
   - Queue job processing time

### Monitoring Commands

```bash
# View search analytics
php artisan search:analytics

# Check index statistics
php artisan tinker --execute="print_r(app(App\Services\SearchIndexService::class)->getIndexStats());"

# Monitor slow queries
tail -f storage/logs/laravel.log | grep "Fuzzy search"
```

### Setting Up Alerts

Configure alerts for:
- Search response time > 1 second
- Cache hit rate < 70%
- Search error rate > 1%
- Index build failures

## Rollback Procedure

If issues occur, you can quickly disable fuzzy search:

### Quick Disable (No Code Changes)

```bash
# Disable fuzzy search via environment
php artisan tinker --execute="
config(['fuzzy-search.enabled.posts' => false]);
config(['fuzzy-search.enabled.tags' => false]);
config(['fuzzy-search.enabled.categories' => false]);
config(['fuzzy-search.enabled.admin' => false]);
"

# Or update .env and clear config cache
php artisan config:clear
```

The system will automatically fall back to the original LIKE-based search.

### Full Rollback

If you need to completely rollback:

1. **Revert code:**
   ```bash
   git revert <commit-hash>
   git push origin main
   ```

2. **Rollback migrations (if necessary):**
   ```bash
   php artisan migrate:rollback --step=2
   ```

3. **Clear cache:**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. **Restart services:**
   ```bash
   php artisan queue:restart
   sudo systemctl restart php8.4-fpm
   ```

## Troubleshooting

### Issue: Search returns no results

**Possible causes:**
- Index not built
- Cache cleared but not rebuilt
- Configuration disabled

**Solution:**
```bash
php artisan search:rebuild-index --all
```

### Issue: Slow search performance

**Possible causes:**
- Cache not enabled or not working
- Too many items in index
- Database not optimized

**Solution:**
```bash
# Verify cache is working
php artisan tinker --execute="echo cache()->has('fuzzy_search:index:posts') ? 'Cache OK' : 'Cache MISSING';"

# Check index size
php artisan tinker --execute="print_r(app(App\Services\SearchIndexService::class)->getIndexStats());"

# Rebuild index with optimization
php artisan search:rebuild-index --all
```

### Issue: High memory usage

**Possible causes:**
- Index too large for memory
- Cache driver not suitable

**Solution:**
1. Reduce `max_index_items` in config
2. Switch to Redis cache driver
3. Implement pagination in search results

### Issue: Search index not updating

**Possible causes:**
- Model observers not registered
- Cache not invalidating
- Queue not processing

**Solution:**
```bash
# Check observers are registered
php artisan tinker --execute="
\$post = App\Models\Post::first();
echo 'Observers: ' . count(\$post->getObservableEvents()) . PHP_EOL;
"

# Manually rebuild index
php artisan search:rebuild-index --type=posts

# Check queue
php artisan queue:work --once
```

### Issue: Analytics not logging

**Possible causes:**
- Analytics disabled in config
- Database connection issues
- Queue not processing

**Solution:**
```bash
# Check configuration
php artisan tinker --execute="echo config('fuzzy-search.analytics.enabled') ? 'Enabled' : 'Disabled';"

# Check search_logs table
php artisan tinker --execute="echo App\Models\SearchLog::count() . ' logs';"

# Process queue
php artisan queue:work
```

## Maintenance Tasks

### Daily
- Monitor search performance metrics
- Check error logs for search-related issues

### Weekly
- Review top search queries
- Analyze no-result queries for content gaps
- Check cache hit rates

### Monthly
- Archive old search logs (automatic via command)
- Review and optimize search configuration
- Analyze search trends

### Quarterly
- Performance audit
- Index optimization review
- User feedback analysis

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review application logs: `storage/logs/laravel.log`
3. Check search analytics: `php artisan search:analytics`
4. Contact the development team

## Additional Resources

- [Fuzzy Search Requirements](../.kiro/specs/fuzzy-search-integration/requirements.md)
- [Fuzzy Search Design](../.kiro/specs/fuzzy-search-integration/design.md)
- [Laravel Documentation](https://laravel.com/docs)

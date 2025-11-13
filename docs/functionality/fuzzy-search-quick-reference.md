# Fuzzy Search Quick Reference

Quick reference for common fuzzy search operations and commands.

## Quick Start

### Deploy to Staging
```bash
./scripts/deploy-fuzzy-search-staging.sh
./scripts/build-search-index-staging.sh
```

### Deploy to Production
```bash
./scripts/deploy-fuzzy-search-production.sh
```

### Run Performance Tests
```bash
./scripts/performance-test-fuzzy-search.sh
```

## Common Commands

### Index Management

```bash
# Rebuild all indexes
php artisan search:rebuild-index --all

# Rebuild specific index
php artisan search:rebuild-index --type=posts
php artisan search:rebuild-index --type=tags
php artisan search:rebuild-index --type=categories

# Get index statistics
php artisan tinker --execute="
\$stats = app(App\Services\SearchIndexService::class)->getIndexStats();
print_r(\$stats);
"
```

### Search Operations

```bash
# Test basic search
php artisan tinker --execute="
\$results = app(App\Services\FuzzySearchService::class)->searchPosts('laravel');
echo 'Found: ' . \$results->count() . ' results' . PHP_EOL;
"

# Test fuzzy search with typo
php artisan tinker --execute="
\$results = app(App\Services\FuzzySearchService::class)->searchPosts('laravle');
echo 'Found: ' . \$results->count() . ' results' . PHP_EOL;
"

# Test suggestions
php artisan tinker --execute="
\$suggestions = app(App\Services\FuzzySearchService::class)->getSuggestions('lar');
print_r(\$suggestions);
"
```

### Analytics

```bash
# View search analytics
php artisan search:analytics

# Archive old logs
php artisan search:archive-logs

# Check recent searches
php artisan tinker --execute="
\$logs = App\Models\SearchLog::latest()->limit(10)->get(['query', 'result_count', 'created_at']);
print_r(\$logs->toArray());
"
```

### Cache Management

```bash
# Clear search cache
php artisan cache:forget fuzzy_search:index:posts
php artisan cache:forget fuzzy_search:index:tags
php artisan cache:forget fuzzy_search:index:categories

# Check cache status
php artisan tinker --execute="
echo 'Posts cached: ' . (cache()->has('fuzzy_search:index:posts') ? 'YES' : 'NO') . PHP_EOL;
echo 'Tags cached: ' . (cache()->has('fuzzy_search:index:tags') ? 'YES' : 'NO') . PHP_EOL;
echo 'Categories cached: ' . (cache()->has('fuzzy_search:index:categories') ? 'YES' : 'NO') . PHP_EOL;
"

# Clear all cache
php artisan cache:clear
```

### Configuration

```bash
# View current configuration
php artisan tinker --execute="
echo 'Posts: ' . (config('fuzzy-search.enabled.posts') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Cache: ' . (config('fuzzy-search.cache.enabled') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Threshold: ' . config('fuzzy-search.threshold') . PHP_EOL;
echo 'Analytics: ' . (config('fuzzy-search.analytics.enabled') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
"

# Clear config cache
php artisan config:clear

# Rebuild config cache
php artisan config:cache
```

## Environment Variables

### Essential Variables
```bash
FUZZY_SEARCH_POSTS=true
FUZZY_SEARCH_CACHE=true
FUZZY_SEARCH_THRESHOLD=60
FUZZY_SEARCH_ANALYTICS=true
```

### Cache Configuration
```bash
CACHE_STORE=redis
FUZZY_SEARCH_CACHE_TTL=600
FUZZY_SEARCH_INDEX_TTL=86400
```

## Troubleshooting

### Search Returns No Results

```bash
# Check if index exists
php artisan tinker --execute="
\$stats = app(App\Services\SearchIndexService::class)->getIndexStats();
echo 'Posts indexed: ' . (\$stats['posts']['count'] ?? 0) . PHP_EOL;
"

# Rebuild index
php artisan search:rebuild-index --all
```

### Slow Search Performance

```bash
# Check cache
php artisan tinker --execute="
echo 'Cache enabled: ' . (config('fuzzy-search.cache.enabled') ? 'YES' : 'NO') . PHP_EOL;
echo 'Cache driver: ' . config('cache.default') . PHP_EOL;
"

# Test cache
php artisan tinker --execute="
cache()->put('test', 'value', 60);
echo cache()->get('test') . PHP_EOL;
"
```

### Index Not Updating

```bash
# Check observers
php artisan tinker --execute="
\$post = App\Models\Post::first();
echo 'Post model has observers: ' . (count(\$post->getObservableEvents()) > 0 ? 'YES' : 'NO') . PHP_EOL;
"

# Manually rebuild
php artisan search:rebuild-index --type=posts
```

### Analytics Not Logging

```bash
# Check configuration
php artisan tinker --execute="
echo 'Analytics enabled: ' . (config('fuzzy-search.analytics.enabled') ? 'YES' : 'NO') . PHP_EOL;
"

# Check logs table
php artisan tinker --execute="
echo 'Total logs: ' . App\Models\SearchLog::count() . PHP_EOL;
echo 'Recent logs: ' . App\Models\SearchLog::where('created_at', '>=', now()->subHour())->count() . PHP_EOL;
"
```

## Quick Disable/Enable

### Disable Fuzzy Search
```bash
# Update .env
FUZZY_SEARCH_POSTS=false
FUZZY_SEARCH_TAGS=false
FUZZY_SEARCH_CATEGORIES=false
FUZZY_SEARCH_ADMIN=false

# Clear cache
php artisan config:clear
php artisan config:cache
```

### Enable Fuzzy Search
```bash
# Update .env
FUZZY_SEARCH_POSTS=true
FUZZY_SEARCH_TAGS=true
FUZZY_SEARCH_CATEGORIES=true
FUZZY_SEARCH_ADMIN=true

# Clear cache
php artisan config:clear
php artisan config:cache

# Rebuild index
php artisan search:rebuild-index --all
```

## Performance Monitoring

### Check Response Times
```bash
php artisan tinker --execute="
\$logs = App\Models\SearchLog::where('created_at', '>=', now()->subHour())
    ->get();
echo 'Average: ' . round(\$logs->avg('execution_time'), 2) . 'ms' . PHP_EOL;
echo 'Max: ' . round(\$logs->max('execution_time'), 2) . 'ms' . PHP_EOL;
"
```

### Check Cache Hit Rate
```bash
php artisan tinker --execute="
// This requires custom implementation
echo 'Check your monitoring dashboard for cache hit rates' . PHP_EOL;
"
```

### Monitor Logs
```bash
# Watch error logs
tail -f storage/logs/laravel.log | grep -i "fuzzy\|search"

# Watch all logs
tail -f storage/logs/laravel.log
```

## Testing

### Run All Tests
```bash
php artisan test --filter=FuzzySearch
```

### Run Performance Tests
```bash
php artisan test tests/Performance/FuzzySearchPerformanceTest.php
```

### Run Specific Test
```bash
php artisan test --filter=test_search_posts_with_fuzzy_matching
```

## API Endpoints

### Search Posts
```bash
# GET request
curl "https://your-site.com/api/search?q=laravel&limit=15"

# With authentication
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://your-site.com/api/search?q=laravel"
```

### Get Suggestions
```bash
curl "https://your-site.com/api/suggestions?q=lar"
```

## Maintenance Tasks

### Daily
```bash
# Check error logs
tail -100 storage/logs/laravel.log | grep -i error

# Monitor performance
php artisan search:analytics
```

### Weekly
```bash
# Review top queries
php artisan tinker --execute="
\$top = App\Models\SearchLog::selectRaw('query, COUNT(*) as count')
    ->where('created_at', '>=', now()->subWeek())
    ->groupBy('query')
    ->orderBy('count', 'desc')
    ->limit(10)
    ->get();
print_r(\$top->toArray());
"
```

### Monthly
```bash
# Archive old logs
php artisan search:archive-logs

# Performance review
./scripts/performance-test-fuzzy-search.sh
```

## Health Check

```bash
# Quick health check
php artisan tinker --execute="
try {
    \$searchService = app(App\Services\FuzzySearchService::class);
    \$results = \$searchService->searchPosts('test', ['limit' => 1]);
    echo 'Status: HEALTHY' . PHP_EOL;
    echo 'Search working: YES' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Status: UNHEALTHY' . PHP_EOL;
    echo 'Error: ' . \$e->getMessage() . PHP_EOL;
}
"
```

## Useful Queries

### Find Slow Searches
```bash
php artisan tinker --execute="
\$slow = App\Models\SearchLog::where('execution_time', '>', 1000)
    ->orderBy('execution_time', 'desc')
    ->limit(10)
    ->get(['query', 'execution_time', 'result_count']);
print_r(\$slow->toArray());
"
```

### Find No-Result Queries
```bash
php artisan tinker --execute="
\$noResults = App\Models\SearchLog::where('result_count', 0)
    ->selectRaw('query, COUNT(*) as count')
    ->groupBy('query')
    ->orderBy('count', 'desc')
    ->limit(10)
    ->get();
print_r(\$noResults->toArray());
"
```

### Check Index Size
```bash
php artisan tinker --execute="
\$stats = app(App\Services\SearchIndexService::class)->getIndexStats();
foreach (\$stats as \$type => \$data) {
    echo ucfirst(\$type) . ': ' . (\$data['count'] ?? 0) . ' items' . PHP_EOL;
}
"
```

## Support

### Log Files
- Application: `storage/logs/laravel.log`
- Performance: `storage/logs/fuzzy-search-performance-*.txt`

### Documentation
- [Deployment Guide](fuzzy-search-deployment.md)
- [Environment Setup](fuzzy-search-environment-setup.md)
- [Load Testing](fuzzy-search-load-testing.md)
- [Deployment Checklist](fuzzy-search-deployment-checklist.md)

### Scripts
- `scripts/deploy-fuzzy-search-staging.sh`
- `scripts/build-search-index-staging.sh`
- `scripts/deploy-fuzzy-search-production.sh`
- `scripts/performance-test-fuzzy-search.sh`

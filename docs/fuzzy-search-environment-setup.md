# Fuzzy Search Environment Setup Checklist

This document provides a comprehensive checklist for configuring the fuzzy search feature across different environments.

## Environment Variables Reference

### Core Configuration

| Variable | Default | Description | Production | Staging | Development |
|----------|---------|-------------|------------|---------|-------------|
| `FUZZY_SEARCH_POSTS` | `true` | Enable fuzzy search for posts | `true` | `true` | `true` |
| `FUZZY_SEARCH_TAGS` | `true` | Enable fuzzy search for tags | `true` | `true` | `true` |
| `FUZZY_SEARCH_CATEGORIES` | `true` | Enable fuzzy search for categories | `true` | `true` | `true` |
| `FUZZY_SEARCH_ADMIN` | `true` | Enable fuzzy search in admin panel | `true` | `true` | `true` |

### Search Behavior

| Variable | Default | Description | Production | Staging | Development |
|----------|---------|-------------|------------|---------|-------------|
| `FUZZY_SEARCH_THRESHOLD` | `60` | Minimum relevance score (0-100) | `60` | `50` | `50` |
| `FUZZY_SEARCH_LEVENSHTEIN` | `2` | Max character edits for fuzzy matching | `2` | `2` | `2` |
| `FUZZY_SEARCH_PHONETIC` | `false` | Enable phonetic matching | `false` | `true` | `true` |
| `FUZZY_SEARCH_PHONETIC_WEIGHT` | `0.3` | Weight for phonetic matches | `0.3` | `0.3` | `0.3` |

### Caching

| Variable | Default | Description | Production | Staging | Development |
|----------|---------|-------------|------------|---------|-------------|
| `FUZZY_SEARCH_CACHE` | `true` | Enable search result caching | `true` | `true` | `false` |
| `FUZZY_SEARCH_CACHE_TTL` | `600` | Cache TTL in seconds (10 min) | `600` | `300` | `60` |
| `FUZZY_SEARCH_INDEX_TTL` | `86400` | Index cache TTL (24 hours) | `86400` | `3600` | `600` |
| `FUZZY_SEARCH_SUGGESTION_TTL` | `3600` | Suggestion cache TTL (1 hour) | `3600` | `1800` | `300` |

### Analytics

| Variable | Default | Description | Production | Staging | Development |
|----------|---------|-------------|------------|---------|-------------|
| `FUZZY_SEARCH_ANALYTICS` | `true` | Enable search analytics logging | `true` | `true` | `true` |

## Production Environment Setup

### Prerequisites Checklist

- [ ] Redis or Memcached installed and configured
- [ ] Queue worker configured and running
- [ ] Database backup created
- [ ] Monitoring tools configured
- [ ] SSL certificate valid
- [ ] Sufficient server resources (CPU, RAM, Disk)

### Configuration Steps

1. **Copy environment template:**
   ```bash
   cp .env.example .env
   ```

2. **Set fuzzy search variables:**
   ```bash
   # Add to .env
   FUZZY_SEARCH_POSTS=true
   FUZZY_SEARCH_TAGS=true
   FUZZY_SEARCH_CATEGORIES=true
   FUZZY_SEARCH_ADMIN=true
   FUZZY_SEARCH_THRESHOLD=60
   FUZZY_SEARCH_LEVENSHTEIN=2
   FUZZY_SEARCH_PHONETIC=false
   FUZZY_SEARCH_CACHE=true
   FUZZY_SEARCH_CACHE_TTL=600
   FUZZY_SEARCH_INDEX_TTL=86400
   FUZZY_SEARCH_SUGGESTION_TTL=3600
   FUZZY_SEARCH_ANALYTICS=true
   ```

3. **Configure cache driver (Redis recommended):**
   ```bash
   CACHE_STORE=redis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=your_secure_password
   REDIS_PORT=6379
   ```

4. **Configure queue driver:**
   ```bash
   QUEUE_CONNECTION=redis
   ```

5. **Clear and cache configuration:**
   ```bash
   php artisan config:cache
   ```

### Verification

```bash
# Verify configuration
php artisan tinker --execute="
echo 'Fuzzy Search Configuration:' . PHP_EOL;
echo '  Posts: ' . (config('fuzzy-search.enabled.posts') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo '  Cache: ' . (config('fuzzy-search.cache.enabled') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo '  Cache Driver: ' . config('cache.default') . PHP_EOL;
echo '  Queue Driver: ' . config('queue.default') . PHP_EOL;
"
```

## Staging Environment Setup

### Configuration Steps

1. **Set fuzzy search variables (more permissive for testing):**
   ```bash
   FUZZY_SEARCH_POSTS=true
   FUZZY_SEARCH_TAGS=true
   FUZZY_SEARCH_CATEGORIES=true
   FUZZY_SEARCH_ADMIN=true
   FUZZY_SEARCH_THRESHOLD=50
   FUZZY_SEARCH_LEVENSHTEIN=2
   FUZZY_SEARCH_PHONETIC=true
   FUZZY_SEARCH_CACHE=true
   FUZZY_SEARCH_CACHE_TTL=300
   FUZZY_SEARCH_INDEX_TTL=3600
   FUZZY_SEARCH_ANALYTICS=true
   ```

2. **Use same cache driver as production:**
   ```bash
   CACHE_STORE=redis
   ```

3. **Deploy and test:**
   ```bash
   ./scripts/deploy-fuzzy-search-staging.sh
   ./scripts/build-search-index-staging.sh
   ```

## Development Environment Setup

### Configuration Steps

1. **Set fuzzy search variables (cache disabled for immediate results):**
   ```bash
   FUZZY_SEARCH_POSTS=true
   FUZZY_SEARCH_TAGS=true
   FUZZY_SEARCH_CATEGORIES=true
   FUZZY_SEARCH_ADMIN=true
   FUZZY_SEARCH_THRESHOLD=50
   FUZZY_SEARCH_LEVENSHTEIN=2
   FUZZY_SEARCH_PHONETIC=true
   FUZZY_SEARCH_CACHE=false
   FUZZY_SEARCH_ANALYTICS=true
   ```

2. **Use simple cache driver:**
   ```bash
   CACHE_STORE=file
   # or
   CACHE_STORE=database
   ```

3. **Build index:**
   ```bash
   php artisan search:rebuild-index --all
   ```

## Cache Driver Configuration

### Redis (Recommended for Production)

**Installation:**
```bash
# Ubuntu/Debian
sudo apt-get install redis-server php-redis

# macOS
brew install redis
pecl install redis
```

**Configuration:**
```bash
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_password
REDIS_PORT=6379
REDIS_CLIENT=phpredis
```

**Start Redis:**
```bash
# Ubuntu/Debian
sudo systemctl start redis-server
sudo systemctl enable redis-server

# macOS
brew services start redis
```

**Verify:**
```bash
redis-cli ping
# Should return: PONG
```

### Memcached (Alternative)

**Installation:**
```bash
# Ubuntu/Debian
sudo apt-get install memcached php-memcached

# macOS
brew install memcached
pecl install memcached
```

**Configuration:**
```bash
CACHE_STORE=memcached
MEMCACHED_HOST=127.0.0.1
```

**Start Memcached:**
```bash
# Ubuntu/Debian
sudo systemctl start memcached
sudo systemctl enable memcached

# macOS
brew services start memcached
```

### Database (Fallback)

**Configuration:**
```bash
CACHE_STORE=database
```

**Setup:**
```bash
php artisan cache:table
php artisan migrate
```

**Note:** Database cache is slower but doesn't require additional services.

## Queue Configuration

### Redis Queue (Recommended)

**Configuration:**
```bash
QUEUE_CONNECTION=redis
```

**Start Worker:**
```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

**Supervisor Configuration (Production):**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

### Database Queue (Development)

**Configuration:**
```bash
QUEUE_CONNECTION=database
```

**Setup:**
```bash
php artisan queue:table
php artisan migrate
```

**Start Worker:**
```bash
php artisan queue:work database
```

## Monitoring Setup

### Application Monitoring

1. **Enable Laravel Telescope (Development/Staging):**
   ```bash
   composer require laravel/telescope --dev
   php artisan telescope:install
   php artisan migrate
   ```

2. **Configure logging:**
   ```bash
   LOG_CHANNEL=stack
   LOG_LEVEL=info
   ```

3. **Monitor search logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "Fuzzy search"
   ```

### Performance Monitoring

1. **Install monitoring tools:**
   - New Relic
   - Datadog
   - Laravel Horizon (for queue monitoring)

2. **Set up alerts for:**
   - Search response time > 1 second
   - Cache hit rate < 70%
   - Search error rate > 1%
   - Queue backlog > 100 jobs

### Health Checks

Create a health check endpoint:

```php
// routes/web.php
Route::get('/health/search', function () {
    $searchService = app(App\Services\FuzzySearchService::class);
    $indexService = app(App\Services\SearchIndexService::class);
    
    try {
        // Test search
        $results = $searchService->searchPosts('test', ['limit' => 1]);
        
        // Check index
        $stats = $indexService->getIndexStats();
        
        return response()->json([
            'status' => 'healthy',
            'search_enabled' => config('fuzzy-search.enabled.posts'),
            'cache_enabled' => config('fuzzy-search.cache.enabled'),
            'index_stats' => $stats,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
        ], 500);
    }
});
```

## Security Configuration

### Rate Limiting

Configure in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi();
    
    // Custom rate limiter for search
    RateLimiter::for('search', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
})
```

### Input Validation

Ensure `SearchRequest` and `ApiSearchRequest` are properly configured with validation rules.

### HTTPS

Ensure all production environments use HTTPS:

```bash
APP_URL=https://yourdomain.com
```

## Troubleshooting Configuration Issues

### Issue: Configuration not updating

**Solution:**
```bash
php artisan config:clear
php artisan config:cache
```

### Issue: Cache not working

**Solution:**
```bash
# Check cache driver
php artisan tinker --execute="echo config('cache.default');"

# Test cache
php artisan tinker --execute="
cache()->put('test', 'value', 60);
echo cache()->get('test');
"

# Clear cache
php artisan cache:clear
```

### Issue: Queue not processing

**Solution:**
```bash
# Check queue connection
php artisan tinker --execute="echo config('queue.default');"

# Check queue jobs
php artisan queue:failed

# Restart queue
php artisan queue:restart
php artisan queue:work
```

## Configuration Validation Script

Create a validation script:

```bash
#!/bin/bash
# scripts/validate-fuzzy-search-config.sh

echo "Validating Fuzzy Search Configuration..."

php artisan tinker --execute="
\$errors = [];

// Check required config
if (!config('fuzzy-search.enabled.posts')) {
    \$errors[] = 'Fuzzy search for posts is disabled';
}

// Check cache
if (config('fuzzy-search.cache.enabled') && !in_array(config('cache.default'), ['redis', 'memcached', 'database'])) {
    \$errors[] = 'Invalid cache driver: ' . config('cache.default');
}

// Check queue
if (!in_array(config('queue.default'), ['redis', 'database', 'sync'])) {
    \$errors[] = 'Invalid queue driver: ' . config('queue.default');
}

// Check index
try {
    \$stats = app(App\Services\SearchIndexService::class)->getIndexStats();
    if ((\$stats['posts']['count'] ?? 0) === 0) {
        \$errors[] = 'Search index is empty';
    }
} catch (\Exception \$e) {
    \$errors[] = 'Cannot access search index: ' . \$e->getMessage();
}

if (empty(\$errors)) {
    echo '✓ Configuration is valid' . PHP_EOL;
    exit(0);
} else {
    echo '✗ Configuration errors:' . PHP_EOL;
    foreach (\$errors as \$error) {
        echo '  - ' . \$error . PHP_EOL;
    }
    exit(1);
}
"
```

Make it executable:
```bash
chmod +x scripts/validate-fuzzy-search-config.sh
```

Run validation:
```bash
./scripts/validate-fuzzy-search-config.sh
```

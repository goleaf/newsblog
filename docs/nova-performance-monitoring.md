# Nova Performance Monitoring Guide

This guide provides detailed instructions for monitoring the performance of the Laravel Nova integration during and after deployment.

## Table of Contents

1. [Performance Metrics Overview](#performance-metrics-overview)
2. [Monitoring Tools](#monitoring-tools)
3. [Key Performance Indicators (KPIs)](#key-performance-indicators-kpis)
4. [Database Performance](#database-performance)
5. [Application Performance](#application-performance)
6. [Frontend Performance](#frontend-performance)
7. [Resource Usage](#resource-usage)
8. [Performance Optimization](#performance-optimization)
9. [Troubleshooting Slow Performance](#troubleshooting-slow-performance)

---

## Performance Metrics Overview

### Target Performance Metrics

| Metric | Target | Warning | Critical |
|--------|--------|---------|----------|
| Dashboard Load Time | <2s | 2-4s | >4s |
| Resource Index Load | <1.5s | 1.5-3s | >3s |
| Resource Detail Load | <1s | 1-2s | >2s |
| Search Response | <1s | 1-2s | >2s |
| Action Execution | <3s | 3-5s | >5s |
| Database Query Time | <100ms | 100-500ms | >500ms |
| Memory Usage | <256MB | 256-512MB | >512MB |
| CPU Usage | <50% | 50-80% | >80% |

---

## Monitoring Tools

### 1. Laravel Telescope (Development/Staging)

**Installation** (if not already installed):
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Access**: Navigate to `/telescope` in your browser

**Key Features**:
- Request monitoring
- Query monitoring
- Exception tracking
- Job monitoring
- Cache monitoring
- Model events

**Usage**:
```bash
# Enable Telescope
php artisan telescope:publish

# Clear Telescope data
php artisan telescope:clear
```

### 2. Laravel Debugbar (Development)

**Installation**:
```bash
composer require barryvdh/laravel-debugbar --dev
```

**Features**:
- Query count and execution time
- Memory usage
- Timeline
- Route information
- View rendering time

### 3. Application Performance Monitoring (Production)

**Recommended Tools**:
- **New Relic**: Full-stack monitoring
- **Blackfire**: PHP profiling
- **Scout APM**: Laravel-specific monitoring
- **Datadog**: Infrastructure and application monitoring

### 4. Built-in Laravel Tools

**Query Logging**:
```php
// In AppServiceProvider boot method
DB::listen(function ($query) {
    if ($query->time > 100) { // Log queries over 100ms
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

**Memory Monitoring**:
```php
// Check memory usage
$memory = memory_get_usage(true) / 1024 / 1024; // MB
Log::info("Memory usage: {$memory}MB");
```

---

## Key Performance Indicators (KPIs)

### 1. Response Time Monitoring

**Command to Test Response Times**:
```bash
# Test Nova dashboard
time curl -I https://your-domain.com/nova

# Test specific resource
time curl -I https://your-domain.com/nova/resources/posts
```

**Using Apache Bench**:
```bash
# Test with 100 requests, 10 concurrent
ab -n 100 -c 10 https://your-domain.com/nova/
```

### 2. Database Query Performance

**Enable Query Logging**:
```php
// In config/database.php
'connections' => [
    'sqlite' => [
        // ...
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ],
    ],
],
```

**Check Slow Queries**:
```bash
# View slow queries in log
grep "slow query" storage/logs/laravel.log

# Count queries per request (using Telescope)
# Navigate to /telescope/queries
```

**Identify N+1 Queries**:
```bash
# Look for repeated similar queries
grep "select \* from" storage/logs/laravel.log | sort | uniq -c | sort -rn
```

### 3. Cache Performance

**Monitor Cache Hit Rate**:
```php
// Add to a monitoring command
$hits = Cache::get('cache_hits', 0);
$misses = Cache::get('cache_misses', 0);
$total = $hits + $misses;
$hitRate = $total > 0 ? ($hits / $total) * 100 : 0;

Log::info("Cache hit rate: {$hitRate}%");
```

**Check Cache Size**:
```bash
# For file cache
du -sh storage/framework/cache

# For database cache
php artisan tinker
>>> DB::table('cache')->count();
```

---

## Database Performance

### 1. Query Analysis

**Check Query Count per Request**:
```php
// Add to middleware or service provider
DB::enableQueryLog();

// After request
$queries = DB::getQueryLog();
$queryCount = count($queries);
$totalTime = array_sum(array_column($queries, 'time'));

if ($queryCount > 50) {
    Log::warning("High query count: {$queryCount} queries in {$totalTime}ms");
}
```

**Analyze Specific Resource Queries**:
```bash
# Enable query logging in Nova resource
public static function indexQuery(NovaRequest $request, $query)
{
    DB::enableQueryLog();
    
    $result = $query->with(['user', 'category', 'tags']);
    
    Log::info('Post index queries', [
        'queries' => DB::getQueryLog()
    ]);
    
    return $result;
}
```

### 2. Index Optimization

**Check Missing Indexes**:
```bash
# Run EXPLAIN on slow queries
php artisan tinker
>>> DB::select('EXPLAIN SELECT * FROM posts WHERE status = ?', ['published']);
```

**Verify Indexes Exist**:
```bash
# For SQLite
php artisan tinker
>>> DB::select("SELECT * FROM sqlite_master WHERE type='index'");

# For MySQL
>>> DB::select("SHOW INDEXES FROM posts");
```

**Add Missing Indexes** (if needed):
```php
// Create migration
php artisan make:migration add_performance_indexes_to_posts_table

// In migration
public function up()
{
    Schema::table('posts', function (Blueprint $table) {
        $table->index(['status', 'published_at']);
        $table->index(['user_id', 'status']);
        $table->index(['category_id', 'status']);
    });
}
```

### 3. Eager Loading Verification

**Check for N+1 Issues**:
```php
// In Nova resource
public static function indexQuery(NovaRequest $request, $query)
{
    // Log query count before
    $beforeCount = count(DB::getQueryLog());
    
    $result = $query->with(['user', 'category', 'tags']);
    
    // Log query count after
    $afterCount = count(DB::getQueryLog());
    
    Log::info('Eager loading check', [
        'queries_added' => $afterCount - $beforeCount,
        'expected' => 4, // 1 main + 3 relationships
    ]);
    
    return $result;
}
```

---

## Application Performance

### 1. Memory Usage Monitoring

**Track Memory per Request**:
```php
// In middleware
public function handle($request, Closure $next)
{
    $startMemory = memory_get_usage(true);
    
    $response = $next($request);
    
    $endMemory = memory_get_usage(true);
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
    
    if ($memoryUsed > 50) { // Alert if over 50MB
        Log::warning("High memory usage: {$memoryUsed}MB", [
            'url' => $request->url(),
        ]);
    }
    
    return $response;
}
```

**Check Peak Memory**:
```bash
# View memory usage in logs
grep "memory" storage/logs/laravel.log

# Check PHP memory limit
php -i | grep memory_limit
```

### 2. CPU Usage Monitoring

**Monitor PHP-FPM Processes**:
```bash
# Check active PHP processes
ps aux | grep php-fpm

# Monitor CPU usage
top -p $(pgrep -d',' php-fpm)
```

**Profile CPU-Intensive Operations**:
```php
// Use Blackfire or Xdebug profiler
// Or simple timing
$start = microtime(true);

// Your code here

$end = microtime(true);
$duration = ($end - $start) * 1000; // milliseconds

if ($duration > 1000) {
    Log::warning("Slow operation: {$duration}ms");
}
```

### 3. Opcode Cache

**Verify OPcache is Enabled**:
```bash
php -i | grep opcache

# Check OPcache status
php -r "print_r(opcache_get_status());"
```

**Optimize OPcache Settings** (in `php.ini`):
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0  # Production only
opcache.revalidate_freq=0
```

---

## Frontend Performance

### 1. Asset Loading

**Check Asset Sizes**:
```bash
# Check Nova asset sizes
ls -lh public/vendor/nova/

# Check compiled assets
ls -lh public/build/
```

**Optimize Assets**:
```bash
# Rebuild with production settings
npm run build

# Verify minification
cat public/build/assets/app-*.js | wc -c
```

### 2. Browser Performance

**Use Browser DevTools**:
1. Open Chrome DevTools (F12)
2. Go to Network tab
3. Reload Nova dashboard
4. Check:
   - Total page size
   - Number of requests
   - Load time
   - Largest contentful paint (LCP)

**Performance Metrics to Track**:
- First Contentful Paint (FCP): <1.8s
- Largest Contentful Paint (LCP): <2.5s
- Time to Interactive (TTI): <3.8s
- Total Blocking Time (TBT): <200ms

### 3. API Response Times

**Monitor Nova API Calls**:
```javascript
// In browser console
performance.getEntriesByType('resource')
    .filter(r => r.name.includes('/nova-api/'))
    .forEach(r => console.log(r.name, r.duration + 'ms'));
```

---

## Resource Usage

### 1. Disk Space Monitoring

**Check Disk Usage**:
```bash
# Overall disk usage
df -h

# Storage directory
du -sh storage/

# Log files
du -sh storage/logs/

# Cache files
du -sh storage/framework/cache/
```

**Automated Monitoring**:
```bash
# Add to cron (check every hour)
0 * * * * cd /path/to/app && df -h . | awk 'NR==2 {if ($5+0 > 80) print "Disk usage high: " $5}' | mail -s "Disk Alert" admin@example.com
```

### 2. Database Size

**Check Database Size**:
```bash
# For SQLite
ls -lh database/database.sqlite

# For MySQL
php artisan tinker
>>> DB::select("SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'your_database' GROUP BY table_schema");
```

### 3. Log File Management

**Monitor Log Growth**:
```bash
# Check log file sizes
ls -lh storage/logs/

# Count log entries
wc -l storage/logs/laravel.log
```

**Rotate Logs**:
```bash
# Configure in config/logging.php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 14, // Keep 14 days
],
```

---

## Performance Optimization

### 1. Caching Strategies

**Config Caching**:
```bash
# Cache configuration
php artisan config:cache

# Clear when needed
php artisan config:clear
```

**Route Caching**:
```bash
# Cache routes (production only)
php artisan route:cache

# Clear when needed
php artisan route:clear
```

**View Caching**:
```bash
# Cache views
php artisan view:cache

# Clear when needed
php artisan view:clear
```

**Query Result Caching**:
```php
// In Nova metrics
public function calculate(NovaRequest $request)
{
    return Cache::remember('total_posts_metric', 300, function () {
        return Post::published()->count();
    });
}
```

### 2. Database Optimization

**Optimize Tables** (MySQL):
```bash
php artisan tinker
>>> DB::statement('OPTIMIZE TABLE posts');
>>> DB::statement('OPTIMIZE TABLE users');
```

**Analyze Tables** (MySQL):
```bash
>>> DB::statement('ANALYZE TABLE posts');
```

**Vacuum Database** (SQLite):
```bash
php artisan tinker
>>> DB::statement('VACUUM');
```

### 3. Code Optimization

**Use Lazy Collections**:
```php
// Instead of
$posts = Post::all();

// Use
$posts = Post::cursor();
```

**Chunk Large Datasets**:
```php
Post::chunk(100, function ($posts) {
    foreach ($posts as $post) {
        // Process post
    }
});
```

**Select Only Needed Columns**:
```php
// Instead of
$posts = Post::all();

// Use
$posts = Post::select('id', 'title', 'status')->get();
```

---

## Troubleshooting Slow Performance

### 1. Identify Bottlenecks

**Step 1: Enable Detailed Logging**:
```php
// In AppServiceProvider
DB::listen(function ($query) {
    Log::debug('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

**Step 2: Profile Specific Request**:
```bash
# Use Blackfire
blackfire curl https://your-domain.com/nova/resources/posts

# Or use Xdebug profiler
# Enable in php.ini:
# xdebug.mode=profile
# xdebug.output_dir=/tmp
```

**Step 3: Analyze Results**:
- Look for slow queries (>100ms)
- Check for N+1 query patterns
- Identify memory-intensive operations
- Find CPU-intensive code

### 2. Common Performance Issues

**Issue: Slow Resource Index**

**Diagnosis**:
```bash
# Check query count
# Navigate to /telescope/queries
# Look for repeated queries
```

**Solution**:
```php
// Add eager loading in Nova resource
public static function indexQuery(NovaRequest $request, $query)
{
    return $query->with(['user', 'category', 'tags']);
}
```

**Issue: Slow Dashboard Metrics**

**Diagnosis**:
```bash
# Check metric calculation time
# Add timing to metric
```

**Solution**:
```php
// Add caching to metrics
public function calculate(NovaRequest $request)
{
    return Cache::remember('metric_key', 300, function () {
        return $this->expensiveCalculation();
    });
}
```

**Issue: High Memory Usage**

**Diagnosis**:
```bash
# Check memory usage in logs
grep "memory" storage/logs/laravel.log
```

**Solution**:
```php
// Use chunking for large datasets
// Use cursor() instead of get()
// Unset variables when done
```

### 3. Performance Testing

**Load Testing with Apache Bench**:
```bash
# Test dashboard
ab -n 1000 -c 10 https://your-domain.com/nova/

# Test resource index
ab -n 1000 -c 10 https://your-domain.com/nova/resources/posts
```

**Stress Testing**:
```bash
# Install siege
brew install siege  # macOS
apt-get install siege  # Ubuntu

# Run stress test
siege -c 50 -t 1M https://your-domain.com/nova/
```

---

## Monitoring Checklist

### Daily Checks (First Week)
- [ ] Check error logs for exceptions
- [ ] Review slow query log
- [ ] Monitor disk usage
- [ ] Check memory usage
- [ ] Review user activity logs

### Weekly Checks
- [ ] Analyze performance trends
- [ ] Review cache hit rates
- [ ] Check database size growth
- [ ] Optimize slow queries
- [ ] Review user feedback

### Monthly Checks
- [ ] Full performance audit
- [ ] Database optimization
- [ ] Log rotation and cleanup
- [ ] Update performance baselines
- [ ] Plan optimizations

---

## Performance Reporting

### Create Performance Report

```bash
# Generate performance report
php artisan tinker

# Collect metrics
$metrics = [
    'avg_response_time' => DB::table('telescope_entries')
        ->where('type', 'request')
        ->where('created_at', '>=', now()->subDay())
        ->avg('content->duration'),
    'slow_queries' => DB::table('telescope_entries')
        ->where('type', 'query')
        ->where('content->time', '>', 100)
        ->count(),
    'total_requests' => DB::table('telescope_entries')
        ->where('type', 'request')
        ->where('created_at', '>=', now()->subDay())
        ->count(),
];

print_r($metrics);
```

### Performance Dashboard

Create a custom Nova metric or tool to display:
- Average response time
- Slow query count
- Memory usage trends
- Cache hit rate
- Error rate

---

## Conclusion

Regular performance monitoring ensures the Nova integration remains fast and responsive. Use this guide to:

1. Establish performance baselines
2. Monitor key metrics
3. Identify bottlenecks early
4. Optimize proactively
5. Maintain optimal performance

For issues or questions, refer to:
- Laravel Nova documentation: https://nova.laravel.com/docs
- Laravel performance documentation: https://laravel.com/docs/performance
- Internal troubleshooting guide: `docs/nova-deployment-checklist.md`

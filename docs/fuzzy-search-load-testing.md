# Fuzzy Search Load Testing Guide

This guide provides instructions for load testing the fuzzy search feature to ensure it meets performance requirements.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Performance Targets](#performance-targets)
3. [Testing Tools](#testing-tools)
4. [Test Scenarios](#test-scenarios)
5. [Running Tests](#running-tests)
6. [Analyzing Results](#analyzing-results)
7. [Optimization Recommendations](#optimization-recommendations)

## Prerequisites

### Required Tools

- PHP 8.4+
- PHPUnit (included with Laravel)
- Apache Bench (ab) or similar load testing tool
- Access to staging environment with production-like data

### Optional Tools

- [k6](https://k6.io/) - Modern load testing tool
- [Apache JMeter](https://jmeter.apache.org/) - Comprehensive testing suite
- [Locust](https://locust.io/) - Python-based load testing

### Environment Setup

Ensure your testing environment:
- Has production-like data volumes
- Uses the same cache driver as production (Redis/Memcached)
- Has queue workers running
- Has sufficient resources (CPU, RAM, Disk)

## Performance Targets

### Response Time Targets

| Metric | Target | Acceptable | Poor |
|--------|--------|------------|------|
| Average search response | < 500ms | < 1000ms | > 1000ms |
| 95th percentile | < 1000ms | < 1500ms | > 1500ms |
| 99th percentile | < 1500ms | < 2000ms | > 2000ms |
| Cached search | < 100ms | < 200ms | > 200ms |

### Throughput Targets

| Metric | Target | Acceptable | Poor |
|--------|--------|------------|------|
| Requests per second | > 50 | > 20 | < 20 |
| Concurrent users | > 100 | > 50 | < 50 |
| Error rate | < 0.1% | < 1% | > 1% |

### Resource Targets

| Metric | Target | Acceptable | Poor |
|--------|--------|------------|------|
| CPU usage | < 70% | < 85% | > 85% |
| Memory usage | < 512MB | < 1GB | > 1GB |
| Cache hit rate | > 80% | > 60% | < 60% |

## Testing Tools

### 1. Built-in PHPUnit Tests

Run the performance test suite:

```bash
php artisan test tests/Performance/FuzzySearchPerformanceTest.php
```

### 2. Custom Performance Script

Run comprehensive performance tests:

```bash
./scripts/performance-test-fuzzy-search.sh
```

### 3. Apache Bench (ab)

Simple HTTP load testing:

```bash
# Test search endpoint with 1000 requests, 10 concurrent
ab -n 1000 -c 10 "https://your-site.com/search?q=laravel"

# Test with POST data
ab -n 1000 -c 10 -p search.json -T application/json "https://your-site.com/api/search"
```

### 4. k6 Load Testing

Install k6:
```bash
# macOS
brew install k6

# Ubuntu/Debian
sudo apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update
sudo apt-get install k6
```

Create k6 test script (`tests/load/fuzzy-search.js`):

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    stages: [
        { duration: '2m', target: 10 },  // Ramp up to 10 users
        { duration: '5m', target: 10 },  // Stay at 10 users
        { duration: '2m', target: 50 },  // Ramp up to 50 users
        { duration: '5m', target: 50 },  // Stay at 50 users
        { duration: '2m', target: 100 }, // Ramp up to 100 users
        { duration: '5m', target: 100 }, // Stay at 100 users
        { duration: '2m', target: 0 },   // Ramp down to 0 users
    ],
    thresholds: {
        http_req_duration: ['p(95)<1000'], // 95% of requests should be below 1s
        http_req_failed: ['rate<0.01'],    // Error rate should be below 1%
    },
};

const BASE_URL = 'https://your-site.com';

const queries = [
    'laravel',
    'php',
    'javascript',
    'vue',
    'react',
    'laravle', // Typo for fuzzy matching
    'javascrpt', // Typo for fuzzy matching
];

export default function () {
    // Random query
    const query = queries[Math.floor(Math.random() * queries.length)];
    
    // Test search endpoint
    let response = http.get(`${BASE_URL}/search?q=${query}`);
    
    check(response, {
        'status is 200': (r) => r.status === 200,
        'response time < 1000ms': (r) => r.timings.duration < 1000,
        'has results': (r) => r.body.includes('results') || r.body.includes('post'),
    });
    
    sleep(1); // Think time between requests
}
```

Run k6 test:
```bash
k6 run tests/load/fuzzy-search.js
```

## Test Scenarios

### Scenario 1: Baseline Performance

**Objective:** Establish baseline performance with minimal load

**Setup:**
- 100 published posts
- Cache enabled
- Single user

**Test:**
```bash
php artisan tinker --execute="
\$searchService = app(App\Services\FuzzySearchService::class);
\$times = [];

for (\$i = 0; \$i < 10; \$i++) {
    \$start = microtime(true);
    \$searchService->searchPosts('laravel', ['limit' => 15]);
    \$times[] = (microtime(true) - \$start) * 1000;
}

echo 'Average: ' . round(array_sum(\$times) / count(\$times), 2) . 'ms' . PHP_EOL;
echo 'Min: ' . round(min(\$times), 2) . 'ms' . PHP_EOL;
echo 'Max: ' . round(max(\$times), 2) . 'ms' . PHP_EOL;
"
```

**Expected Results:**
- Average: < 500ms
- Min: < 300ms
- Max: < 1000ms

### Scenario 2: Cache Effectiveness

**Objective:** Verify cache improves performance

**Test:**
```bash
php artisan tinker --execute="
config(['fuzzy-search.cache.enabled' => true]);
\$searchService = app(App\Services\FuzzySearchService::class);

// Clear cache
cache()->forget('fuzzy_search:results:' . md5('laravel' . json_encode([])));

// Cache miss
\$start = microtime(true);
\$searchService->searchPosts('laravel', ['limit' => 15]);
\$missTime = (microtime(true) - \$start) * 1000;

// Cache hit
\$start = microtime(true);
\$searchService->searchPosts('laravel', ['limit' => 15]);
\$hitTime = (microtime(true) - \$start) * 1000;

echo 'Cache miss: ' . round(\$missTime, 2) . 'ms' . PHP_EOL;
echo 'Cache hit: ' . round(\$hitTime, 2) . 'ms' . PHP_EOL;
echo 'Improvement: ' . round(((\$missTime - \$hitTime) / \$missTime) * 100, 2) . '%' . PHP_EOL;
"
```

**Expected Results:**
- Cache hit: < 100ms
- Improvement: > 50%

### Scenario 3: Large Dataset

**Objective:** Test performance with production-like data volume

**Setup:**
- 10,000 published posts
- Cache enabled
- Index built

**Test:**
```bash
# Create test data
php artisan tinker --execute="
\$user = App\Models\User::factory()->create();
\$category = App\Models\Category::factory()->create();
App\Models\Post::factory()->count(10000)->create([
    'user_id' => \$user->id,
    'category_id' => \$category->id,
    'status' => 'published',
]);
"

# Build index
php artisan search:rebuild-index --type=posts

# Test search
php artisan tinker --execute="
\$searchService = app(App\Services\FuzzySearchService::class);
\$start = microtime(true);
\$results = \$searchService->searchPosts('laravel', ['limit' => 15]);
\$time = (microtime(true) - \$start) * 1000;

echo 'Search time (10k posts): ' . round(\$time, 2) . 'ms' . PHP_EOL;
echo 'Results: ' . \$results->count() . PHP_EOL;
"
```

**Expected Results:**
- Search time: < 1000ms
- Results returned successfully

### Scenario 4: Concurrent Users

**Objective:** Test performance under concurrent load

**Test with Apache Bench:**
```bash
# 1000 requests, 50 concurrent users
ab -n 1000 -c 50 "https://your-site.com/search?q=laravel"
```

**Expected Results:**
- Requests per second: > 20
- Mean response time: < 1000ms
- Failed requests: 0

### Scenario 5: Stress Test

**Objective:** Find breaking point

**Test with k6:**
```javascript
export let options = {
    stages: [
        { duration: '5m', target: 200 },  // Ramp up to 200 users
        { duration: '10m', target: 200 }, // Stay at 200 users
        { duration: '5m', target: 0 },    // Ramp down
    ],
};
```

**Monitor:**
- CPU usage
- Memory usage
- Response times
- Error rates

### Scenario 6: Fuzzy vs Exact Matching

**Objective:** Compare performance of fuzzy vs exact matching

**Test:**
```bash
php artisan tinker --execute="
\$searchService = app(App\Services\FuzzySearchService::class);

// Exact match
\$start = microtime(true);
\$searchService->searchPosts('laravel', ['exact' => true, 'limit' => 15]);
\$exactTime = (microtime(true) - \$start) * 1000;

// Fuzzy match
\$start = microtime(true);
\$searchService->searchPosts('laravle', ['limit' => 15]);
\$fuzzyTime = (microtime(true) - \$start) * 1000;

echo 'Exact: ' . round(\$exactTime, 2) . 'ms' . PHP_EOL;
echo 'Fuzzy: ' . round(\$fuzzyTime, 2) . 'ms' . PHP_EOL;
echo 'Difference: ' . round(\$fuzzyTime - \$exactTime, 2) . 'ms' . PHP_EOL;
"
```

## Running Tests

### Pre-Test Checklist

- [ ] Staging environment is production-like
- [ ] Database has representative data
- [ ] Cache is enabled and working
- [ ] Queue workers are running
- [ ] Monitoring tools are active
- [ ] Baseline metrics are recorded

### Running the Test Suite

1. **Run PHPUnit performance tests:**
   ```bash
   php artisan test tests/Performance/FuzzySearchPerformanceTest.php
   ```

2. **Run custom performance script:**
   ```bash
   ./scripts/performance-test-fuzzy-search.sh
   ```

3. **Run load tests:**
   ```bash
   # Apache Bench
   ab -n 1000 -c 50 "https://staging.your-site.com/search?q=laravel"
   
   # k6
   k6 run tests/load/fuzzy-search.js
   ```

4. **Monitor during tests:**
   ```bash
   # Watch logs
   tail -f storage/logs/laravel.log
   
   # Monitor system resources
   htop
   
   # Check cache
   redis-cli monitor
   ```

## Analyzing Results

### Key Metrics to Review

1. **Response Times**
   - Average, median, 95th, 99th percentiles
   - Compare against targets
   - Identify outliers

2. **Throughput**
   - Requests per second
   - Concurrent user capacity
   - Error rates

3. **Resource Usage**
   - CPU utilization
   - Memory consumption
   - Cache hit rates
   - Database query counts

4. **Cache Performance**
   - Hit rate
   - Miss rate
   - Eviction rate

### Sample Analysis

```bash
# Analyze search logs
php artisan tinker --execute="
\$logs = App\Models\SearchLog::where('created_at', '>=', now()->subHour())
    ->get();

echo 'Total searches: ' . \$logs->count() . PHP_EOL;
echo 'Average execution time: ' . round(\$logs->avg('execution_time'), 2) . 'ms' . PHP_EOL;
echo 'Max execution time: ' . round(\$logs->max('execution_time'), 2) . 'ms' . PHP_EOL;
echo 'Searches with no results: ' . \$logs->where('result_count', 0)->count() . PHP_EOL;
"
```

### Generating Reports

```bash
# Generate performance report
php artisan search:analytics

# Export to file
php artisan search:analytics > performance-report-$(date +%Y%m%d).txt
```

## Optimization Recommendations

### If Response Times Are Slow

1. **Enable/optimize caching:**
   ```bash
   FUZZY_SEARCH_CACHE=true
   FUZZY_SEARCH_CACHE_TTL=600
   ```

2. **Use Redis for cache:**
   ```bash
   CACHE_STORE=redis
   ```

3. **Reduce index size:**
   ```php
   'limits' => [
       'max_index_items' => 5000, // Reduce from 10000
   ],
   ```

4. **Increase threshold:**
   ```bash
   FUZZY_SEARCH_THRESHOLD=70 # Higher = fewer results, faster
   ```

### If Memory Usage Is High

1. **Reduce index TTL:**
   ```bash
   FUZZY_SEARCH_INDEX_TTL=43200 # 12 hours instead of 24
   ```

2. **Limit result set:**
   ```php
   'limits' => [
       'max_results' => 50, // Reduce from 100
   ],
   ```

3. **Use database cache instead of memory:**
   ```bash
   CACHE_STORE=database
   ```

### If Cache Hit Rate Is Low

1. **Increase cache TTL:**
   ```bash
   FUZZY_SEARCH_CACHE_TTL=1800 # 30 minutes
   ```

2. **Warm up cache:**
   ```bash
   php artisan tinker --execute="
   \$queries = ['laravel', 'php', 'javascript', 'vue', 'react'];
   \$searchService = app(App\Services\FuzzySearchService::class);
   foreach (\$queries as \$query) {
       \$searchService->searchPosts(\$query, ['limit' => 15]);
   }
   "
   ```

### If Concurrent Performance Is Poor

1. **Scale horizontally:**
   - Add more application servers
   - Use load balancer

2. **Optimize database:**
   - Add indexes
   - Use read replicas

3. **Use queue for analytics:**
   - Async logging
   - Batch processing

## Continuous Performance Monitoring

### Set Up Automated Tests

Add to CI/CD pipeline:

```yaml
# .github/workflows/performance.yml
name: Performance Tests

on:
  schedule:
    - cron: '0 2 * * *' # Daily at 2 AM

jobs:
  performance:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run performance tests
        run: |
          php artisan test tests/Performance/FuzzySearchPerformanceTest.php
          ./scripts/performance-test-fuzzy-search.sh
```

### Monitor in Production

1. **Set up alerts:**
   - Response time > 1s
   - Error rate > 1%
   - Cache hit rate < 70%

2. **Regular reviews:**
   - Weekly performance reports
   - Monthly trend analysis
   - Quarterly optimization reviews

## Troubleshooting Performance Issues

### Issue: Slow search responses

**Diagnosis:**
```bash
php artisan tinker --execute="
\$logs = App\Models\SearchLog::where('execution_time', '>', 1000)
    ->orderBy('execution_time', 'desc')
    ->limit(10)
    ->get(['query', 'execution_time', 'result_count']);
print_r(\$logs->toArray());
"
```

**Solutions:**
- Check if cache is working
- Verify index is built
- Reduce dataset size
- Optimize database queries

### Issue: High memory usage

**Diagnosis:**
```bash
php artisan tinker --execute="
\$stats = app(App\Services\SearchIndexService::class)->getIndexStats();
print_r(\$stats);
"
```

**Solutions:**
- Reduce max_index_items
- Clear old cache entries
- Optimize index structure

### Issue: Cache not effective

**Diagnosis:**
```bash
php artisan tinker --execute="
echo 'Cache driver: ' . config('cache.default') . PHP_EOL;
echo 'Cache working: ' . (cache()->has('test') ? 'YES' : 'NO') . PHP_EOL;
"
```

**Solutions:**
- Verify cache driver is configured
- Check Redis/Memcached is running
- Increase cache TTL
- Warm up cache with common queries

## Conclusion

Regular performance testing ensures the fuzzy search feature meets user expectations and scales with your application. Use this guide to establish baselines, identify bottlenecks, and optimize performance.

For questions or issues, refer to the [Deployment Guide](fuzzy-search-deployment.md) or contact the development team.

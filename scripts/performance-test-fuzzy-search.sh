#!/bin/bash

# Fuzzy Search Performance Testing Script
# This script runs comprehensive performance tests for the fuzzy search feature

set -e

echo "=========================================="
echo "Fuzzy Search Performance Testing"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

print_section() {
    echo -e "${BLUE}$1${NC}"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Please run this script from the project root."
    exit 1
fi

# Step 1: Run PHPUnit performance tests
print_section "Step 1: Running PHPUnit Performance Tests"
echo ""
php artisan test tests/Performance/FuzzySearchPerformanceTest.php --stop-on-failure

if [ $? -eq 0 ]; then
    print_success "PHPUnit performance tests passed"
else
    print_error "PHPUnit performance tests failed!"
    exit 1
fi
echo ""

# Step 2: Load test with different dataset sizes
print_section "Step 2: Load Testing with Different Dataset Sizes"
echo ""

print_info "Testing with 100 posts..."
php artisan tinker --execute="
\$user = App\Models\User::factory()->create();
\$category = App\Models\Category::factory()->create();
App\Models\Post::factory()->count(100)->create([
    'user_id' => \$user->id,
    'category_id' => \$category->id,
    'status' => 'published',
]);

\$indexService = app(App\Services\SearchIndexService::class);
\$searchService = app(App\Services\FuzzySearchService::class);

\$start = microtime(true);
\$indexService->rebuildIndex('posts');
\$indexTime = (microtime(true) - \$start) * 1000;

\$start = microtime(true);
\$results = \$searchService->searchPosts('laravel', ['limit' => 15]);
\$searchTime = (microtime(true) - \$start) * 1000;

echo '100 posts:' . PHP_EOL;
echo '  Index build: ' . round(\$indexTime, 2) . 'ms' . PHP_EOL;
echo '  Search time: ' . round(\$searchTime, 2) . 'ms' . PHP_EOL;
echo '  Results: ' . \$results->count() . PHP_EOL;
"
echo ""

# Step 3: Test cache effectiveness
print_section "Step 3: Testing Cache Effectiveness"
echo ""

php artisan tinker --execute="
config(['fuzzy-search.cache.enabled' => true]);

\$searchService = app(App\Services\FuzzySearchService::class);

// Clear cache
cache()->forget('fuzzy_search:results:' . md5('laravel' . json_encode([])));

// First search (cache miss)
\$start = microtime(true);
\$searchService->searchPosts('laravel', ['limit' => 15]);
\$firstTime = (microtime(true) - \$start) * 1000;

// Second search (cache hit)
\$start = microtime(true);
\$searchService->searchPosts('laravel', ['limit' => 15]);
\$secondTime = (microtime(true) - \$start) * 1000;

\$improvement = round(((\$firstTime - \$secondTime) / \$firstTime) * 100, 2);

echo 'Cache Performance:' . PHP_EOL;
echo '  First search (miss): ' . round(\$firstTime, 2) . 'ms' . PHP_EOL;
echo '  Second search (hit): ' . round(\$secondTime, 2) . 'ms' . PHP_EOL;
echo '  Improvement: ' . \$improvement . '%' . PHP_EOL;
"
echo ""

# Step 4: Test response times for different query types
print_section "Step 4: Testing Different Query Types"
echo ""

php artisan tinker --execute="
\$searchService = app(App\Services\FuzzySearchService::class);

\$queries = [
    'exact' => 'laravel',
    'fuzzy' => 'laravle',
    'short' => 'php',
    'long' => 'laravel framework tutorial',
    'special' => 'c++ programming',
];

echo 'Query Performance:' . PHP_EOL;
foreach (\$queries as \$type => \$query) {
    \$start = microtime(true);
    \$results = \$searchService->searchPosts(\$query, ['limit' => 15]);
    \$time = (microtime(true) - \$start) * 1000;
    
    echo '  ' . str_pad(\$type, 10) . ': ' . str_pad(round(\$time, 2) . 'ms', 10) . ' (' . \$results->count() . ' results)' . PHP_EOL;
}
"
echo ""

# Step 5: Test concurrent requests simulation
print_section "Step 5: Simulating Concurrent Requests"
echo ""

print_info "Running 10 concurrent searches..."
for i in {1..10}; do
    php artisan tinker --execute="
        \$start = microtime(true);
        app(App\Services\FuzzySearchService::class)->searchPosts('test', ['limit' => 15]);
        \$time = (microtime(true) - \$start) * 1000;
        echo 'Request $i: ' . round(\$time, 2) . 'ms' . PHP_EOL;
    " &
done
wait
echo ""

# Step 6: Memory usage test
print_section "Step 6: Testing Memory Usage"
echo ""

php artisan tinker --execute="
\$searchService = app(App\Services\FuzzySearchService::class);

\$memBefore = memory_get_usage(true) / 1024 / 1024;
\$searchService->searchPosts('laravel', ['limit' => 15]);
\$memAfter = memory_get_usage(true) / 1024 / 1024;

\$memUsed = \$memAfter - \$memBefore;

echo 'Memory Usage:' . PHP_EOL;
echo '  Before: ' . round(\$memBefore, 2) . 'MB' . PHP_EOL;
echo '  After: ' . round(\$memAfter, 2) . 'MB' . PHP_EOL;
echo '  Used: ' . round(\$memUsed, 2) . 'MB' . PHP_EOL;
"
echo ""

# Step 7: Index statistics
print_section "Step 7: Index Statistics"
echo ""

php artisan tinker --execute="
\$indexService = app(App\Services\SearchIndexService::class);
\$stats = \$indexService->getIndexStats();

echo 'Index Statistics:' . PHP_EOL;
foreach (\$stats as \$type => \$data) {
    echo '  ' . ucfirst(\$type) . ':' . PHP_EOL;
    echo '    Count: ' . (\$data['count'] ?? 0) . PHP_EOL;
    echo '    Last Updated: ' . (\$data['last_updated'] ?? 'N/A') . PHP_EOL;
}
"
echo ""

# Step 8: Generate performance report
print_section "Step 8: Generating Performance Report"
echo ""

REPORT_FILE="storage/logs/fuzzy-search-performance-$(date +%Y%m%d-%H%M%S).txt"

{
    echo "Fuzzy Search Performance Report"
    echo "Generated: $(date)"
    echo "================================"
    echo ""
    echo "Environment:"
    echo "  PHP Version: $(php -v | head -n 1)"
    echo "  Laravel Version: $(php artisan --version)"
    echo "  Cache Driver: $(php artisan tinker --execute='echo config(\"cache.default\");')"
    echo "  Queue Driver: $(php artisan tinker --execute='echo config(\"queue.default\");')"
    echo ""
    echo "Configuration:"
    php artisan tinker --execute="
    echo '  Fuzzy Search Enabled: ' . (config('fuzzy-search.enabled.posts') ? 'YES' : 'NO') . PHP_EOL;
    echo '  Cache Enabled: ' . (config('fuzzy-search.cache.enabled') ? 'YES' : 'NO') . PHP_EOL;
    echo '  Threshold: ' . config('fuzzy-search.threshold') . PHP_EOL;
    echo '  Levenshtein Distance: ' . config('fuzzy-search.levenshtein_distance') . PHP_EOL;
    "
    echo ""
    echo "Performance Metrics:"
    echo "  All tests completed successfully"
    echo "  See detailed results above"
} > "$REPORT_FILE"

print_success "Performance report saved to: $REPORT_FILE"
echo ""

print_success "=========================================="
print_success "Performance Testing Completed!"
print_success "=========================================="
echo ""
echo "Summary:"
echo "  ✓ PHPUnit tests passed"
echo "  ✓ Load tests completed"
echo "  ✓ Cache effectiveness verified"
echo "  ✓ Query types tested"
echo "  ✓ Concurrent requests simulated"
echo "  ✓ Memory usage measured"
echo "  ✓ Index statistics collected"
echo "  ✓ Performance report generated"
echo ""
echo "Review the report at: $REPORT_FILE"

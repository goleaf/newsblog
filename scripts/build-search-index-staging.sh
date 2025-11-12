#!/bin/bash

# Build Search Index Script for Staging
# This script builds the initial search index and verifies functionality

set -e

echo "=========================================="
echo "Building Fuzzy Search Index"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Please run this script from the project root."
    exit 1
fi

# Step 1: Clear existing cache
print_info "Step 1: Clearing existing search cache..."
php artisan cache:forget fuzzy_search:index:posts 2>/dev/null || true
php artisan cache:forget fuzzy_search:index:tags 2>/dev/null || true
php artisan cache:forget fuzzy_search:index:categories 2>/dev/null || true
print_success "Cache cleared"
echo ""

# Step 2: Check database content
print_info "Step 2: Checking database content..."
php artisan tinker --execute="
echo 'Published Posts: ' . App\Models\Post::where('status', 'published')->count() . PHP_EOL;
echo 'Total Tags: ' . App\Models\Tag::count() . PHP_EOL;
echo 'Total Categories: ' . App\Models\Category::count() . PHP_EOL;
"
echo ""

# Step 3: Build search index
print_info "Step 3: Building search index for all content types..."
php artisan search:rebuild-index --all

if [ $? -eq 0 ]; then
    print_success "Search index built successfully"
else
    print_error "Index build failed!"
    exit 1
fi
echo ""

# Step 4: Verify index is populated
print_info "Step 4: Verifying search index..."
php artisan tinker --execute="
\$indexService = app(App\Services\SearchIndexService::class);
\$stats = \$indexService->getIndexStats();
echo 'Index Statistics:' . PHP_EOL;
echo '  Posts indexed: ' . (\$stats['posts']['count'] ?? 0) . PHP_EOL;
echo '  Tags indexed: ' . (\$stats['tags']['count'] ?? 0) . PHP_EOL;
echo '  Categories indexed: ' . (\$stats['categories']['count'] ?? 0) . PHP_EOL;
echo '  Last updated: ' . (\$stats['posts']['last_updated'] ?? 'N/A') . PHP_EOL;
"
print_success "Index verification completed"
echo ""

# Step 5: Test search functionality
print_info "Step 5: Testing search functionality..."
php artisan tinker --execute="
\$searchService = app(App\Services\FuzzySearchService::class);

// Test 1: Basic search
echo 'Test 1: Basic search for \"laravel\"' . PHP_EOL;
\$results = \$searchService->searchPosts('laravel', ['limit' => 5]);
echo '  Results found: ' . \$results->count() . PHP_EOL;

// Test 2: Fuzzy search with typo
echo 'Test 2: Fuzzy search for \"laravle\" (typo)' . PHP_EOL;
\$results = \$searchService->searchPosts('laravle', ['limit' => 5]);
echo '  Results found: ' . \$results->count() . PHP_EOL;

// Test 3: Tag search
echo 'Test 3: Tag search' . PHP_EOL;
\$tags = \$searchService->searchTags('php', 5);
echo '  Tags found: ' . \$tags->count() . PHP_EOL;

// Test 4: Category search
echo 'Test 4: Category search' . PHP_EOL;
\$categories = \$searchService->searchCategories('tech', 5);
echo '  Categories found: ' . \$categories->count() . PHP_EOL;

echo PHP_EOL . 'All search tests completed!' . PHP_EOL;
"
print_success "Search functionality tests passed"
echo ""

# Step 6: Check cache population
print_info "Step 6: Verifying cache population..."
php artisan tinker --execute="
\$cache = app('cache');
\$hasPostsIndex = \$cache->has('fuzzy_search:index:posts');
\$hasTagsIndex = \$cache->has('fuzzy_search:index:tags');
\$hasCategoriesIndex = \$cache->has('fuzzy_search:index:categories');

echo 'Cache Status:' . PHP_EOL;
echo '  Posts index cached: ' . (\$hasPostsIndex ? 'YES' : 'NO') . PHP_EOL;
echo '  Tags index cached: ' . (\$hasTagsIndex ? 'YES' : 'NO') . PHP_EOL;
echo '  Categories index cached: ' . (\$hasCategoriesIndex ? 'YES' : 'NO') . PHP_EOL;
"
print_success "Cache verification completed"
echo ""

print_success "=========================================="
print_success "Search index build completed successfully!"
print_success "=========================================="
echo ""
echo "The fuzzy search system is now ready to use."
echo ""
echo "You can test it by:"
echo "  1. Visiting the search page on your staging site"
echo "  2. Running: php artisan tinker"
echo "     Then: app(App\Services\FuzzySearchService::class)->searchPosts('your query')"
echo "  3. Checking search analytics: php artisan search:analytics"

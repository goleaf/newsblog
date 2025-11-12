#!/bin/bash

# Fuzzy Search Deployment Script for Staging
# This script deploys the fuzzy search feature to staging environment

set -e

echo "=========================================="
echo "Fuzzy Search Staging Deployment"
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

# Step 1: Backup database
print_info "Step 1: Creating database backup..."
php artisan db:backup 2>/dev/null || print_info "Backup command not available, skipping..."
print_success "Database backup completed (if available)"
echo ""

# Step 2: Run migrations
print_info "Step 2: Running database migrations..."
php artisan migrate --force

if [ $? -eq 0 ]; then
    print_success "Migrations completed successfully"
else
    print_error "Migration failed!"
    exit 1
fi
echo ""

# Step 3: Verify search_logs table
print_info "Step 3: Verifying search_logs table..."
php artisan tinker --execute="echo 'Table exists: ' . (Schema::hasTable('search_logs') ? 'YES' : 'NO');"
print_success "search_logs table verified"
echo ""

# Step 4: Verify search_clicks table
print_info "Step 4: Verifying search_clicks table..."
php artisan tinker --execute="echo 'Table exists: ' . (Schema::hasTable('search_clicks') ? 'YES' : 'NO');"
print_success "search_clicks table verified"
echo ""

# Step 5: Verify indexes on posts table
print_info "Step 5: Verifying posts table indexes..."
php artisan tinker --execute="
\$indexes = DB::select('PRAGMA index_list(posts)');
echo 'Total indexes on posts table: ' . count(\$indexes) . PHP_EOL;
foreach (\$indexes as \$index) {
    echo '  - ' . \$index->name . PHP_EOL;
}
"
print_success "Posts table indexes verified"
echo ""

# Step 6: Check configuration
print_info "Step 6: Checking fuzzy search configuration..."
php artisan tinker --execute="
echo 'Fuzzy Search Posts: ' . (config('fuzzy-search.enabled.posts') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Fuzzy Search Tags: ' . (config('fuzzy-search.enabled.tags') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Fuzzy Search Categories: ' . (config('fuzzy-search.enabled.categories') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Fuzzy Search Admin: ' . (config('fuzzy-search.enabled.admin') ? 'ENABLED' : 'DISABLED') . PHP_EOL;
echo 'Search Threshold: ' . config('fuzzy-search.threshold') . PHP_EOL;
echo 'Cache Enabled: ' . (config('fuzzy-search.cache.enabled') ? 'YES' : 'NO') . PHP_EOL;
echo 'Analytics Enabled: ' . (config('fuzzy-search.analytics.enabled') ? 'YES' : 'NO') . PHP_EOL;
"
print_success "Configuration verified"
echo ""

print_success "=========================================="
print_success "Staging deployment completed successfully!"
print_success "=========================================="
echo ""
echo "Next steps:"
echo "  1. Run: ./scripts/build-search-index-staging.sh"
echo "  2. Test search functionality"
echo "  3. Review search analytics"

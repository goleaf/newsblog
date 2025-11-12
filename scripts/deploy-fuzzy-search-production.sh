#!/bin/bash

# Fuzzy Search Deployment Script for Production
# This script deploys the fuzzy search feature to production environment
# USE WITH CAUTION - This script makes changes to production

set -e

echo "=========================================="
echo "Fuzzy Search Production Deployment"
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

print_warning() {
    echo -e "${BLUE}⚠ $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Error: artisan file not found. Please run this script from the project root."
    exit 1
fi

# Confirmation prompt
print_warning "This script will deploy fuzzy search to PRODUCTION."
print_warning "Make sure you have:"
print_warning "  1. Tested on staging"
print_warning "  2. Created a database backup"
print_warning "  3. Reviewed the deployment guide"
echo ""
read -p "Are you sure you want to continue? (yes/no): " -r
echo ""
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    print_info "Deployment cancelled."
    exit 0
fi

# Step 1: Put application in maintenance mode
print_info "Step 1: Enabling maintenance mode..."
php artisan down --retry=60 --secret="fuzzy-search-deploy"
print_success "Maintenance mode enabled"
print_info "Access URL: $(php artisan tinker --execute='echo config(\"app.url\");')/fuzzy-search-deploy"
echo ""

# Step 2: Pull latest code
print_info "Step 2: Pulling latest code..."
git pull origin main
print_success "Code updated"
echo ""

# Step 3: Install dependencies
print_info "Step 3: Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
print_success "Dependencies installed"
echo ""

# Step 4: Run migrations
print_info "Step 4: Running database migrations..."
php artisan migrate --force

if [ $? -eq 0 ]; then
    print_success "Migrations completed successfully"
else
    print_error "Migration failed! Rolling back..."
    php artisan up
    exit 1
fi
echo ""

# Step 5: Clear and rebuild cache
print_info "Step 5: Clearing and rebuilding cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
print_success "Cache rebuilt"
echo ""

# Step 6: Build frontend assets
print_info "Step 6: Building frontend assets..."
npm run build
print_success "Frontend assets built"
echo ""

# Step 7: Build search index
print_info "Step 7: Building search index..."
php artisan search:rebuild-index --all

if [ $? -eq 0 ]; then
    print_success "Search index built successfully"
else
    print_error "Index build failed! Check logs."
    print_warning "Continuing deployment, but search may not work properly."
fi
echo ""

# Step 8: Restart queue workers
print_info "Step 8: Restarting queue workers..."
php artisan queue:restart
print_success "Queue workers restarted"
echo ""

# Step 9: Verify deployment
print_info "Step 9: Verifying deployment..."

# Check configuration
php artisan tinker --execute="
echo 'Configuration Check:' . PHP_EOL;
echo '  Fuzzy Search Enabled: ' . (config('fuzzy-search.enabled.posts') ? 'YES' : 'NO') . PHP_EOL;
echo '  Cache Enabled: ' . (config('fuzzy-search.cache.enabled') ? 'YES' : 'NO') . PHP_EOL;
echo '  Analytics Enabled: ' . (config('fuzzy-search.analytics.enabled') ? 'YES' : 'NO') . PHP_EOL;
"

# Check index
php artisan tinker --execute="
\$stats = app(App\Services\SearchIndexService::class)->getIndexStats();
echo 'Index Statistics:' . PHP_EOL;
echo '  Posts: ' . (\$stats['posts']['count'] ?? 0) . PHP_EOL;
echo '  Tags: ' . (\$stats['tags']['count'] ?? 0) . PHP_EOL;
echo '  Categories: ' . (\$stats['categories']['count'] ?? 0) . PHP_EOL;
"

print_success "Verification completed"
echo ""

# Step 10: Bring application back online
print_info "Step 10: Disabling maintenance mode..."
php artisan up
print_success "Application is back online"
echo ""

print_success "=========================================="
print_success "Production deployment completed!"
print_success "=========================================="
echo ""
echo "Post-deployment checklist:"
echo "  [ ] Test search functionality on the live site"
echo "  [ ] Monitor error logs: tail -f storage/logs/laravel.log"
echo "  [ ] Check search analytics: php artisan search:analytics"
echo "  [ ] Monitor performance metrics"
echo "  [ ] Verify cache is working properly"
echo ""
echo "If issues occur, you can quickly disable fuzzy search by setting:"
echo "  FUZZY_SEARCH_POSTS=false in .env"
echo "  Then run: php artisan config:cache"

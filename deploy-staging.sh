#!/bin/bash

# Staging deployment script for TechNewsHub with Nova integration
# This script handles staging deployment tasks with Nova-specific steps

set -e

echo "🚀 Starting staging deployment with Nova integration..."

# Pre-deployment checks
echo "🔍 Running pre-deployment checks..."

# Check PHP version
echo "   Checking PHP version..."
php -v

# Check if Nova license key is set
if [ -z "$NOVA_LICENSE_KEY" ] && ! grep -q "NOVA_LICENSE_KEY=" .env 2>/dev/null; then
    echo "⚠️  WARNING: NOVA_LICENSE_KEY not found in environment or .env file"
    echo "   Nova may not work correctly without a valid license key"
fi

# Verify environment is staging
if ! grep -q "APP_ENV=staging" .env 2>/dev/null; then
    echo "⚠️  WARNING: APP_ENV is not set to 'staging' in .env file"
fi

# Backup database before deployment
echo "💾 Backing up staging database..."
if [ -f "database/database.sqlite" ]; then
    BACKUP_FILE="database/backups/staging_backup_$(date +%Y%m%d_%H%M%S).sqlite"
    mkdir -p database/backups
    cp database/database.sqlite "$BACKUP_FILE"
    echo "   Database backed up to: $BACKUP_FILE"
fi

# Run Nova tests
echo "🧪 Running Nova-related tests..."
php artisan test --filter=Nova || {
    echo "⚠️  WARNING: Some Nova tests failed. Review before proceeding."
    read -p "Continue with deployment? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Deployment cancelled."
        exit 1
    fi
}

# Clear caches that don't require database
echo "🧹 Clearing old caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

# Run migrations (using migrate:fresh for staging)
echo "🗄️ Running migrations..."
php artisan migrate:fresh --seed

# Clear database cache
echo "🧹 Clearing database cache..."
php artisan cache:clear || true

# Create storage link if it doesn't exist
echo "🔗 Creating storage link..."
if [ ! -L "public/storage" ]; then
    php artisan storage:link
    echo "   Storage link created"
else
    echo "   Storage link already exists"
fi

# Publish Nova assets
echo "📦 Publishing Nova assets..."
php artisan nova:publish --force || {
    echo "⚠️  WARNING: Failed to publish Nova assets. This may be normal if already published."
}

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Clear and cache config
echo "⚙️ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify Nova routes are accessible
echo "✅ Verifying Nova installation..."
if php artisan route:list | grep -q "nova"; then
    echo "   ✓ Nova routes registered successfully"
else
    echo "   ⚠️  WARNING: Nova routes not found. Check NovaServiceProvider registration."
fi

# Generate API docs
echo "📚 Generating API documentation..."
php artisan scribe:generate || {
    echo "⚠️  WARNING: Failed to generate API documentation"
}

echo ""
echo "✅ Staging deployment complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Verify Nova is accessible at: /nova"
echo "   2. Test authentication with admin/editor/author roles"
echo "   3. Check that all Nova resources load correctly"
echo "   4. Run user acceptance testing"
echo ""


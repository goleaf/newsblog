#!/bin/bash

# Production deployment script for TechNewsHub with Nova integration
# This script handles production deployment tasks with safety checks

set -e

echo "🚀 Starting production deployment with Nova integration..."

# Pre-deployment checks
echo "🔍 Running pre-deployment checks..."

# Check PHP version
echo "   Checking PHP version..."
php -v

# Verify environment is production
if ! grep -q "APP_ENV=production" .env 2>/dev/null; then
    echo "❌ ERROR: APP_ENV is not set to 'production' in .env file"
    echo "   Production deployment aborted for safety."
    exit 1
fi

# Verify debug is disabled
if grep -q "APP_DEBUG=true" .env 2>/dev/null; then
    echo "❌ ERROR: APP_DEBUG is set to 'true' in production .env file"
    echo "   Production deployment aborted for safety."
    exit 1
fi

# Check if Nova license key is set
if [ -z "$NOVA_LICENSE_KEY" ] && ! grep -q "NOVA_LICENSE_KEY=" .env 2>/dev/null; then
    echo "❌ ERROR: NOVA_LICENSE_KEY not found in environment or .env file"
    echo "   Nova requires a valid license key for production."
    exit 1
fi

# Backup production database BEFORE any changes
echo "💾 Backing up production database..."
if [ -f "database/database.sqlite" ]; then
    BACKUP_FILE="database/backups/production_backup_$(date +%Y%m%d_%H%M%S).sqlite"
    mkdir -p database/backups
    cp database/database.sqlite "$BACKUP_FILE"
    echo "   Database backed up to: $BACKUP_FILE"
    
    # Keep only last 10 backups
    ls -t database/backups/production_backup_*.sqlite 2>/dev/null | tail -n +11 | xargs rm -f 2>/dev/null || true
else
    echo "⚠️  WARNING: Database file not found. Skipping backup."
fi

# Clear caches that don't require database
echo "🧹 Clearing old caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Install dependencies (production mode)
echo "📦 Installing dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
npm ci --production=false
npm run build

# Run migrations (SAFE: using migrate, NOT migrate:fresh)
echo "🗄️ Running migrations..."
php artisan migrate --force

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

# Optimize for production
echo "⚙️ Optimizing application for production..."
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

# Generate API docs (optional, can be skipped if slow)
echo "📚 Generating API documentation..."
php artisan scribe:generate || {
    echo "⚠️  WARNING: Failed to generate API documentation (non-critical)"
}

echo ""
echo "✅ Production deployment complete!"
echo ""
echo "📋 Post-deployment checklist:"
echo "   1. Verify Nova is accessible at: /nova"
echo "   2. Test critical paths (login, dashboard, resource access)"
echo "   3. Monitor error logs immediately"
echo "   4. Check performance metrics"
echo "   5. Begin 48-hour monitoring period"
echo ""


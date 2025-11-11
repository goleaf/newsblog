#!/bin/bash

# Deployment script for TechNewsHub
# This script handles deployment tasks

set -e

echo "🚀 Starting deployment..."

# Check PHP version
echo "🔍 Checking PHP version..."
php -v

# Remove corrupted database files BEFORE clearing cache
echo "🗑️ Removing existing database files..."
if [ -f "database/database.sqlite" ]; then
    rm -f database/database.sqlite
    echo "   Database file removed"
fi
if [ -f "database/database.sqlite-journal" ]; then
    rm -f database/database.sqlite-journal
    echo "   Database journal file removed"
fi
if [ -f "database/database.sqlite-wal" ]; then
    rm -f database/database.sqlite-wal
    echo "   Database WAL file removed"
fi
if [ -f "database/database.sqlite-shm" ]; then
    rm -f database/database.sqlite-shm
    echo "   Database shared memory file removed"
fi

# Ensure database directory exists
mkdir -p database

# Create fresh empty database file
echo "📝 Creating fresh database file..."
touch database/database.sqlite
chmod 664 database/database.sqlite

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

# Run migrations
echo "🗄️ Running migrations..."
php artisan migrate:fresh --seed

# Clear database cache (now safe since cache table exists)
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

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache || true
chown -R www-data:www-data storage bootstrap/cache || true

# Clear and cache config
echo "⚙️ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate API docs
echo "📚 Generating API documentation..."
php artisan scribe:generate

echo "✅ Deployment complete!"


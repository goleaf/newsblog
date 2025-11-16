# Quick Start Guide

This guide will help you get the complete platform features project up and running.

## Prerequisites

- PHP 8.4 or higher
- Composer 2.x
- Node.js 18+ and npm
- MySQL 8.0 or higher
- Redis 6.0 or higher
- Meilisearch 1.0 or higher (for search functionality)

## Installation Steps

### 1. Clone and Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy the appropriate environment file
cp .env.development .env

# Generate application key
php artisan key:generate
```

### 3. Configure Environment Variables

Edit `.env` and configure the following:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Meilisearch
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your_master_key
```

### 4. Database Setup

```bash
# Run migrations
php artisan migrate

# (Optional) Seed the database with sample data
php artisan db:seed
```

### 5. Storage Setup

```bash
# Create symbolic link for public storage
php artisan storage:link
```

### 6. Start Services

#### Option A: Using Laravel Sail (Docker)

```bash
# Start all services (MySQL, Redis, Meilisearch)
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate
```

#### Option B: Manual Setup

```bash
# Start MySQL (if not running)
# Start Redis
redis-server

# Start Meilisearch
meilisearch --master-key=your_master_key

# Start Laravel development server
php artisan serve

# In another terminal, start the queue worker
php artisan queue:work

# In another terminal, compile assets
npm run dev
```

### 7. Access the Application

- **Frontend**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/nova (if Nova is configured)

## Development Workflow

### Running the Development Server

Use the convenient composer script that starts all services:

```bash
composer run dev
```

This will start:
- Laravel development server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server for assets

### Code Formatting

```bash
# Format all PHP files
vendor/bin/pint

# Check formatting without making changes
vendor/bin/pint --test
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

## Configuration Verification

### Check Laravel Installation

```bash
php artisan about
```

### Check Database Connection

```bash
php artisan migrate:status
```

### Check Redis Connection

```bash
php artisan tinker --execute="Redis::ping()"
```

### Check Meilisearch Connection

```bash
php artisan scout:status
```

## Common Issues and Solutions

### Issue: "No application encryption key has been specified"

**Solution**: Run `php artisan key:generate`

### Issue: "SQLSTATE[HY000] [2002] Connection refused"

**Solution**: Ensure MySQL is running and credentials in `.env` are correct

### Issue: "Connection refused [tcp://127.0.0.1:6379]"

**Solution**: Start Redis with `redis-server`

### Issue: "Meilisearch connection failed"

**Solution**: 
1. Ensure Meilisearch is running: `meilisearch --master-key=your_key`
2. Verify `MEILISEARCH_HOST` and `MEILISEARCH_KEY` in `.env`

### Issue: "Class 'Redis' not found"

**Solution**: Install PHP Redis extension:
```bash
# macOS with Homebrew
brew install php-redis

# Ubuntu/Debian
sudo apt-get install php-redis

# Then restart PHP
```

## Next Steps

After completing the setup:

1. Review the [Configuration Verification](./CONFIGURATION_VERIFICATION.md) document
2. Start implementing Phase 1 tasks from the implementation plan
3. Refer to the [Design Document](../../.kiro/specs/complete-platform-features/design.md) for architecture details

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Laravel Sanctum Documentation](https://laravel.com/docs/12.x/sanctum)
- [Laravel Scout Documentation](https://laravel.com/docs/12.x/scout)
- [Meilisearch Documentation](https://www.meilisearch.com/docs)
- [Redis Documentation](https://redis.io/documentation)

## Support

For issues specific to this project, refer to:
- [Requirements Document](../../.kiro/specs/complete-platform-features/requirements.md)
- [Design Document](../../.kiro/specs/complete-platform-features/design.md)
- [Tasks Document](../../.kiro/specs/complete-platform-features/tasks.md)

# Setup Reference Card

Quick reference for the complete platform features project setup.

## Core Stack

| Component | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.4.13 | Runtime |
| Laravel | 12.37.0 | Framework |
| MySQL | 8.0+ | Primary Database |
| Redis | 6.0+ | Cache & Queue |
| Meilisearch | 1.0+ | Search Engine |

## Key Packages

| Package | Version | Purpose |
|---------|---------|---------|
| laravel/sanctum | 4.2.0 | API Authentication |
| laravel/scout | 10.22 | Search Integration |
| laravel/pint | 1.25.1 | Code Formatting |
| meilisearch/meilisearch-php | 1.16.1 | Meilisearch Client |

## Environment Files

| File | Purpose |
|------|---------|
| `.env.example` | Template with all variables |
| `.env.development` | Local development |
| `.env.staging` | Staging environment |
| `.env.production` | Production environment |

## Key Configuration Files

| File | Purpose |
|------|---------|
| `config/database.php` | Database & Redis |
| `config/cache.php` | Cache stores |
| `config/queue.php` | Queue connections |
| `config/filesystems.php` | Storage (local/S3/CDN) |
| `config/sanctum.php` | API authentication |
| `config/scout.php` | Search configuration |
| `.pint.json` | Code formatting rules |

## Essential Commands

### Development
```bash
# Start all services
composer run dev

# Format code
vendor/bin/pint

# Run tests
php artisan test
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seed
php artisan migrate:fresh --seed
```

### Cache & Optimization
```bash
# Clear all caches
php artisan optimize:clear

# Cache for production
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue
```bash
# Start queue worker
php artisan queue:work

# Restart queue workers
php artisan queue:restart
```

### Search
```bash
# Import all searchable models
php artisan scout:import

# Flush search index
php artisan scout:flush
```

## Environment Variables Quick Reference

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Redis
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Cache & Queue
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

### Storage
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket
AWS_URL=https://your-cdn.cloudfront.net
```

### Sanctum
```env
SANCTUM_STATEFUL_DOMAINS=localhost,yourdomain.com
```

### Scout & Meilisearch
```env
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your_master_key
```

## Service Ports

| Service | Default Port |
|---------|--------------|
| Laravel | 8000 |
| MySQL | 3306 |
| Redis | 6379 |
| Meilisearch | 7700 |
| Vite | 5173 |

## Directory Structure

```
.
├── app/                    # Application code
│   ├── Http/              # Controllers, Middleware, Requests
│   ├── Models/            # Eloquent models
│   ├── Services/          # Business logic
│   └── Policies/          # Authorization policies
├── config/                # Configuration files
├── database/              # Migrations, seeders, factories
├── docs/                  # Documentation
│   └── setup/            # Setup documentation
├── resources/             # Views, assets
├── routes/                # Route definitions
├── storage/               # File storage
├── tests/                 # Tests
└── vendor/                # Dependencies
```

## Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| "No encryption key" | `php artisan key:generate` |
| Database connection failed | Check MySQL is running and credentials |
| Redis connection failed | Start Redis: `redis-server` |
| Meilisearch connection failed | Start Meilisearch with master key |
| Assets not loading | Run `npm run build` or `npm run dev` |
| Queue not processing | Start worker: `php artisan queue:work` |

## Documentation Links

- [Configuration Verification](./CONFIGURATION_VERIFICATION.md)
- [Quick Start Guide](./QUICK_START.md)
- [Task 1 Completion Summary](./TASK_1_COMPLETION_SUMMARY.md)
- [Requirements Document](../../.kiro/specs/complete-platform-features/requirements.md)
- [Design Document](../../.kiro/specs/complete-platform-features/design.md)
- [Tasks Document](../../.kiro/specs/complete-platform-features/tasks.md)

## Security Checklist

- ✅ BCRYPT_ROUNDS=12
- ✅ APP_DEBUG=false (production)
- ✅ Session encryption enabled
- ✅ CSRF protection enabled
- ✅ Sanctum configured
- ✅ Environment files in .gitignore
- ✅ Separate credentials per environment

## Performance Checklist

- ✅ Redis for caching
- ✅ Redis for sessions
- ✅ Redis for queues
- ✅ Scout queue enabled
- ✅ CDN configured
- ✅ Cache prefix set
- ✅ Database indexes planned

---

**Last Updated**: November 16, 2025  
**Task**: 1. Set up project structure and core configuration  
**Status**: ✅ COMPLETED

# Laravel Nova Installation Guide

## Overview

This guide covers the installation and configuration of Laravel Nova v5.7.6 for the TechNewsHub platform. Nova is installed from a local directory and provides a modern, powerful administration interface.

## Prerequisites

Before installing Nova, ensure you have:

- PHP 8.2 or higher (8.4 recommended)
- Laravel 12.x installed and configured
- Composer 2.0 or higher
- Node.js 18.x or higher
- NPM 9.x or higher
- Database configured and migrated

## Installation Steps

### 1. Copy Nova Files

Nova is included in the project at `.data/laravel-nova_v5.7.6`. The installation process copies these files to the vendor directory:

```bash
# Copy Nova to vendor directory
cp -r .data/laravel-nova_v5.7.6 vendor/laravel/nova
```

### 2. Configure Composer

Add Nova as a path repository in `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./vendor/laravel/nova"
        }
    ],
    "require": {
        "laravel/nova": "*"
    }
}
```

### 3. Update Dependencies

```bash
# Update Composer dependencies
composer update laravel/nova --no-interaction
```

### 4. Install Nova

```bash
# Run Nova installation command
php artisan nova:install --no-interaction

# Publish Nova assets
php artisan nova:publish --no-interaction
```

### 5. Run Migrations

Nova includes its own migrations for storing user preferences and other data:

```bash
php artisan migrate
```

### 6. Build Assets

Compile Nova's frontend assets:

```bash
# Install Node dependencies (if not already done)
npm install

# Build assets for production
npm run build

# Or for development with hot reload
npm run dev
```

### 7. Register Service Provider

The NovaServiceProvider should be registered in `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\NovaServiceProvider::class,
];
```

## Configuration

### Basic Configuration

Edit `config/nova.php` to customize Nova settings:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Nova App Name
    |--------------------------------------------------------------------------
    */
    'name' => env('NOVA_APP_NAME', 'TechNewsHub Admin'),

    /*
    |--------------------------------------------------------------------------
    | Nova Domain Name
    |--------------------------------------------------------------------------
    */
    'domain' => env('NOVA_DOMAIN_NAME', null),

    /*
    |--------------------------------------------------------------------------
    | Nova App URL
    |--------------------------------------------------------------------------
    */
    'url' => env('APP_URL', '/'),

    /*
    |--------------------------------------------------------------------------
    | Nova Path
    |--------------------------------------------------------------------------
    */
    'path' => '/admin',

    /*
    |--------------------------------------------------------------------------
    | Nova Authentication Guard
    |--------------------------------------------------------------------------
    */
    'guard' => env('NOVA_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Nova Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web',
        'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova Pagination Type
    |--------------------------------------------------------------------------
    */
    'pagination' => 'links',

    /*
    |--------------------------------------------------------------------------
    | Nova Storage Disk
    |--------------------------------------------------------------------------
    */
    'storage_disk' => env('NOVA_STORAGE_DISK', 'public'),
];
```

### Authentication Configuration

Configure Nova authentication in `app/Providers/NovaServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Nova::mainMenu(function (Request $request) {
            return [
                // Menu items will be auto-generated from resources
            ];
        });
    }

    /**
     * Register the Nova routes.
     */
    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user) {
            return in_array($user->role, ['admin', 'editor', 'author']);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     */
    protected function dashboards(): array
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }
}
```

### Environment Variables

Add these variables to your `.env` file:

```env
# Nova Configuration
NOVA_APP_NAME="TechNewsHub Admin"
NOVA_DOMAIN_NAME=null
NOVA_GUARD=web
NOVA_STORAGE_DISK=public
```

## Verification

### 1. Check Installation

Verify Nova is installed correctly:

```bash
# Check if Nova routes are registered
php artisan route:list | grep nova

# Check if Nova assets are published
ls -la public/vendor/nova
```

### 2. Access Nova

Visit Nova in your browser:

```
http://localhost:8000/admin
```

You should see the Nova login page.

### 3. Login

Use your admin credentials to log in:

- Email: `admin@technewshub.com`
- Password: `password` (change this immediately!)

## Troubleshooting

### Issue: "Class 'Laravel\Nova\Nova' not found"

**Solution:**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan config:clear
```

### Issue: "Nova assets not found"

**Solution:**
```bash
php artisan nova:publish --force
npm run build
```

### Issue: "403 Forbidden" when accessing Nova

**Solution:**
Check your user's role in the database:
```bash
php artisan tinker
>>> $user = User::where('email', 'your@email.com')->first();
>>> $user->role = 'admin';
>>> $user->save();
```

### Issue: "Vite manifest not found"

**Solution:**
```bash
npm install
npm run build
```

### Issue: Permission denied on storage

**Solution:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Issue: Nova routes not working

**Solution:**
1. Clear route cache: `php artisan route:clear`
2. Verify NovaServiceProvider is registered in `bootstrap/providers.php`
3. Check `config/nova.php` path setting

## Post-Installation Steps

### 1. Create Admin User

If you don't have an admin user, create one:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Or manually:

```bash
php artisan tinker
>>> $user = new App\Models\User();
>>> $user->name = 'Admin User';
>>> $user->email = 'admin@technewshub.com';
>>> $user->password = bcrypt('password');
>>> $user->role = 'admin';
>>> $user->status = 'active';
>>> $user->email_verified_at = now();
>>> $user->save();
```

### 2. Configure Resources

Nova resources are located in `app/Nova/`. The following resources are available:

- Post
- User
- Category
- Tag
- Comment
- Media
- Page
- Newsletter
- Setting
- ActivityLog

### 3. Set Up Policies

Ensure all authorization policies are registered in `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::policy(\App\Models\Post::class, \App\Policies\PostPolicy::class);
    Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
    Gate::policy(\App\Models\Category::class, \App\Policies\CategoryPolicy::class);
    Gate::policy(\App\Models\Tag::class, \App\Policies\TagPolicy::class);
    Gate::policy(\App\Models\Comment::class, \App\Policies\CommentPolicy::class);
    Gate::policy(\App\Models\Media::class, \App\Policies\MediaPolicy::class);
    Gate::policy(\App\Models\Page::class, \App\Policies\PagePolicy::class);
    Gate::policy(\App\Models\Newsletter::class, \App\Policies\NewsletterPolicy::class);
    Gate::policy(\App\Models\Setting::class, \App\Policies\SettingPolicy::class);
    Gate::policy(\App\Models\ActivityLog::class, \App\Policies\ActivityLogPolicy::class);
}
```

### 4. Test Nova Features

1. **Dashboard**: Visit `/admin` to see metrics
2. **Resources**: Navigate to Posts, Users, etc.
3. **Search**: Use global search (Cmd/Ctrl + K)
4. **Filters**: Apply filters on resource index pages
5. **Actions**: Select items and run bulk actions

## Next Steps

- Read the [Nova User Guide](nova-user-guide.md) to learn how to use Nova
- Review [Nova Custom Actions](nova-custom-actions.md) for bulk operations
- Check [Nova Custom Tools](nova-custom-tools.md) for system management
- See [Nova Troubleshooting](nova-troubleshooting.md) for common issues

## Additional Resources

- [Laravel Nova Documentation](https://nova.laravel.com/docs)
- [Nova GitHub Issues](https://github.com/laravel/nova-issues)
- [TechNewsHub Documentation](../INDEX.md)

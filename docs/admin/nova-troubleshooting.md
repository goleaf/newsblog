# Laravel Nova Troubleshooting Guide

## Table of Contents

- [Common Issues](#common-issues)
- [Installation Problems](#installation-problems)
- [Authentication Issues](#authentication-issues)
- [Performance Problems](#performance-problems)
- [Resource Issues](#resource-issues)
- [Action and Filter Problems](#action-and-filter-problems)
- [Asset and Display Issues](#asset-and-display-issues)
- [Database and Query Issues](#database-and-query-issues)
- [Permission Problems](#permission-problems)
- [Getting Help](#getting-help)

## Common Issues

### Nova Not Loading

**Symptoms**:
- Blank page at `/admin`
- 404 error
- White screen

**Possible Causes**:
1. Assets not compiled
2. Routes not registered
3. Service provider not loaded
4. Permission issues

**Solutions**:

```bash
# 1. Compile assets
npm run build

# 2. Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 3. Verify service provider
# Check bootstrap/providers.php includes NovaServiceProvider

# 4. Check file permissions
chmod -R 775 storage bootstrap/cache
```

### 403 Forbidden Error

**Symptoms**:
- "403 Forbidden" when accessing Nova
- "Unauthorized" message

**Possible Causes**:
1. User doesn't have required role
2. Gate not configured
3. Middleware blocking access

**Solutions**:

```bash
# Check user role
php artisan tinker
>>> $user = User::where('email', 'your@email.com')->first();
>>> $user->role;
>>> $user->role = 'admin';
>>> $user->save();

# Verify gate in NovaServiceProvider
# Should allow admin, editor, author roles
```

### Assets Not Loading

**Symptoms**:
- Broken styles
- Missing JavaScript
- Console errors

**Possible Causes**:
1. Assets not published
2. Assets not compiled
3. Wrong asset URL
4. Cache issues

**Solutions**:

```bash
# Publish Nova assets
php artisan nova:publish --force

# Compile assets
npm install
npm run build

# Clear browser cache
# Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)

# Check public/vendor/nova directory exists
ls -la public/vendor/nova
```

## Installation Problems

### Composer Install Fails

**Error**: "Package laravel/nova not found"

**Solution**:
```bash
# Verify Nova files exist
ls -la .data/laravel-nova_v5.7.6

# Copy Nova to vendor
cp -r .data/laravel-nova_v5.7.6 vendor/laravel/nova

# Update composer.json with path repository
# Then run:
composer update laravel/nova --no-interaction
```

### Migration Errors

**Error**: "Table already exists" or "Column not found"

**Solution**:
```bash
# Check migration status
php artisan migrate:status

# Rollback if needed
php artisan migrate:rollback

# Fresh migration (CAUTION: Deletes data)
php artisan migrate:fresh

# Or migrate specific file
php artisan migrate --path=database/migrations/xxxx_create_nova_tables.php
```

### NPM Build Fails

**Error**: "Module not found" or "Build failed"

**Solution**:
```bash
# Clear NPM cache
npm cache clean --force

# Remove node_modules
rm -rf node_modules package-lock.json

# Reinstall
npm install

# Build
npm run build

# If still failing, check Node version
node --version  # Should be 18.x or higher
```

## Authentication Issues

### Cannot Login

**Symptoms**:
- Login form doesn't work
- Redirects to login after entering credentials
- "Invalid credentials" error

**Solutions**:

```bash
# 1. Verify user exists and has correct role
php artisan tinker
>>> User::where('email', 'admin@technewshub.com')->first();

# 2. Reset password
>>> $user = User::where('email', 'admin@technewshub.com')->first();
>>> $user->password = bcrypt('newpassword');
>>> $user->save();

# 3. Check email verification
>>> $user->email_verified_at = now();
>>> $user->save();

# 4. Clear session
php artisan session:clear
```

### Logged Out Immediately

**Symptoms**:
- Login successful but immediately logged out
- Session not persisting

**Solutions**:

```bash
# 1. Check session configuration
# In .env:
SESSION_DRIVER=file
SESSION_LIFETIME=120

# 2. Clear session files
rm -rf storage/framework/sessions/*

# 3. Check session permissions
chmod -R 775 storage/framework/sessions

# 4. Verify APP_KEY is set
php artisan key:generate
```

### "Too Many Login Attempts"

**Symptoms**:
- Locked out after failed logins
- "Too many attempts" message

**Solutions**:

```bash
# Wait 60 seconds, or clear rate limit cache
php artisan cache:clear

# Or manually in tinker
php artisan tinker
>>> Cache::forget('login.attempts.' . request()->ip());
```

## Performance Problems

### Slow Page Load

**Symptoms**:
- Nova pages load slowly
- Timeout errors
- High server load

**Solutions**:

```bash
# 1. Enable query logging to find slow queries
# In AppServiceProvider:
DB::listen(function ($query) {
    if ($query->time > 1000) {
        Log::warning('Slow query', ['sql' => $query->sql, 'time' => $query->time]);
    }
});

# 2. Check for N+1 queries
# Add eager loading in Nova resources:
public static function indexQuery(NovaRequest $request, $query)
{
    return $query->with(['user', 'category', 'tags']);
}

# 3. Enable caching
# In config/nova.php:
'cache' => true,

# 4. Optimize database
php artisan db:optimize
```

### High Memory Usage

**Symptoms**:
- "Allowed memory size exhausted" error
- Server crashes

**Solutions**:

```bash
# 1. Increase PHP memory limit
# In php.ini:
memory_limit = 512M

# 2. Reduce pagination size
# In Nova resource:
public static $perPageOptions = [10, 25, 50];

# 3. Limit eager loading
# Only load what's needed

# 4. Use chunking for large datasets
# In actions:
$models->chunk(100, function ($chunk) {
    // Process chunk
});
```

### Timeout Errors

**Symptoms**:
- "Maximum execution time exceeded"
- 504 Gateway Timeout

**Solutions**:

```bash
# 1. Increase PHP timeout
# In php.ini:
max_execution_time = 300

# 2. Queue long-running actions
# Make action implement ShouldQueue

# 3. Reduce batch size
# Process fewer items at once

# 4. Optimize queries
# Add indexes, use eager loading
```

## Resource Issues

### Resource Not Showing

**Symptoms**:
- Resource missing from sidebar
- Can't access resource

**Solutions**:

```bash
# 1. Check resource is registered
# Resources auto-register from app/Nova/

# 2. Verify model exists
# Check App\Models\YourModel exists

# 3. Check authorization
# In resource:
public static function authorizedToViewAny(Request $request): bool
{
    return true; // For testing
}

# 4. Clear cache
php artisan config:clear
php artisan route:clear
```

### Fields Not Displaying

**Symptoms**:
- Fields missing from forms
- Blank detail view

**Solutions**:

```php
// 1. Check field definitions
public function fields(NovaRequest $request): array
{
    return [
        ID::make()->sortable(),
        Text::make('Title')->required(),
        // ... more fields
    ];
}

// 2. Check field authorization
Text::make('Title')->canSee(function () {
    return true; // For testing
}),

// 3. Verify database columns exist
// Run: php artisan tinker
>>> Schema::hasColumn('posts', 'title');
```

### Relationship Not Working

**Symptoms**:
- Related items not showing
- "No results found" in relationship field

**Solutions**:

```php
// 1. Verify relationship exists on model
// In App\Models\Post:
public function category()
{
    return $this->belongsTo(Category::class);
}

// 2. Check Nova relationship field
BelongsTo::make('Category')
    ->searchable()
    ->withoutTrashed(),

// 3. Verify foreign key
// Check database has category_id column

// 4. Check relatableQuery
public static function relatableCategories(NovaRequest $request, $query)
{
    return $query->where('status', 'active');
}
```

## Action and Filter Problems

### Action Not Visible

**Symptoms**:
- Action missing from dropdown
- Can't run action

**Solutions**:

```php
// 1. Check action is registered
public function actions(NovaRequest $request): array
{
    return [
        new PublishPosts,
    ];
}

// 2. Check authorization
public function authorizedToRun(Request $request, $model): bool
{
    return true; // For testing
}

// 3. Verify items selected
// Actions only show when items are selected

// 4. Check action visibility
public function canSee(Request $request): bool
{
    return true; // For testing
}
```

### Action Fails

**Symptoms**:
- Action runs but shows error
- Partial success

**Solutions**:

```php
// 1. Add error handling
public function handle(ActionFields $fields, Collection $models)
{
    try {
        foreach ($models as $model) {
            // Your logic
        }
        return Action::message('Success!');
    } catch (\Exception $e) {
        return Action::danger('Error: ' . $e->getMessage());
    }
}

// 2. Check validation
// Ensure models meet requirements

// 3. Review logs
tail -f storage/logs/laravel.log

// 4. Test with single item first
```

### Filter Not Working

**Symptoms**:
- Filter doesn't affect results
- No items after filtering

**Solutions**:

```php
// 1. Check filter apply method
public function apply(NovaRequest $request, $query, $value)
{
    return $query->where('status', $value);
}

// 2. Verify filter options
public function options(NovaRequest $request): array
{
    return [
        'Draft' => 'draft',
        'Published' => 'published',
    ];
}

// 3. Check database values match
// Ensure 'draft' exists in database

// 4. Clear filter cache
// Click "Clear Filters" button
```

## Asset and Display Issues

### Broken Layout

**Symptoms**:
- Layout looks wrong
- Elements overlapping
- Missing styles

**Solutions**:

```bash
# 1. Clear browser cache
# Hard refresh: Ctrl+Shift+R

# 2. Rebuild assets
npm run build

# 3. Check for JavaScript errors
# Open browser console (F12)

# 4. Verify Tailwind CSS compiled
ls -la public/build/
```

### Images Not Loading

**Symptoms**:
- Broken image icons
- Images don't display

**Solutions**:

```bash
# 1. Check storage link
php artisan storage:link

# 2. Verify file exists
ls -la storage/app/public/

# 3. Check file permissions
chmod -R 775 storage/app/public

# 4. Verify disk configuration
# In config/filesystems.php:
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

### Rich Text Editor Not Working

**Symptoms**:
- Trix editor not loading
- Can't format text

**Solutions**:

```bash
# 1. Check Trix field definition
Trix::make('Content')
    ->withFiles('public')
    ->required(),

# 2. Verify assets loaded
# Check browser console for errors

# 3. Rebuild assets
npm run build

# 4. Check for JavaScript conflicts
# Disable browser extensions
```

## Database and Query Issues

### "Column not found" Error

**Symptoms**:
- SQL error about missing column
- "Unknown column" message

**Solutions**:

```bash
# 1. Check database schema
php artisan tinker
>>> Schema::getColumnListing('posts');

# 2. Run migrations
php artisan migrate

# 3. Check field name matches column
# In Nova resource:
Text::make('Title', 'title'), // Second param is column name

# 4. Refresh database
php artisan migrate:fresh --seed  # CAUTION: Deletes data
```

### "Too many connections" Error

**Symptoms**:
- Database connection errors
- "Too many connections" message

**Solutions**:

```bash
# 1. Check active connections
# In MySQL:
SHOW PROCESSLIST;

# 2. Increase max connections
# In MySQL config:
max_connections = 200

# 3. Close unused connections
# In code, ensure connections are closed

# 4. Use connection pooling
# Configure in database.php
```

### Slow Queries

**Symptoms**:
- Pages load slowly
- Database CPU high

**Solutions**:

```bash
# 1. Enable query log
# In .env:
DB_LOG_QUERIES=true

# 2. Add indexes
php artisan make:migration add_indexes_to_posts_table

# In migration:
$table->index('status');
$table->index('published_at');
$table->index(['category_id', 'status']);

# 3. Use eager loading
# In Nova resource:
public static function indexQuery(NovaRequest $request, $query)
{
    return $query->with(['user', 'category']);
}

# 4. Optimize queries
# Use select() to limit columns
# Use chunk() for large datasets
```

## Permission Problems

### "Unauthorized" Errors

**Symptoms**:
- Can't access certain resources
- "This action is unauthorized" message

**Solutions**:

```php
// 1. Check policy exists
// In app/Policies/PostPolicy.php

// 2. Register policy
// In AppServiceProvider:
Gate::policy(Post::class, PostPolicy::class);

// 3. Check policy methods
public function viewAny(User $user): bool
{
    return in_array($user->role, ['admin', 'editor', 'author']);
}

// 4. Temporarily disable for testing
public function viewAny(User $user): bool
{
    return true; // For testing only!
}
```

### Can't Edit Own Content

**Symptoms**:
- Authors can't edit their posts
- "Unauthorized" when editing

**Solutions**:

```php
// In PostPolicy:
public function update(User $user, Post $post): bool
{
    // Allow admin and editor always
    if (in_array($user->role, ['admin', 'editor'])) {
        return true;
    }
    
    // Allow author to edit own posts
    return $user->id === $post->user_id;
}
```

### Tool Not Accessible

**Symptoms**:
- Can't access custom tools
- Tools not in sidebar

**Solutions**:

```php
// 1. Check tool authorization
// In tool class:
public function authorize(Request $request): bool
{
    return $request->user()->role === 'admin';
}

// 2. Verify user role
php artisan tinker
>>> auth()->user()->role;

// 3. Check gate definition
// In NovaServiceProvider:
Gate::define('viewNovaTools', function (User $user) {
    return $user->role === 'admin';
});
```

## Getting Help

### Debugging Steps

1. **Check Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Enable Debug Mode**:
   ```env
   APP_DEBUG=true
   ```

3. **Check Browser Console**:
   - Open DevTools (F12)
   - Look for JavaScript errors
   - Check Network tab for failed requests

4. **Use Tinker**:
   ```bash
   php artisan tinker
   >>> User::count();
   >>> Post::first();
   ```

5. **Review Configuration**:
   ```bash
   php artisan config:show nova
   ```

### Collecting Information

When reporting issues, include:

1. **Error Message**: Exact error text
2. **Steps to Reproduce**: What you did
3. **Expected Behavior**: What should happen
4. **Actual Behavior**: What actually happened
5. **Environment**:
   - PHP version: `php -v`
   - Laravel version: `php artisan --version`
   - Nova version: Check `composer.json`
   - Database: MySQL/PostgreSQL/SQLite
   - OS: macOS/Linux/Windows

### Resources

- **Laravel Nova Docs**: https://nova.laravel.com/docs
- **Nova GitHub Issues**: https://github.com/laravel/nova-issues
- **Laravel Docs**: https://laravel.com/docs
- **TechNewsHub Docs**: [Documentation Index](../INDEX.md)

### Support Channels

1. **Internal Documentation**: Check docs/ directory
2. **GitHub Issues**: Report bugs and feature requests
3. **Laravel Discord**: Nova channel for community help
4. **System Administrator**: Contact your admin

### Common Commands Reference

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild everything
composer dump-autoload
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
npm run build

# Check system status
php artisan about

# View routes
php artisan route:list | grep nova

# Check database
php artisan db:show

# Run tests
php artisan test --filter=Nova
```

## Prevention Tips

### Regular Maintenance

1. **Keep Updated**: Update Nova and Laravel regularly
2. **Monitor Logs**: Check logs daily for errors
3. **Backup Database**: Regular backups before changes
4. **Test Changes**: Test in staging before production
5. **Document Issues**: Keep log of problems and solutions

### Best Practices

1. **Use Version Control**: Commit changes regularly
2. **Follow Conventions**: Stick to Laravel/Nova patterns
3. **Write Tests**: Test custom code
4. **Monitor Performance**: Watch for slow queries
5. **Review Policies**: Ensure proper authorization

### Security

1. **Update Regularly**: Apply security patches
2. **Strong Passwords**: Enforce password requirements
3. **Limit Access**: Only grant necessary permissions
4. **Monitor Activity**: Review activity logs
5. **Backup Data**: Regular backups

## Conclusion

Most Nova issues can be resolved by:
1. Clearing caches
2. Rebuilding assets
3. Checking permissions
4. Reviewing logs
5. Verifying configuration

When in doubt, start with the basics and work your way up. Document solutions for future reference.

For persistent issues, gather detailed information and seek help through appropriate channels.

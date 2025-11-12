# Laravel Nova Custom Tools

## Overview

Custom tools in Nova provide specialized functionality for system management and monitoring. This guide covers all available custom tools in TechNewsHub.

## Table of Contents

- [What are Tools?](#what-are-tools)
- [Cache Manager](#cache-manager)
- [Maintenance Mode](#maintenance-mode)
- [System Health](#system-health)
- [Accessing Tools](#accessing-tools)
- [Best Practices](#best-practices)

## What are Tools?

Tools are custom pages in Nova that provide specialized functionality beyond standard CRUD operations. They appear in the sidebar navigation and offer interactive interfaces for system management.

### Available Tools

1. **Cache Manager**: Clear application caches
2. **Maintenance Mode**: Enable/disable maintenance mode
3. **System Health**: Monitor system status

### Tool Permissions

All custom tools require **Admin** role access.

## Cache Manager

### Overview

The Cache Manager tool provides a centralized interface for clearing various application caches.

### Purpose

- Clear stale cache data
- Improve performance after updates
- Troubleshoot caching issues
- Free up storage space

### Features

#### Application Cache
Clears the application cache (data cache).

**What it clears**:
- Cached database queries
- Cached API responses
- Cached search results
- Custom cached data

**When to use**:
- After database changes
- When data seems stale
- After configuration updates

**Command equivalent**:
```bash
php artisan cache:clear
```

#### Route Cache
Clears the compiled route cache.

**What it clears**:
- Cached route definitions
- Route-to-controller mappings

**When to use**:
- After adding new routes
- After modifying route files
- When routes not working

**Command equivalent**:
```bash
php artisan route:clear
```

#### Config Cache
Clears the configuration cache.

**What it clears**:
- Cached configuration files
- Environment variable cache

**When to use**:
- After changing .env file
- After modifying config files
- When settings not updating

**Command equivalent**:
```bash
php artisan config:clear
```

#### View Cache
Clears the compiled Blade view cache.

**What it clears**:
- Compiled Blade templates
- Cached view files

**When to use**:
- After modifying views
- When views not updating
- After template changes

**Command equivalent**:
```bash
php artisan view:clear
```

#### Clear All Caches
Clears all caches at once.

**What it clears**:
- Application cache
- Route cache
- Config cache
- View cache
- Compiled files

**When to use**:
- After major updates
- When troubleshooting
- Before deployment

**Command equivalent**:
```bash
php artisan optimize:clear
```

### Using Cache Manager

1. Navigate to **Tools** → **Cache Manager** in sidebar
2. Review cache information:
   - Last cleared timestamps
   - Cache sizes (if available)
   - Cache status
3. Click button for specific cache to clear
4. Or click **Clear All Caches** for complete clear
5. View success confirmation
6. Verify changes

### Cache Manager Interface

```
┌─────────────────────────────────────────┐
│         Cache Manager                    │
├─────────────────────────────────────────┤
│                                          │
│  Application Cache                       │
│  Last cleared: 2 hours ago               │
│  [Clear Application Cache]               │
│                                          │
│  Route Cache                             │
│  Last cleared: 1 day ago                 │
│  [Clear Route Cache]                     │
│                                          │
│  Config Cache                            │
│  Last cleared: 3 hours ago               │
│  [Clear Config Cache]                    │
│                                          │
│  View Cache                              │
│  Last cleared: 30 minutes ago            │
│  [Clear View Cache]                      │
│                                          │
│  ─────────────────────────────────────  │
│                                          │
│  [Clear All Caches]                      │
│                                          │
└─────────────────────────────────────────┘
```

### Best Practices

**When to Clear Cache**:
- After code deployments
- After configuration changes
- When debugging issues
- Before testing

**When NOT to Clear Cache**:
- During high traffic periods
- Without understanding impact
- As routine maintenance (unnecessary)

**Performance Impact**:
- Temporary performance decrease
- Cache rebuilds automatically
- Plan during low traffic

## Maintenance Mode

### Overview

The Maintenance Mode tool allows you to enable/disable maintenance mode with custom messages and IP whitelisting.

### Purpose

- Perform system maintenance
- Deploy updates safely
- Prevent user access during critical operations
- Display custom maintenance messages

### Features

#### Enable/Disable Toggle
Simple switch to enable or disable maintenance mode.

**When enabled**:
- Site shows maintenance page to visitors
- Whitelisted IPs can still access
- Admin panel remains accessible
- API returns 503 status

**When disabled**:
- Site operates normally
- All users can access

#### Custom Message
Set a custom message displayed on the maintenance page.

**Default message**:
```
We're currently performing scheduled maintenance.
We'll be back shortly!
```

**Custom message examples**:
```
"Upgrading to improve your experience. Back in 30 minutes!"
"Scheduled maintenance in progress. Expected completion: 2:00 PM EST"
"We're making things better! Check back soon."
```

#### IP Whitelist
Allow specific IP addresses to bypass maintenance mode.

**Use cases**:
- Allow admin access
- Allow developer access
- Allow testing from specific locations
- Allow monitoring services

**Format**:
```
192.168.1.1
10.0.0.5
203.0.113.42
```

**Multiple IPs**:
- One IP per line
- IPv4 and IPv6 supported
- CIDR notation supported (e.g., 192.168.1.0/24)

#### Schedule Maintenance
Set start and end times for automatic maintenance mode.

**Features**:
- Schedule future maintenance
- Automatic enable/disable
- Countdown timer for users
- Email notifications (optional)

### Using Maintenance Mode

#### Enabling Maintenance Mode

1. Navigate to **Tools** → **Maintenance Mode**
2. Review current status
3. Enter custom message (optional)
4. Add whitelisted IPs (optional)
5. Click **Enable Maintenance Mode**
6. Confirm action
7. Verify maintenance page displays

#### Disabling Maintenance Mode

1. Navigate to **Tools** → **Maintenance Mode**
2. Click **Disable Maintenance Mode**
3. Confirm action
4. Verify site is accessible

#### Scheduling Maintenance

1. Navigate to **Tools** → **Maintenance Mode**
2. Click **Schedule Maintenance**
3. Set start date/time
4. Set end date/time
5. Enter custom message
6. Add whitelisted IPs
7. Click **Schedule**
8. Maintenance enables/disables automatically

### Maintenance Mode Interface

```
┌─────────────────────────────────────────┐
│      Maintenance Mode                    │
├─────────────────────────────────────────┤
│                                          │
│  Status: ● Disabled                      │
│                                          │
│  Custom Message:                         │
│  ┌────────────────────────────────────┐ │
│  │ We're performing maintenance...    │ │
│  │                                    │ │
│  └────────────────────────────────────┘ │
│                                          │
│  Whitelisted IPs:                        │
│  ┌────────────────────────────────────┐ │
│  │ 192.168.1.1                        │ │
│  │ 10.0.0.5                           │ │
│  └────────────────────────────────────┘ │
│                                          │
│  [Enable Maintenance Mode]               │
│                                          │
│  ─────────────────────────────────────  │
│                                          │
│  Schedule Maintenance:                   │
│  Start: [2025-01-20 02:00]              │
│  End:   [2025-01-20 04:00]              │
│  [Schedule]                              │
│                                          │
└─────────────────────────────────────────┘
```

### Command Line Alternative

```bash
# Enable maintenance mode
php artisan down --message="Custom message" --retry=60

# Enable with IP whitelist
php artisan down --allow=192.168.1.1 --allow=10.0.0.5

# Disable maintenance mode
php artisan up
```

### Best Practices

**Before Enabling**:
- Notify users in advance
- Schedule during low traffic
- Prepare rollback plan
- Test maintenance page

**During Maintenance**:
- Monitor progress
- Keep message updated
- Respond to urgent issues
- Document changes

**After Maintenance**:
- Disable promptly
- Verify site functionality
- Monitor for issues
- Notify users of completion

## System Health

### Overview

The System Health tool provides real-time monitoring of critical system components.

### Purpose

- Monitor system status
- Detect issues early
- Track resource usage
- Ensure system reliability

### Monitored Components

#### Database Connection
Checks database connectivity and performance.

**Metrics**:
- Connection status (Connected/Disconnected)
- Response time (ms)
- Active connections
- Slow query count

**Status Indicators**:
- 🟢 Green: Healthy (< 100ms response)
- 🟡 Yellow: Warning (100-500ms response)
- 🔴 Red: Critical (> 500ms or disconnected)

#### Queue Status
Monitors queue health and failed jobs.

**Metrics**:
- Queue connection status
- Pending jobs count
- Failed jobs count
- Average processing time

**Status Indicators**:
- 🟢 Green: Healthy (< 100 pending, 0 failed)
- 🟡 Yellow: Warning (100-1000 pending, 1-10 failed)
- 🔴 Red: Critical (> 1000 pending, > 10 failed)

#### Storage Usage
Tracks disk space usage.

**Metrics**:
- Total disk space
- Used space
- Available space
- Usage percentage

**Status Indicators**:
- 🟢 Green: Healthy (< 70% used)
- 🟡 Yellow: Warning (70-90% used)
- 🔴 Red: Critical (> 90% used)

#### Recent Errors
Displays recent errors from logs.

**Information**:
- Error message
- Timestamp
- File and line number
- Stack trace (expandable)

**Filters**:
- Last hour
- Last 24 hours
- Last 7 days

#### Cache Status
Shows cache health and hit rate.

**Metrics**:
- Cache driver
- Hit rate percentage
- Total requests
- Cache size

**Status Indicators**:
- 🟢 Green: Healthy (> 80% hit rate)
- 🟡 Yellow: Warning (50-80% hit rate)
- 🔴 Red: Critical (< 50% hit rate)

### Using System Health

1. Navigate to **Tools** → **System Health**
2. Review all status indicators
3. Click components for details
4. Check recent errors
5. Take action if needed
6. Monitor trends over time

### System Health Interface

```
┌─────────────────────────────────────────┐
│        System Health                     │
├─────────────────────────────────────────┤
│                                          │
│  Database Connection                     │
│  Status: 🟢 Connected                    │
│  Response: 45ms                          │
│  [View Details]                          │
│                                          │
│  Queue Status                            │
│  Status: 🟡 Warning                      │
│  Pending: 150 jobs                       │
│  Failed: 3 jobs                          │
│  [View Failed Jobs]                      │
│                                          │
│  Storage Usage                           │
│  Status: 🟢 Healthy                      │
│  Used: 45.2 GB / 100 GB (45%)           │
│  [View Details]                          │
│                                          │
│  Recent Errors (Last 24h)                │
│  ⚠ 2 errors found                        │
│  [View Error Log]                        │
│                                          │
│  Cache Status                            │
│  Status: 🟢 Healthy                      │
│  Hit Rate: 87%                           │
│  [View Cache Stats]                      │
│                                          │
│  Last Updated: 30 seconds ago            │
│  [Refresh Now]                           │
│                                          │
└─────────────────────────────────────────┘
```

### Auto-Refresh

System Health auto-refreshes every 30 seconds to provide real-time monitoring.

**Controls**:
- Pause auto-refresh
- Manual refresh button
- Adjust refresh interval

### Alerts and Notifications

**Email Alerts** (if configured):
- Critical status changes
- Failed jobs threshold exceeded
- Storage space low
- Database connection lost

**In-App Notifications**:
- Badge on System Health icon
- Dashboard warning cards
- Toast notifications

### Taking Action

#### Database Issues
1. Check database server status
2. Review slow query log
3. Optimize problematic queries
4. Restart database if needed

#### Queue Issues
1. View failed jobs
2. Retry failed jobs
3. Check queue worker status
4. Restart queue workers

#### Storage Issues
1. Review large files
2. Clean up old logs
3. Archive old media
4. Expand storage if needed

#### Error Issues
1. Review error details
2. Check stack traces
3. Fix underlying issues
4. Monitor for recurrence

### Best Practices

**Regular Monitoring**:
- Check daily
- Review trends weekly
- Set up alerts
- Document issues

**Proactive Maintenance**:
- Address warnings promptly
- Don't wait for critical status
- Plan capacity upgrades
- Keep logs clean

**Performance Optimization**:
- Monitor slow queries
- Optimize cache hit rate
- Clean failed jobs
- Archive old data

## Accessing Tools

### Navigation

Tools appear in the Nova sidebar under the "Tools" section:

```
Nova Sidebar
├── Dashboard
├── Resources
│   ├── Posts
│   ├── Users
│   └── ...
└── Tools
    ├── Cache Manager
    ├── Maintenance Mode
    └── System Health
```

### Permissions

All tools require **Admin** role:

```php
// In NovaServiceProvider
protected function gate(): void
{
    Gate::define('viewNova', function (User $user) {
        return in_array($user->role, ['admin', 'editor', 'author']);
    });
    
    // Tools have additional check
    Gate::define('viewNovaTools', function (User $user) {
        return $user->role === 'admin';
    });
}
```

### Direct URLs

Tools can be accessed directly:

- Cache Manager: `/admin/tools/cache-manager`
- Maintenance Mode: `/admin/tools/maintenance-mode`
- System Health: `/admin/tools/system-health`

## Best Practices

### General Guidelines

1. **Understand Impact**: Know what each tool does before using
2. **Plan Ahead**: Schedule maintenance during low traffic
3. **Monitor Results**: Check system health after changes
4. **Document Actions**: Keep log of tool usage
5. **Test First**: Test in staging before production

### Security

1. **Restrict Access**: Tools are admin-only for good reason
2. **Audit Usage**: Review activity logs regularly
3. **Secure Credentials**: Protect admin accounts
4. **Use HTTPS**: Always access tools over secure connection

### Performance

1. **Clear Caches Wisely**: Don't clear unnecessarily
2. **Monitor Impact**: Watch performance after clearing
3. **Schedule Maintenance**: Use low-traffic periods
4. **Plan Capacity**: Monitor trends and plan upgrades

### Troubleshooting

1. **Check Logs**: Review activity and error logs
2. **Verify Permissions**: Ensure proper access
3. **Test Functionality**: Verify tools work as expected
4. **Contact Support**: Get help when needed

## Conclusion

Custom tools provide powerful system management capabilities in Nova. Use them responsibly, understand their impact, and monitor results. Regular use of these tools helps maintain a healthy, performant system.

For more information, see:
- [Nova User Guide](nova-user-guide.md)
- [Nova Troubleshooting](nova-troubleshooting.md)
- [System Administration Guide](../functionality/system-administration.md)

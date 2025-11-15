# Monitoring & Analytics Setup

## Overview

Comprehensive monitoring system for tracking DNT compliance, performance metrics, engagement, search analytics, and errors.

## Components

### 1. MonitoringService (`app/Services/MonitoringService.php`)

Centralized service for tracking application metrics:

- **DNT Compliance**: Tracks Do Not Track header presence and compliance
- **Post View Performance**: Monitors view tracking duration and queue status
- **Engagement Metrics**: Tracks scroll depth, time spent, and user interactions
- **Search Performance**: Monitors search query performance and zero-result rates
- **Error Tracking**: Logs and counts errors by type
- **Alert Thresholds**: Automatically checks for concerning metric patterns

### 2. Admin Dashboard (`/admin/monitoring`)

Real-time monitoring dashboard showing:

- Post views tracked (total and queued)
- DNT compliance rate
- Engagement events (authenticated vs anonymous)
- Search quality metrics
- Error tracking by type
- Active alerts with severity levels
- Latest performance data

### 3. Custom Logging Channels

Configured in `config/logging.php`:

- **analytics**: Daily logs for DNT and tracking events (30 days retention)
- **performance**: Daily logs for slow operations (14 days retention)
- **errors**: Daily error logs (30 days retention)

### 4. Integration Points

#### PostViewController
- Tracks DNT compliance on every post view
- Monitors view tracking performance
- Logs slow tracking operations

#### EngagementMetricController
- Tracks DNT compliance for engagement events
- Monitors engagement metric types
- Alerts on slow engagement tracking

## Usage

### Accessing the Dashboard

```
/admin/monitoring
```

Requires admin or editor role.

### Tracking Custom Metrics

```php
use App\Services\MonitoringService;

$monitoring = app(MonitoringService::class);

// Track DNT compliance
$monitoring->trackDntCompliance($dntEnabled, 'endpoint.name');

// Track performance
$monitoring->trackViewPerformance($postId, $duration, $queued);

// Track engagement
$monitoring->trackEngagementMetric('scroll', $postId, $userId);

// Track search
$monitoring->trackSearchPerformance($query, $resultCount, $duration);

// Track errors
$monitoring->trackError('type', 'message', ['context' => 'data']);
```

### Getting Metrics Snapshot

```php
$metrics = $monitoring->getMetricsSnapshot();
```

Returns:
- post_views (total, queued, latest)
- dnt (enabled, disabled)
- engagement (total, by type, authenticated/anonymous)
- search (total, zero_results, latest)
- errors (total, by type)

### Checking Alerts

```php
$alerts = $monitoring->checkAlertThresholds();
```

Returns array of alerts with:
- severity (high, medium, low)
- type (error_rate, search_quality, etc.)
- message
- value

## Alert Thresholds

### High Severity
- Error rate > 100 errors

### Medium Severity
- Zero-result search rate > 30%

## Metrics Storage

Metrics are stored in cache with the following prefixes:

- `metrics:post_views:*` - View tracking metrics
- `metrics:dnt:*` - DNT compliance metrics
- `metrics:engagement:*` - Engagement metrics
- `metrics:search:*` - Search metrics
- `metrics:errors:*` - Error metrics

## Testing

Comprehensive test suite in `tests/Feature/MonitoringTest.php`:

```bash
php artisan test --filter=MonitoringTest
```

Tests cover:
- DNT compliance tracking
- Performance monitoring
- Engagement metrics
- Search analytics
- Error tracking
- Alert thresholds
- Dashboard access control
- Integration with controllers

## Recommendations

### Production Setup

1. **External Monitoring**: Consider integrating with Sentry, New Relic, or Datadog for production monitoring
2. **Log Aggregation**: Use ELK stack or similar for centralized log management
3. **Alerting**: Configure Slack/email notifications for critical alerts
4. **Metrics Retention**: Adjust cache TTL based on your needs
5. **Database Monitoring**: Add query performance tracking
6. **API Monitoring**: Track API response times and error rates

### Performance Optimization

1. Use Redis for cache storage in production
2. Consider moving metrics to a time-series database (InfluxDB, Prometheus)
3. Implement metric aggregation for high-traffic sites
4. Add metric sampling for very high-volume endpoints

### Security

1. Restrict monitoring dashboard to admin users only
2. Sanitize logged data to prevent PII leakage
3. Implement rate limiting on metric endpoints
4. Regular audit of logged data

## Future Enhancements

- Real-time dashboard with WebSockets
- Historical trend analysis
- Custom metric definitions
- Automated incident response
- Integration with APM tools
- User behavior analytics
- A/B testing metrics
- Conversion funnel tracking

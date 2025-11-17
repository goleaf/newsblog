# Error Logging and Monitoring

This document describes the error logging and monitoring system implemented in the platform.

## Overview

The platform uses a comprehensive logging and monitoring system that includes:

1. **Multiple Log Channels** - Separate logs for different types of events
2. **Contextual Logging** - Rich context information with every log entry
3. **Sentry Integration** - Real-time error tracking and alerting
4. **Slack Notifications** - Critical error alerts sent to Slack

## Log Channels

### Daily Logs
- **File**: `storage/logs/laravel.log`
- **Retention**: 14 days (configurable via `LOG_DAILY_DAYS`)
- **Purpose**: General application logs
- **Level**: Debug and above

### Security Logs
- **File**: `storage/logs/security.log`
- **Retention**: 90 days
- **Purpose**: Security-related events
- **Level**: Info and above
- **Events**:
  - Failed login attempts
  - Successful logins
  - Password reset requests
  - Password changes
  - Rate limit violations
  - Unauthorized access attempts
  - Suspicious activity
  - GDPR data export/deletion requests

### Business Logs
- **File**: `storage/logs/business.log`
- **Retention**: 30 days
- **Purpose**: Business events and metrics
- **Level**: Info and above
- **Events**:
  - User registrations
  - Article publications
  - Comment creation
  - Comment moderation
  - Newsletter subscriptions
  - Newsletter sends

### Error Logs
- **File**: `storage/logs/errors.log`
- **Retention**: 30 days
- **Purpose**: Application errors
- **Level**: Error and above

### Critical Logs
- **Channels**: Daily + Slack
- **Purpose**: Critical errors requiring immediate attention
- **Level**: Critical only
- **Notifications**: Sent to Slack webhook if configured

### Analytics Logs
- **File**: `storage/logs/analytics.log`
- **Retention**: 30 days
- **Purpose**: Analytics and tracking events
- **Level**: Info and above

### Performance Logs
- **File**: `storage/logs/performance.log`
- **Retention**: 14 days
- **Purpose**: Performance warnings and issues
- **Level**: Warning and above

## LoggingService

The `LoggingService` provides a centralized interface for logging with automatic context enrichment.

### Usage Examples

```php
use App\Services\LoggingService;

// Inject via constructor
public function __construct(
    private LoggingService $loggingService
) {}

// Or resolve from container
$loggingService = app(LoggingService::class);
```

### Security Events

```php
// Failed login
$loggingService->logFailedLogin($email, 'invalid_credentials');

// Successful login
$loggingService->logSuccessfulLogin($userId, $email);

// Password reset request
$loggingService->logPasswordResetRequest($email);

// Password change
$loggingService->logPasswordChange($userId);

// Rate limit exceeded
$loggingService->logRateLimitExceeded($endpoint, $userId);

// Suspicious activity
$loggingService->logSuspiciousActivity('multiple_failed_logins', [
    'attempts' => 5,
    'timeframe' => '5 minutes',
]);

// Unauthorized access
$loggingService->logUnauthorizedAccess($resource, $userId);

// GDPR requests
$loggingService->logDataExportRequest($userId);
$loggingService->logDataDeletionRequest($userId);
```

### Business Events

```php
// User registration
$loggingService->logUserRegistration($userId, $email);

// Article published
$loggingService->logArticlePublished($articleId, $authorId, $title);

// Comment created
$loggingService->logCommentCreated($commentId, $articleId, $userId);

// Comment moderated
$loggingService->logCommentModerated($commentId, 'approved', $moderatorId, $reason);

// Newsletter subscription
$loggingService->logNewsletterSubscription($email, $frequency);

// Newsletter sent
$loggingService->logNewsletterSent($newsletterId, $recipientCount);
```

### Error Logging

```php
// Log error without exception
$loggingService->logError('Failed to process payment', null, [
    'order_id' => $orderId,
    'amount' => $amount,
]);

// Log error with exception
try {
    // Some code that might throw
} catch (\Exception $e) {
    $loggingService->logError('Payment processing failed', $e, [
        'order_id' => $orderId,
    ]);
}

// Log critical error
$loggingService->logCritical('Database connection lost', null, [
    'database' => 'primary',
    'last_query' => $lastQuery,
]);
```

### Custom Events

```php
// Security event
$loggingService->logSecurityEvent('custom_security_event', [
    'custom_field' => 'value',
]);

// Business event
$loggingService->logBusinessEvent('custom_business_event', [
    'custom_field' => 'value',
]);
```

## Automatic Context Enrichment

All log entries are automatically enriched with:

- `timestamp` - ISO 8601 formatted timestamp
- `ip_address` - Client IP address
- `user_agent` - Client user agent
- `url` - Full request URL
- `method` - HTTP method
- `user_id` - Authenticated user ID (if available)

## Sentry Integration

### Configuration

Set the following environment variables:

```env
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production
SENTRY_SEND_DEFAULT_PII=false
SENTRY_ENABLE_LOGS=true
SENTRY_LOG_LEVEL=error
```

### Features

- **Automatic Exception Capture**: All exceptions are automatically sent to Sentry
- **Performance Monitoring**: 10% of transactions are traced (configurable)
- **Breadcrumbs**: Captures logs, SQL queries, cache operations, and more
- **Release Tracking**: Track errors by release version
- **User Context**: Associates errors with authenticated users (when PII is enabled)

### Testing Sentry

```bash
# Test exception tracking
php artisan error:test --type=exception

# Check Sentry dashboard for the captured exception
```

## Slack Notifications

### Configuration

Set the following environment variables:

```env
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
LOG_SLACK_USERNAME="Tech News Platform"
LOG_SLACK_EMOJI=":warning:"
```

### When Notifications Are Sent

Slack notifications are sent for:
- Critical errors logged via `logCritical()`
- Unhandled exceptions with 500-level status codes
- System health check failures

## Testing Error Tracking

Use the `error:test` command to test different logging scenarios:

```bash
# Test business event logging
php artisan error:test --type=info

# Test security event logging
php artisan error:test --type=warning

# Test error logging
php artisan error:test --type=error

# Test critical error logging (triggers Slack if configured)
php artisan error:test --type=critical

# Test exception tracking (sends to Sentry if configured)
php artisan error:test --type=exception
```

## Log Rotation

Logs are automatically rotated based on their retention settings:

- Daily logs: 14 days
- Security logs: 90 days
- Business logs: 30 days
- Error logs: 30 days
- Analytics logs: 30 days
- Performance logs: 14 days

Old log files are automatically deleted by Laravel's logging system.

## Monitoring Best Practices

1. **Use Appropriate Log Levels**
   - `debug`: Detailed debugging information
   - `info`: Informational messages
   - `warning`: Warning messages
   - `error`: Error conditions
   - `critical`: Critical conditions requiring immediate attention

2. **Include Context**
   - Always include relevant IDs (user, article, order, etc.)
   - Include error codes and messages
   - Add custom context for debugging

3. **Avoid Logging Sensitive Data**
   - Never log passwords or tokens
   - Be careful with PII (emails, names, addresses)
   - Use Sentry's `SENTRY_SEND_DEFAULT_PII=false` in production

4. **Monitor Log Volume**
   - Watch for excessive logging
   - Set up alerts for error rate spikes
   - Review logs regularly for patterns

5. **Use Structured Logging**
   - Use the LoggingService methods
   - Include structured context arrays
   - Avoid string concatenation in log messages

## Troubleshooting

### Logs Not Being Written

1. Check file permissions: `storage/logs` must be writable
2. Check disk space: Ensure sufficient space available
3. Check log level: Ensure `LOG_LEVEL` is set appropriately

### Sentry Not Receiving Errors

1. Verify `SENTRY_LARAVEL_DSN` is set correctly
2. Check network connectivity to Sentry
3. Verify error level meets `SENTRY_LOG_LEVEL` threshold
4. Check Sentry project settings

### Slack Notifications Not Sending

1. Verify `LOG_SLACK_WEBHOOK_URL` is set correctly
2. Test webhook URL manually
3. Check Slack workspace permissions
4. Verify critical errors are being logged

## Related Documentation

- [System Health Monitoring](SYSTEM_HEALTH.md)
- [Performance Monitoring](../performance/MONITORING.md)
- [Security Audit](../security/AUDIT.md)

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Centralized logging service for contextual logging across the application.
 *
 * Provides methods for logging security events, business events, and errors
 * with rich context information.
 */
class LoggingService
{
    /**
     * Log a security event with context.
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context, [
            'event_type' => 'security',
            'event_name' => $event,
        ]);

        Log::channel('security')->info($event, $enrichedContext);
    }

    /**
     * Log a business event with context.
     */
    public function logBusinessEvent(string $event, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context, [
            'event_type' => 'business',
            'event_name' => $event,
        ]);

        Log::channel('business')->info($event, $enrichedContext);
    }

    /**
     * Log an error with context.
     */
    public function logError(string $message, \Throwable $exception = null, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context, [
            'error_message' => $message,
        ]);

        if ($exception) {
            $enrichedContext['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        Log::channel('errors')->error($message, $enrichedContext);
    }

    /**
     * Log a critical error that requires immediate attention.
     */
    public function logCritical(string $message, \Throwable $exception = null, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($context, [
            'severity' => 'critical',
            'error_message' => $message,
        ]);

        if ($exception) {
            $enrichedContext['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        Log::channel('critical')->critical($message, $enrichedContext);
    }

    /**
     * Log a failed login attempt.
     */
    public function logFailedLogin(string $email, string $reason = 'invalid_credentials'): void
    {
        $this->logSecurityEvent('failed_login_attempt', [
            'email' => $email,
            'reason' => $reason,
        ]);
    }

    /**
     * Log a successful login.
     */
    public function logSuccessfulLogin(int $userId, string $email): void
    {
        $this->logSecurityEvent('successful_login', [
            'user_id' => $userId,
            'email' => $email,
        ]);
    }

    /**
     * Log a password reset request.
     */
    public function logPasswordResetRequest(string $email): void
    {
        $this->logSecurityEvent('password_reset_requested', [
            'email' => $email,
        ]);
    }

    /**
     * Log a password change.
     */
    public function logPasswordChange(int $userId): void
    {
        $this->logSecurityEvent('password_changed', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Log a user registration.
     */
    public function logUserRegistration(int $userId, string $email): void
    {
        $this->logBusinessEvent('user_registered', [
            'user_id' => $userId,
            'email' => $email,
        ]);
    }

    /**
     * Log article publication.
     */
    public function logArticlePublished(int $articleId, int $authorId, string $title): void
    {
        $this->logBusinessEvent('article_published', [
            'article_id' => $articleId,
            'author_id' => $authorId,
            'title' => $title,
        ]);
    }

    /**
     * Log comment creation.
     */
    public function logCommentCreated(int $commentId, int $articleId, int $userId): void
    {
        $this->logBusinessEvent('comment_created', [
            'comment_id' => $commentId,
            'article_id' => $articleId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Log comment moderation action.
     */
    public function logCommentModerated(int $commentId, string $action, int $moderatorId, ?string $reason = null): void
    {
        $this->logBusinessEvent('comment_moderated', [
            'comment_id' => $commentId,
            'action' => $action,
            'moderator_id' => $moderatorId,
            'reason' => $reason,
        ]);
    }

    /**
     * Log newsletter subscription.
     */
    public function logNewsletterSubscription(string $email, string $frequency): void
    {
        $this->logBusinessEvent('newsletter_subscribed', [
            'email' => $email,
            'frequency' => $frequency,
        ]);
    }

    /**
     * Log newsletter sent.
     */
    public function logNewsletterSent(int $newsletterId, int $recipientCount): void
    {
        $this->logBusinessEvent('newsletter_sent', [
            'newsletter_id' => $newsletterId,
            'recipient_count' => $recipientCount,
        ]);
    }

    /**
     * Log API rate limit exceeded.
     */
    public function logRateLimitExceeded(string $endpoint, ?int $userId = null): void
    {
        $this->logSecurityEvent('rate_limit_exceeded', [
            'endpoint' => $endpoint,
            'user_id' => $userId,
        ]);
    }

    /**
     * Log suspicious activity.
     */
    public function logSuspiciousActivity(string $activity, array $context = []): void
    {
        $this->logSecurityEvent('suspicious_activity', array_merge([
            'activity' => $activity,
        ], $context));
    }

    /**
     * Log unauthorized access attempt.
     */
    public function logUnauthorizedAccess(string $resource, ?int $userId = null): void
    {
        $this->logSecurityEvent('unauthorized_access_attempt', [
            'resource' => $resource,
            'user_id' => $userId,
        ]);
    }

    /**
     * Log data export request (GDPR).
     */
    public function logDataExportRequest(int $userId): void
    {
        $this->logSecurityEvent('data_export_requested', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Log data deletion request (GDPR).
     */
    public function logDataDeletionRequest(int $userId): void
    {
        $this->logSecurityEvent('data_deletion_requested', [
            'user_id' => $userId,
        ]);
    }

    /**
     * Enrich context with common information.
     */
    protected function enrichContext(array $context, array $additionalContext = []): array
    {
        return array_merge([
            'timestamp' => now()->toIso8601String(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'user_id' => auth()->id(),
        ], $additionalContext, $context);
    }
}

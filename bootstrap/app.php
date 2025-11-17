<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Do not enable Redis-backed throttling. Rely on the default
        // cache-backed rate limiting (database store) to avoid any Redis
        // connections in this environment.

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'page.cache' => \App\Http\Middleware\PageCache::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'rate-limit-headers' => \App\Http\Middleware\AddRateLimitHeaders::class,
        ]);

        $middleware->remove(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class);
        $middleware->prepend(\App\Http\Middleware\MaintenanceModeBypass::class);
        // CSRF: disable validation during tests; enable normally
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(['*']);
        } else {
            $middleware->validateCsrfTokens();
        }
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\TrackPerformance::class);
        $middleware->append(\App\Http\Middleware\SetCacheHeaders::class);
        $middleware->append(\App\Http\Middleware\PageCache::class);

        $middleware->replaceInGroup(
            'web',
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \App\Http\Middleware\EncryptCookies::class
        );

        // Configure rate limiting for API
        // Default: 60 requests per minute for authenticated users, 30 for guests
        $middleware->throttleApi();
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Publish scheduled posts every minute
        $schedule->command('posts:publish-scheduled')->everyMinute();

        // Clean up old post views (older than 1 year)
        $schedule->call(function () {
            \App\Models\PostView::where('viewed_at', '<', now()->subYear())
                ->delete();
        })->daily();

        // Clean up spam comments (older than 30 days)
        $schedule->call(function () {
            \App\Models\Comment::where('status', 'spam')
                ->where('created_at', '<', now()->subDays(30))
                ->forceDelete();
        })->daily();

        // Clean up old read notifications (older than 30 days)
        $schedule->job(new \App\Jobs\CleanupOldNotifications(30))
            ->daily()
            ->at('03:00')
            ->description('Clean up old read notifications');

        // Check for broken links weekly (spec requires running the command)
        $schedule->command('links:check')->weekly();

        // Nova performance monitoring every 5 minutes
        $schedule->command('nova:monitor-performance --period=hour')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Check Nova errors every minute
        if (file_exists(base_path('scripts/check-nova-errors.sh'))) {
            $schedule->exec('bash '.base_path('scripts/check-nova-errors.sh'))
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground();
        }

        // Generate daily Nova performance report
        if (file_exists(base_path('scripts/generate-daily-report.sh'))) {
            $schedule->exec('bash '.base_path('scripts/generate-daily-report.sh'))
                ->dailyAt('23:59')
                ->withoutOverlapping();
        }

        // Check performance alerts every 15 minutes
        $schedule->command('performance:check-alerts')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Warm caches shortly after deployments (fallback: daily at 02:15)
        $schedule->command('cache:warm')
            ->dailyAt('02:15')
            ->withoutOverlapping()
            ->runInBackground();

        // Daily database backup (sqlite only) with 30-day retention
        $schedule->command('backup:database --retention=30')
            ->dailyAt('03:10')
            ->withoutOverlapping()
            ->description('Backup sqlite database and prune old backups');

        // Horizon: Take snapshots of queue metrics every 5 minutes
        $schedule->command('horizon:snapshot')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Horizon: Prune monitored jobs older than 7 days
        $schedule->command('horizon:clear --hours=168')
            ->daily()
            ->at('04:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Integrate Sentry for error tracking
        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        // Log critical errors with context
        $exceptions->reportable(function (Throwable $e) {
            $loggingService = app(\App\Services\LoggingService::class);

            // Log critical errors (500-level errors)
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                if ($e->getStatusCode() >= 500) {
                    $loggingService->logCritical('HTTP Exception: '.$e->getMessage(), $e);
                }
            } else {
                // Log all other exceptions as errors
                $loggingService->logError('Exception: '.$e->getMessage(), $e);
            }
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

            // Log rate limit violation
            $loggingService = app(\App\Services\LoggingService::class);
            $loggingService->logRateLimitExceeded($request->path(), auth()->id());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $retryAfter,
                ], 429, [
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Limit' => $e->getHeaders()['X-RateLimit-Limit'] ?? null,
                    'X-RateLimit-Remaining' => 0,
                ]);
            }

            return response()->view('errors.429', [
                'retry_after' => $retryAfter,
            ], 429, [
                'Retry-After' => $retryAfter,
            ]);
        });

        // Handle maintenance mode (503) exceptions
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 503) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $e->getMessage() ?: 'Service Unavailable',
                        'retry_after' => $retryAfter,
                    ], 503, [
                        'Retry-After' => $retryAfter,
                    ]);
                }

                return response()->view('errors.503', [
                    'exception' => $e,
                    'retryAfter' => $retryAfter,
                ], 503, [
                    'Retry-After' => $retryAfter,
                ]);
            }
        });
    })->create();

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
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->remove(\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class);
        $middleware->prepend(\App\Http\Middleware\MaintenanceModeBypass::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\TrackPerformance::class);
        $middleware->append(\App\Http\Middleware\SetCacheHeaders::class);

        $middleware->replaceInGroup(
            'web',
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \App\Http\Middleware\EncryptCookies::class
        );

        // Configure rate limiting for API
        $middleware->throttleApi('60,1'); // 60 requests per minute for API
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

        // Check for broken links weekly
        $schedule->job(new \App\Jobs\CheckBrokenLinks)->weekly();

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

            // Log rate limit violation
            \Illuminate\Support\Facades\Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'retry_after' => $retryAfter,
            ]);

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

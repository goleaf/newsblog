<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->prepend(\App\Http\Middleware\MaintenanceModeBypass::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Configure rate limiting for API
        $middleware->throttleApi('60,1'); // 60 requests per minute for API

        // Configure custom rate limiters
        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
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
        //
    })->create();

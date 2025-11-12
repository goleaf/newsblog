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

        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Configure rate limiting for API
        $middleware->throttleApi('60,1'); // 60 requests per minute for API

        // Configure custom rate limiters
        RateLimiter::for('comments', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

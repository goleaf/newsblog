<?php

use App\Console\Commands\ArchiveActivityLogs;
use App\Console\Commands\ArchiveSearchLogs;
use App\Jobs\Analytics\AggregateWeeklyStatsJob;
use App\Jobs\Analytics\CalculateDailyMetricsJob;
use App\Jobs\Analytics\CleanOldAnalyticsDataJob;
use App\Jobs\Analytics\GenerateMonthlyReportJob;
use App\Jobs\CleanupOldNotifications;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ArchiveSearchLogs::class)
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->description('Archive old search logs');

Schedule::command(ArchiveActivityLogs::class)
    ->daily()
    ->at('02:00')
    ->description('Archive old activity logs');

// Cleanup old read notifications daily
Schedule::job(new CleanupOldNotifications(30))
    ->dailyAt('03:10')
    ->name('cleanup-old-notifications')
    ->withoutOverlapping()
    ->description('Delete read notifications older than 30 days');

// Search analytics aggregation schedules
Schedule::job(new CalculateDailyMetricsJob)
    ->dailyAt('01:10')
    ->name('analytics-daily')
    ->withoutOverlapping()
    ->description('Calculate and cache daily search analytics metrics');

Schedule::job(new AggregateWeeklyStatsJob)
    ->weeklyOn(1, '01:20')
    ->name('analytics-weekly')
    ->withoutOverlapping()
    ->description('Aggregate and cache weekly search analytics metrics');

Schedule::job(new GenerateMonthlyReportJob)
    ->monthlyOn(1, '01:30')
    ->name('analytics-monthly')
    ->withoutOverlapping()
    ->description('Generate and cache monthly search analytics report');

Schedule::job(new CleanOldAnalyticsDataJob(90))
    ->weeklyOn(7, '01:40')
    ->name('analytics-cleanup')
    ->withoutOverlapping()
    ->description('Clean old analytics data (search logs)');

// Newsletter schedules (basic) – queue sends for verified subscribers
Schedule::command('newsletters:send --subject="Daily News" --content="<p>Your daily news digest.</p>"')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->description('Queue daily newsletter sends');

Schedule::command('newsletters:send --subject="Weekly Digest" --content="<p>Your weekly news roundup.</p>"')
    ->weeklyOn(1, '09:30') // Monday 09:30
    ->withoutOverlapping()
    ->description('Queue weekly newsletter sends');

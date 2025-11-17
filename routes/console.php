<?php

use App\Console\Commands\ArchiveActivityLogs;
use App\Console\Commands\ArchiveSearchLogs;
use App\Console\Commands\WarmCache;
use App\Jobs\Analytics\AggregateWeeklyStatsJob;
use App\Jobs\Analytics\CalculateDailyMetricsJob;
use App\Jobs\Analytics\CleanOldAnalyticsDataJob;
use App\Jobs\Analytics\GenerateMonthlyReportJob;
use App\Jobs\CalculateArticleSimilaritiesJob;
use App\Jobs\CleanupOldNotifications;
use App\Jobs\GenerateUserRecommendationsJob;
use App\Jobs\UpdateRecommendationScoresJob;
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

// Newsletter schedules - automatically send based on subscriber preferences
Schedule::command('newsletter:send daily')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->description('Send daily newsletters to subscribers');

Schedule::command('newsletter:send weekly')
    ->weeklyOn(1, '08:00') // Monday 08:00
    ->withoutOverlapping()
    ->description('Send weekly newsletters to subscribers');

Schedule::command('newsletter:send monthly')
    ->monthlyOn(1, '08:00') // 1st of month at 08:00
    ->withoutOverlapping()
    ->description('Send monthly newsletters to subscribers');

// Warm cache daily
Schedule::command(WarmCache::class)
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->description('Pre-warm application caches for common pages');

// Recommendation system schedules
Schedule::job(new CalculateArticleSimilaritiesJob)
    ->dailyAt('02:00')
    ->name('calculate-article-similarities')
    ->withoutOverlapping()
    ->description('Calculate similarity scores between articles');

Schedule::job(new GenerateUserRecommendationsJob)
    ->dailyAt('03:00')
    ->name('generate-user-recommendations')
    ->withoutOverlapping()
    ->description('Generate personalized recommendations for users');

Schedule::job(new UpdateRecommendationScoresJob)
    ->hourly()
    ->name('update-recommendation-scores')
    ->withoutOverlapping()
    ->description('Update recommendation scores based on freshness and engagement');

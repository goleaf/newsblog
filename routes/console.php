<?php

use App\Console\Commands\ArchiveActivityLogs;
use App\Console\Commands\ArchiveSearchLogs;
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

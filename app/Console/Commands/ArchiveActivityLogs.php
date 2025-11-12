<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class ArchiveActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-logs:archive 
                            {--days=90 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old activity logs by removing entries older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysToKeep = (int) $this->option('days');

        if ($daysToKeep < 1) {
            $this->error('Days must be a positive integer.');

            return self::FAILURE;
        }

        $this->info("Archiving activity logs older than {$daysToKeep} days...");

        try {
            $cutoffDate = now()->subDays($daysToKeep);
            $archivedCount = ActivityLog::where('created_at', '<', $cutoffDate)->delete();

            if ($archivedCount > 0) {
                $this->info("Successfully archived {$archivedCount} activity log(s).");

                return self::SUCCESS;
            }

            $this->info('No activity logs to archive.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to archive activity logs: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}

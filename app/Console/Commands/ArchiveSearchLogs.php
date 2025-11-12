<?php

namespace App\Console\Commands;

use App\Services\SearchAnalyticsService;
use Illuminate\Console\Command;

class ArchiveSearchLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:archive-logs 
                            {--days=90 : Number of days to keep logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old search logs by removing entries older than specified days';

    /**
     * Execute the console command.
     */
    public function handle(SearchAnalyticsService $searchAnalyticsService): int
    {
        $daysToKeep = (int) $this->option('days');

        if ($daysToKeep < 1) {
            $this->error('Days must be a positive integer.');

            return self::FAILURE;
        }

        $this->info("Archiving search logs older than {$daysToKeep} days...");

        try {
            $archivedCount = $searchAnalyticsService->archiveLogs($daysToKeep);

            if ($archivedCount > 0) {
                $this->info("Successfully archived {$archivedCount} search log(s).");

                return self::SUCCESS;
            }

            $this->info('No search logs to archive.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to archive search logs: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}

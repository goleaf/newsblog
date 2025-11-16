<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CleanupOldNotifications implements ShouldQueue
{
    use Queueable;

    /**
     * The number of days to keep read notifications.
     */
    public int $daysOld = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(int $daysOld = 30)
    {
        $this->daysOld = $daysOld;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $deletedCount = $notificationService->deleteOldNotifications($this->daysOld);

        Log::info('Cleanup old notifications job completed', [
            'deleted_count' => $deletedCount,
            'days_old' => $this->daysOld,
        ]);
    }
}

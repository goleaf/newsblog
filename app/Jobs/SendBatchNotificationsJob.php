<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendBatchNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Collection $users,
        public Notification $notification
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send notifications in batches to avoid overwhelming the mail server
        $this->users->chunk(50)->each(function ($chunk) {
            foreach ($chunk as $user) {
                try {
                    $user->notify($this->notification);
                } catch (\Exception $e) {
                    \Log::error('Failed to send notification to user', [
                        'user_id' => $user->id,
                        'notification_class' => get_class($this->notification),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Small delay between batches to prevent rate limiting
            if ($this->users->count() > 50) {
                sleep(1);
            }
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Failed to send batch notifications', [
            'user_count' => $this->users->count(),
            'notification_class' => get_class($this->notification),
            'error' => $exception->getMessage(),
        ]);
    }
}

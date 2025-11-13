<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BulkImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProcessBulkImportJob implements ShouldQueue
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
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $filePath,
        public array $options,
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(BulkImportService $importService): void
    {
        $jobId = $this->getJobId();

        Log::channel('import')->info('Background import job started', [
            'job_id' => $jobId,
            'file' => $this->filePath,
            'options' => $this->options,
        ]);

        try {
            // Initialize progress tracking
            $this->updateProgress(0, 0, 'initializing');

            // Add progress callback to track import progress
            $this->options['progress_callback'] = function ($current, $total) {
                $this->updateProgress($current, $total, 'processing');
            };

            // Execute import
            $result = $importService->import($this->filePath, $this->options);

            // Update final progress
            $this->updateProgress($result['successful'], $result['total_rows'], 'completed', $result);

            // Send completion notification to administrator
            $this->sendCompletionNotification($result);

            Log::channel('import')->info('Background import job completed', [
                'job_id' => $jobId,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            // Update progress with error status
            $this->updateProgress(0, 0, 'failed', ['error' => $e->getMessage()]);

            Log::channel('import')->error('Background import job failed', [
                'job_id' => $jobId,
                'file' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $jobId = $this->getJobId();

        Log::channel('import')->error('Background import job failed permanently', [
            'job_id' => $jobId,
            'file' => $this->filePath,
            'error' => $exception->getMessage(),
        ]);

        // Update progress with permanent failure status
        $this->updateProgress(0, 0, 'failed', [
            'error' => $exception->getMessage(),
            'permanent_failure' => true,
        ]);

        // Send failure notification
        $this->sendFailureNotification($exception);
    }

    /**
     * Update import progress in cache.
     */
    protected function updateProgress(int $current, int $total, string $status, array $additionalData = []): void
    {
        $jobId = $this->getJobId();
        $percentage = $total > 0 ? round(($current / $total) * 100, 2) : 0;

        $progressData = [
            'job_id' => $jobId,
            'file' => basename($this->filePath),
            'current' => $current,
            'total' => $total,
            'percentage' => $percentage,
            'status' => $status,
            'updated_at' => now()->toIso8601String(),
        ];

        // Add estimated time remaining for processing status
        if ($status === 'processing' && $current > 0 && $total > 0) {
            $startTime = Cache::get("import_job_{$jobId}_start_time");
            if ($startTime) {
                $elapsed = now()->diffInSeconds($startTime);
                $rate = $current / $elapsed;
                $remaining = ($total - $current) / $rate;
                $progressData['estimated_remaining_seconds'] = round($remaining);
                $progressData['estimated_remaining'] = $this->formatDuration($remaining);
            }
        }

        // Merge additional data (like final results or errors)
        $progressData = array_merge($progressData, $additionalData);

        // Store in cache with 24 hour expiration
        Cache::put("import_job_{$jobId}", $progressData, now()->addHours(24));

        // Store start time on first update
        if ($status === 'initializing') {
            Cache::put("import_job_{$jobId}_start_time", now(), now()->addHours(24));

            // Add to registry of active jobs
            $this->addToRegistry($jobId);
        }

        // Remove from registry when completed or failed
        if (in_array($status, ['completed', 'failed'])) {
            $this->removeFromRegistry($jobId);
        }
    }

    /**
     * Add job to registry of active jobs.
     */
    protected function addToRegistry(string $jobId): void
    {
        $registry = Cache::get('import_jobs_registry', []);

        if (! in_array($jobId, $registry)) {
            $registry[] = $jobId;
            Cache::put('import_jobs_registry', $registry, now()->addHours(24));
        }
    }

    /**
     * Remove job from registry of active jobs.
     */
    protected function removeFromRegistry(string $jobId): void
    {
        $registry = Cache::get('import_jobs_registry', []);
        $registry = array_filter($registry, fn ($id) => $id !== $jobId);
        Cache::put('import_jobs_registry', array_values($registry), now()->addHours(24));
    }

    /**
     * Send completion notification to administrator.
     */
    protected function sendCompletionNotification(array $result): void
    {
        try {
            $user = User::find($this->userId);

            if ($user) {
                // Create a simple notification message
                $message = sprintf(
                    'Import completed: %d posts created from %s',
                    $result['posts_created'],
                    basename($this->filePath)
                );

                Log::channel('import')->info('Completion notification sent', [
                    'user_id' => $this->userId,
                    'message' => $message,
                ]);

                // Note: In a real application, you would send an actual notification
                // For now, we just log it since we don't have a notification system set up
            }
        } catch (\Exception $e) {
            Log::channel('import')->warning('Failed to send completion notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send failure notification to administrator.
     */
    protected function sendFailureNotification(\Throwable $exception): void
    {
        try {
            $user = User::find($this->userId);

            if ($user) {
                $message = sprintf(
                    'Import failed for %s: %s',
                    basename($this->filePath),
                    $exception->getMessage()
                );

                Log::channel('import')->info('Failure notification sent', [
                    'user_id' => $this->userId,
                    'message' => $message,
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('import')->warning('Failed to send failure notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get unique job identifier.
     */
    protected function getJobId(): string
    {
        return md5($this->filePath.json_encode($this->options));
    }

    /**
     * Format duration in seconds to human-readable format.
     */
    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds).'s';
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = round($seconds % 60);

            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$hours}h {$minutes}m";
    }
}

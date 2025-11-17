<?php

namespace App\Services;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class BatchProgressService
{
    /**
     * Get batch progress information.
     */
    public function getProgress(string $batchId): ?array
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return null;
        }

        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'processed_jobs' => $batch->processedJobs(),
            'failed_jobs' => $batch->failedJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'created_at' => $batch->createdAt,
            'finished_at' => $batch->finishedAt,
        ];
    }

    /**
     * Get all active batches.
     */
    public function getActiveBatches(): array
    {
        // Cache active batch IDs
        $cacheKey = 'batch_progress:active_batches';
        $activeBatchIds = Cache::get($cacheKey, []);

        $batches = [];
        foreach ($activeBatchIds as $batchId) {
            $progress = $this->getProgress($batchId);
            if ($progress && ! $progress['finished']) {
                $batches[] = $progress;
            }
        }

        return $batches;
    }

    /**
     * Track a batch for progress monitoring.
     */
    public function trackBatch(string $batchId): void
    {
        $cacheKey = 'batch_progress:active_batches';
        $activeBatchIds = Cache::get($cacheKey, []);

        if (! in_array($batchId, $activeBatchIds)) {
            $activeBatchIds[] = $batchId;
            Cache::put($cacheKey, $activeBatchIds, now()->addDays(7));
        }
    }

    /**
     * Remove a batch from tracking.
     */
    public function untrackBatch(string $batchId): void
    {
        $cacheKey = 'batch_progress:active_batches';
        $activeBatchIds = Cache::get($cacheKey, []);

        $activeBatchIds = array_filter($activeBatchIds, fn ($id) => $id !== $batchId);
        Cache::put($cacheKey, array_values($activeBatchIds), now()->addDays(7));
    }

    /**
     * Get batch statistics.
     */
    public function getBatchStats(string $batchId): ?array
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return null;
        }

        $duration = null;
        if ($batch->finishedAt) {
            $duration = $batch->createdAt->diffInSeconds($batch->finishedAt);
        }

        return [
            'total_jobs' => $batch->totalJobs,
            'successful_jobs' => $batch->totalJobs - $batch->failedJobs,
            'failed_jobs' => $batch->failedJobs,
            'success_rate' => $batch->totalJobs > 0
                ? round((($batch->totalJobs - $batch->failedJobs) / $batch->totalJobs) * 100, 2)
                : 0,
            'duration_seconds' => $duration,
            'average_job_time' => $duration && $batch->totalJobs > 0
                ? round($duration / $batch->totalJobs, 2)
                : null,
        ];
    }

    /**
     * Cancel a batch.
     */
    public function cancelBatch(string $batchId): bool
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch || $batch->finished()) {
            return false;
        }

        $batch->cancel();

        return true;
    }

    /**
     * Retry failed jobs in a batch.
     */
    public function retryFailedJobs(string $batchId): bool
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return false;
        }

        // Get failed job IDs and retry them
        foreach ($batch->failedJobIds as $failedJobId) {
            // Retry the job using artisan command
            \Artisan::call('queue:retry', ['id' => $failedJobId]);
        }

        return true;
    }
}

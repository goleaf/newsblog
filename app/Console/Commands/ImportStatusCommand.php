<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:import-status
                            {--job-id= : Specific job ID to check (optional)}
                            {--all : Show all active import jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the progress of background import jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $jobId = $this->option('job-id');
        $showAll = $this->option('all');

        if ($jobId) {
            return $this->showJobStatus($jobId);
        }

        if ($showAll) {
            return $this->showAllJobs();
        }

        // Show most recent job by default
        return $this->showMostRecentJob();
    }

    /**
     * Show status for a specific job.
     */
    protected function showJobStatus(string $jobId): int
    {
        $progress = Cache::get("import_job_{$jobId}");

        if (! $progress) {
            $this->error("No import job found with ID: {$jobId}");

            return self::FAILURE;
        }

        $this->displayJobStatus($progress);

        return self::SUCCESS;
    }

    /**
     * Show all active import jobs.
     */
    protected function showAllJobs(): int
    {
        // Get all cache keys matching import jobs
        $keys = $this->getImportJobKeys();

        if (empty($keys)) {
            $this->info('No active import jobs found');

            return self::SUCCESS;
        }

        $this->info('Active Import Jobs');
        $this->info(str_repeat('=', 80));
        $this->newLine();

        foreach ($keys as $index => $key) {
            $progress = Cache::get($key);

            if ($progress) {
                $this->line('Job #'.($index + 1));
                $this->displayJobStatus($progress, false);

                if ($index < count($keys) - 1) {
                    $this->newLine();
                    $this->line(str_repeat('-', 80));
                    $this->newLine();
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Show the most recent import job.
     */
    protected function showMostRecentJob(): int
    {
        $keys = $this->getImportJobKeys();

        if (empty($keys)) {
            $this->info('No active import jobs found');
            $this->newLine();
            $this->line('Use --all to see all jobs or --job-id=<id> to check a specific job');

            return self::SUCCESS;
        }

        // Get the most recent job (last updated)
        $mostRecent = null;
        $mostRecentTime = null;

        foreach ($keys as $key) {
            $progress = Cache::get($key);

            if ($progress && isset($progress['updated_at'])) {
                $updatedAt = strtotime($progress['updated_at']);

                if ($mostRecentTime === null || $updatedAt > $mostRecentTime) {
                    $mostRecentTime = $updatedAt;
                    $mostRecent = $progress;
                }
            }
        }

        if ($mostRecent) {
            $this->info('Most Recent Import Job');
            $this->info(str_repeat('=', 80));
            $this->newLine();
            $this->displayJobStatus($mostRecent);
        } else {
            $this->info('No active import jobs found');
        }

        return self::SUCCESS;
    }

    /**
     * Display job status information.
     */
    protected function displayJobStatus(array $progress, bool $showHeader = true): void
    {
        if ($showHeader) {
            $this->line("Job ID: {$progress['job_id']}");
        } else {
            $this->line("  Job ID: {$progress['job_id']}");
        }

        $indent = $showHeader ? '' : '  ';

        $this->line("{$indent}File: {$progress['file']}");
        $this->line("{$indent}Status: ".$this->formatStatus($progress['status']));

        if ($progress['status'] === 'processing' || $progress['status'] === 'completed') {
            $this->line("{$indent}Progress: {$progress['current']}/{$progress['total']} ({$progress['percentage']}%)");

            if (isset($progress['estimated_remaining'])) {
                $this->line("{$indent}Estimated Time Remaining: {$progress['estimated_remaining']}");
            }
        }

        if ($progress['status'] === 'completed' && isset($progress['posts_created'])) {
            $this->newLine();
            $this->line("{$indent}Results:");
            $this->line("{$indent}  Posts Created: {$progress['posts_created']}");
            $this->line("{$indent}  Tags Created: {$progress['tags_created']}");
            $this->line("{$indent}  Categories Created: {$progress['categories_created']}");

            if (isset($progress['content_generated'])) {
                $this->line("{$indent}  Content Generated: {$progress['content_generated']}");
            }

            if (isset($progress['images_assigned'])) {
                $this->line("{$indent}  Images Assigned: {$progress['images_assigned']}");
            }

            if (isset($progress['duration'])) {
                $this->line("{$indent}  Duration: {$progress['duration']}s");
            }

            if (isset($progress['posts_per_second'])) {
                $this->line("{$indent}  Average Speed: {$progress['posts_per_second']} posts/second");
            }
        }

        if ($progress['status'] === 'failed' && isset($progress['error'])) {
            $this->newLine();
            $this->line("{$indent}<fg=red>Error: {$progress['error']}</>");

            if (isset($progress['permanent_failure']) && $progress['permanent_failure']) {
                $this->line("{$indent}<fg=red>This job has permanently failed after multiple retry attempts.</>");
            }
        }

        $this->line("{$indent}Last Updated: {$progress['updated_at']}");
    }

    /**
     * Format status with color.
     */
    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'initializing' => '<fg=yellow>Initializing</>',
            'processing' => '<fg=blue>Processing</>',
            'completed' => '<fg=green>Completed</>',
            'failed' => '<fg=red>Failed</>',
            default => $status,
        };
    }

    /**
     * Get all import job cache keys.
     */
    protected function getImportJobKeys(): array
    {
        // Note: This is a simplified implementation
        // In production, you might want to use Redis SCAN or maintain a separate index
        $keys = [];

        // Try to get keys from a known pattern
        // This assumes we're using a cache driver that supports key patterns
        try {
            // For file/database cache, we need to scan manually
            // For Redis, we could use SCAN command
            // For now, we'll use a simple approach with a registry

            // Check if there's a registry of active jobs
            $registry = Cache::get('import_jobs_registry', []);

            foreach ($registry as $jobId) {
                if (Cache::has("import_job_{$jobId}")) {
                    $keys[] = "import_job_{$jobId}";
                }
            }
        } catch (\Exception $e) {
            // Fallback: return empty array
        }

        return $keys;
    }
}

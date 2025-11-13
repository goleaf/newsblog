<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBulkImportJob;
use App\Services\BulkImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ImportNewsArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:import
                            {file : Path to CSV file or directory containing CSV files}
                            {--chunk-size=1000 : Number of rows to process per chunk}
                            {--skip-content : Skip content generation}
                            {--skip-images : Skip image assignment}
                            {--user-id= : User ID to assign as post author}
                            {--status=published : Post status (draft, published, scheduled)}
                            {--queue : Process import in background queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import news articles from CSV file(s) in bulk';

    /**
     * Execute the console command.
     */
    public function handle(BulkImportService $importService): int
    {
        $filePath = $this->argument('file');

        // Validate file/directory exists
        if (! File::exists($filePath)) {
            $this->error("File or directory not found: {$filePath}");

            return self::FAILURE;
        }

        // Validate options
        if (! $this->validateOptions()) {
            return self::FAILURE;
        }

        // Check if path is a directory
        if (File::isDirectory($filePath)) {
            return $this->processDirectory($filePath, $importService);
        }

        // Process single file
        return $this->processFile($filePath, $importService);
    }

    /**
     * Validate command options.
     */
    protected function validateOptions(): bool
    {
        $chunkSize = $this->option('chunk-size');
        if (! is_numeric($chunkSize) || $chunkSize < 1) {
            $this->error('Chunk size must be a positive integer');

            return false;
        }

        $status = $this->option('status');
        if (! in_array($status, ['draft', 'published', 'scheduled'])) {
            $this->error('Status must be one of: draft, published, scheduled');

            return false;
        }

        $userId = $this->option('user-id');
        if ($userId !== null && (! is_numeric($userId) || $userId < 1)) {
            $this->error('User ID must be a positive integer');

            return false;
        }

        return true;
    }

    /**
     * Process all CSV files in a directory.
     */
    protected function processDirectory(string $directoryPath, BulkImportService $importService): int
    {
        $csvFiles = File::glob($directoryPath.'/*.csv');

        if (empty($csvFiles)) {
            $this->error("No CSV files found in directory: {$directoryPath}");

            return self::FAILURE;
        }

        $this->info('Found '.count($csvFiles).' CSV file(s) to process');
        $this->newLine();

        $overallSuccess = true;

        foreach ($csvFiles as $index => $file) {
            $this->info('Processing file '.($index + 1).' of '.count($csvFiles).': '.basename($file));
            $this->newLine();

            $result = $this->processFile($file, $importService);

            if ($result === self::FAILURE) {
                $overallSuccess = false;
            }

            if ($index < count($csvFiles) - 1) {
                $this->newLine(2);
                $this->line(str_repeat('=', 80));
                $this->newLine();
            }
        }

        return $overallSuccess ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Process a single CSV file.
     */
    protected function processFile(string $filePath, BulkImportService $importService): int
    {
        // Validate file is CSV
        if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'csv') {
            $this->error("File must be a CSV file: {$filePath}");

            return self::FAILURE;
        }

        // Build options array
        $options = [
            'chunk_size' => (int) $this->option('chunk-size'),
            'skip_content' => $this->option('skip-content'),
            'skip_images' => $this->option('skip-images'),
            'user_id' => $this->option('user-id') ? (int) $this->option('user-id') : null,
            'status' => $this->option('status'),
            'queue' => $this->option('queue'),
        ];

        // Check if should queue
        if ($options['queue']) {
            $userId = $options['user_id'] ?? config('import.default_user_id', 1);

            // Dispatch job to queue
            ProcessBulkImportJob::dispatch($filePath, $options, $userId);

            $this->info('Import job dispatched to background queue');
            $this->info('Use "php artisan news:import-status" command to check progress');
            $this->newLine();

            return self::SUCCESS;
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $this->info('Starting import...');
        $this->info('File: '.basename($filePath));
        $this->newLine();

        try {
            // Create progress callback
            $progressBar = null;
            $options['progress_callback'] = function ($current, $total) use (&$progressBar) {
                if ($progressBar === null) {
                    $progressBar = $this->output->createProgressBar($total);
                    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
                    $progressBar->start();
                }
                $progressBar->setProgress($current);
            };

            // Execute import
            $result = $importService->import($filePath, $options);

            if ($progressBar) {
                $progressBar->finish();
                $this->newLine(2);
            }

            // Calculate metrics
            $duration = round(microtime(true) - $startTime, 2);
            $memoryUsed = $this->formatBytes(memory_get_peak_usage(true) - $startMemory);
            $postsPerSecond = $result['successful'] > 0 ? round($result['successful'] / $duration, 2) : 0;

            // Display summary
            $this->displaySummary($result, $duration, $memoryUsed, $postsPerSecond, basename($filePath));

            // Log completion
            Log::channel('import')->info('Import completed', [
                'file' => $filePath,
                'result' => $result,
                'duration' => $duration,
            ]);

            return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('Import failed: '.$e->getMessage());

            Log::channel('import')->error('Import failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Display import summary report.
     */
    protected function displaySummary(array $result, float $duration, string $memoryUsed, float $postsPerSecond, string $fileName): void
    {
        $this->info('Import Summary');
        $this->info(str_repeat('=', 80));
        $this->line("File: {$fileName}");
        $this->line("Total Rows: {$result['total_rows']}");
        $this->line("Successful: {$result['successful']}");
        $this->line("Failed: {$result['failed']}");
        $this->line("Skipped (Duplicates): {$result['skipped']}");
        $this->newLine();
        $this->line("Posts Created: {$result['posts_created']}");
        $this->line("Tags Created: {$result['tags_created']}");
        $this->line("Categories Created: {$result['categories_created']}");

        if (! empty($result['content_generated'])) {
            $this->line("Content Generated: {$result['content_generated']}");
        }

        if (! empty($result['images_assigned'])) {
            $this->line("Images Assigned: {$result['images_assigned']}");
        }

        $this->newLine();
        $this->line("Duration: {$duration}s");
        $this->line("Average Speed: {$postsPerSecond} posts/second");
        $this->line("Memory Peak: {$memoryUsed}");

        if (isset($result['total_queries'])) {
            $this->line("Total Queries: {$result['total_queries']}");
            $this->line("Queries per Post: {$result['queries_per_post']}");
        }

        if ($result['failed'] > 0 || ! empty($result['errors'])) {
            $this->newLine();
            $logFile = storage_path('logs/import-'.date('Y-m-d').'.log');
            $this->line("Errors logged to: {$logFile}");
        }
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}

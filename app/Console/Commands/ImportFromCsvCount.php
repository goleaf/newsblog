<?php

namespace App\Console\Commands;

use App\Services\BulkImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportFromCsvCount extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'news:import-count {count : Number of rows to import across CSV files}';

    /**
     * The console command description.
     */
    protected $description = 'Import the specified number of rows from discovered CSV files (no extra options)';

    /**
     * Execute the console command.
     */
    public function handle(BulkImportService $importService): int
    {
        $countArg = $this->argument('count');

        if (! is_numeric($countArg) || (int) $countArg < 1) {
            $this->error('Count must be a positive integer');

            return self::FAILURE;
        }

        $targetCount = (int) $countArg;

        // Discover CSV files in common locations
        $csvFiles = $this->discoverCsvFiles();

        if (empty($csvFiles)) {
            $this->error('No CSV files found.');
            $this->line('Place CSV files in one of:');
            $this->line(' - '.database_path('data'));
            $this->line(' - '.base_path());

            return self::FAILURE;
        }

        $this->info('Discovered '.count($csvFiles).' CSV file(s).');

        $remaining = $targetCount;

        $aggregate = [
            'total_rows' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'posts_created' => 0,
            'tags_created' => 0,
            'categories_created' => 0,
            'duration' => 0.0,
            'total_queries' => 0,
        ];

        foreach ($csvFiles as $idx => $file) {
            if ($remaining <= 0) {
                break;
            }

            $limitForThisFile = $remaining;

            $this->newLine();
            $this->info('Processing file '.($idx + 1).' of '.count($csvFiles).': '.basename($file));
            $this->line('Taking up to '.$limitForThisFile.' rows...');
            $this->newLine();

            $progressBar = null;
            $options = [
                'chunk_size' => (int) config('import.chunk_size', 1000),
                'limit' => $limitForThisFile,
                // Keep imports fast and deterministic by default
                'skip_content' => true,
                'skip_images' => true,
            ];

            $options['progress_callback'] = function ($current, $total) use (&$progressBar) {
                if ($progressBar === null) {
                    $progressBar = $this->output->createProgressBar($total);
                    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
                    $progressBar->start();
                }
                $progressBar->setProgress($current);
            };

            $result = $importService->import($file, $options);

            if ($progressBar) {
                $progressBar->finish();
                $this->newLine(2);
            }

            // Update aggregate
            foreach (['total_rows', 'successful', 'failed', 'skipped', 'posts_created', 'tags_created', 'categories_created', 'total_queries'] as $key) {
                $aggregate[$key] += $result[$key] ?? 0;
            }
            $aggregate['duration'] += (float) ($result['duration'] ?? 0.0);

            // Decrease remaining target by actual posts created
            $remaining -= (int) ($result['posts_created'] ?? 0);
        }

        // Show final summary
        $this->newLine();
        $this->info('Import Summary');
        $this->info(str_repeat('=', 80));
        $this->line('Requested: '.$targetCount);
        $this->line('Successful: '.$aggregate['successful']);
        $this->line('Failed: '.$aggregate['failed']);
        $this->line('Skipped (Duplicates): '.$aggregate['skipped']);
        $this->newLine();
        $this->line('Posts Created: '.$aggregate['posts_created']);
        $this->line('Tags Created: '.$aggregate['tags_created']);
        $this->line('Categories Created: '.$aggregate['categories_created']);
        $this->newLine();
        $this->line('Duration: '.round($aggregate['duration'], 2).'s');
        $pps = $aggregate['duration'] > 0 ? round($aggregate['successful'] / $aggregate['duration'], 2) : 0;
        $this->line('Average Speed: '.$pps.' posts/second');
        if ($aggregate['successful'] > 0) {
            $this->line('Total Queries: '.$aggregate['total_queries']);
            $this->line('Queries per Post: '.round($aggregate['total_queries'] / max(1, $aggregate['successful']), 2));
        }

        if ($aggregate['failed'] > 0) {
            $this->newLine();
            $logFile = storage_path('logs/import-'.date('Y-m-d').'.log');
            $this->line('Errors logged to: '.$logFile);
        }

        return $aggregate['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Discover CSV files in standard locations.
     *
     * @return array<string>
     */
    protected function discoverCsvFiles(): array
    {
        $candidates = [];

        $locations = [
            database_path('data'),
            base_path(),
        ];

        foreach ($locations as $dir) {
            if (File::isDirectory($dir)) {
                $files = glob(rtrim($dir, '/').'/*.csv') ?: [];
                foreach ($files as $f) {
                    $candidates[] = $f;
                }
            }
        }

        // Unique and natural sort for stable order
        $candidates = array_values(array_unique($candidates));
        natsort($candidates);

        return array_values($candidates);
    }
}

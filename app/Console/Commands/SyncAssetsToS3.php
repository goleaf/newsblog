<?php

namespace App\Console\Commands;

use App\Services\CdnService;
use Illuminate\Console\Command;

class SyncAssetsToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:sync-s3 
                            {--directory=public : The local directory to sync}
                            {--s3-directory= : The S3 directory prefix}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local storage assets to S3 with CloudFront CDN';

    /**
     * Execute the console command.
     */
    public function handle(CdnService $cdnService): int
    {
        if (! $cdnService->isS3Configured()) {
            $this->error('S3 is not configured. Please set AWS credentials in .env file.');

            return self::FAILURE;
        }

        $directory = $this->option('directory');
        $s3Directory = $this->option('s3-directory') ?? '';
        $dryRun = $this->option('dry-run');

        $this->info('Syncing assets to S3...');
        $this->info("Local directory: {$directory}");
        $this->info('S3 directory: '.($s3Directory ?: '(root)'));

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be uploaded');
        }

        if (! $dryRun) {
            $result = $cdnService->syncToS3($directory, $s3Directory);

            $this->newLine();
            $this->info('Sync completed!');
            $this->info('Files synced: '.count($result['synced']));

            if (! empty($result['failed'])) {
                $this->error('Files failed: '.count($result['failed']));
                $this->newLine();
                $this->error('Failed files:');
                foreach ($result['failed'] as $failed) {
                    $this->line('  - '.$failed);
                }
            }

            return empty($result['failed']) ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }
}

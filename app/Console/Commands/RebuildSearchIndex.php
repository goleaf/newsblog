<?php

namespace App\Console\Commands;

use App\Services\SearchIndexService;
use Illuminate\Console\Command;

use function Laravel\Prompts\progress;

class RebuildSearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:rebuild-index 
                            {--type= : Specific index type to rebuild (posts, tags, categories)}
                            {--all : Rebuild all indexes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the search index for posts, tags, and categories';

    /**
     * Execute the console command.
     */
    public function handle(SearchIndexService $searchIndexService): int
    {
        $type = $this->option('type');
        $all = $this->option('all');

        $indexTypes = ['posts', 'tags', 'categories'];

        if ($type && ! in_array($type, $indexTypes, true)) {
            $this->error("Invalid index type: {$type}. Valid types are: ".implode(', ', $indexTypes));

            return self::FAILURE;
        }

        if ($all || ! $type) {
            $typesToRebuild = $indexTypes;
        } else {
            $typesToRebuild = [$type];
        }

        $this->info('Starting search index rebuild...');

        $results = progress(
            label: 'Rebuilding indexes',
            steps: $typesToRebuild,
            callback: function ($indexType, $progress) use ($searchIndexService) {
                $progress->label("Rebuilding {$indexType} index...");

                try {
                    $count = $searchIndexService->rebuildIndex($indexType);
                    $progress->hint("Indexed {$count} {$indexType}");

                    return ['type' => $indexType, 'count' => $count, 'success' => true];
                } catch (\Exception $e) {
                    $progress->hint("Failed: {$e->getMessage()}");

                    return ['type' => $indexType, 'count' => 0, 'success' => false, 'error' => $e->getMessage()];
                }
            },
            hint: 'This may take some time depending on the amount of data.'
        );

        $this->newLine();
        $this->info('Index rebuild completed:');

        $successCount = 0;
        $totalCount = 0;

        foreach ($results as $result) {
            if ($result['success']) {
                $this->line("  ✓ {$result['type']}: {$result['count']} items indexed");
                $successCount++;
                $totalCount += $result['count'];
            } else {
                $this->error("  ✗ {$result['type']}: {$result['error']}");
            }
        }

        if ($successCount === count($typesToRebuild)) {
            $this->info("Successfully rebuilt {$successCount} index(es) with {$totalCount} total items.");

            return self::SUCCESS;
        }

        $this->warn("Rebuild completed with errors. {$successCount} of ".count($typesToRebuild).' indexes rebuilt successfully.');

        return self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class ClearApplicationCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-app {--type=all : Type of cache to clear (all, views, queries, models)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application-specific caches (views, queries, models)';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $type = $this->option('type');

        $this->info('Clearing application caches...');

        match ($type) {
            'views' => $this->clearViews($cacheService),
            'queries' => $this->clearQueries($cacheService),
            'models' => $this->clearModels($cacheService),
            'all' => $this->clearAll($cacheService),
            default => $this->clearAll($cacheService),
        };

        $this->info('Application caches cleared successfully!');

        return Command::SUCCESS;
    }

    protected function clearViews(CacheService $cacheService): void
    {
        $this->line('Clearing view caches...');
        $cacheService->invalidateAllViews();
    }

    protected function clearQueries(CacheService $cacheService): void
    {
        $this->line('Clearing query caches...');
        $cacheService->invalidateAllQueries();
    }

    protected function clearModels(CacheService $cacheService): void
    {
        $this->line('Clearing model caches...');
        // Model caches are cleared through invalidateAllViews
        $cacheService->invalidateAllViews();
    }

    protected function clearAll(CacheService $cacheService): void
    {
        $this->line('Clearing all application caches...');
        $cacheService->clearAll();
    }
}

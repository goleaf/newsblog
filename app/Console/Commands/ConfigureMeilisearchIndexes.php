<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MeiliSearch\Client;

class ConfigureMeilisearchIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:configure-meilisearch
                            {--force : Force reconfiguration even if index exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Meilisearch indexes with searchable attributes, ranking rules, and synonyms';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (config('scout.driver') !== 'meilisearch') {
            $this->error('Scout driver is not set to meilisearch. Current driver: '.config('scout.driver'));
            $this->info('Set SCOUT_DRIVER=meilisearch in your .env file');

            return self::FAILURE;
        }

        try {
            $client = new Client(
                config('scout.meilisearch.host'),
                config('scout.meilisearch.key')
            );

            // Test connection
            $this->info('Testing Meilisearch connection...');
            $health = $client->health();
            $this->info('✓ Connected to Meilisearch: '.$health['status']);

            $indexSettings = config('scout.meilisearch.index-settings', []);

            if (empty($indexSettings)) {
                $this->warn('No index settings found in config/scout.php');

                return self::SUCCESS;
            }

            foreach ($indexSettings as $indexName => $settings) {
                $this->info("Configuring index: {$indexName}");

                // Get or create index
                $indexPrefix = config('scout.prefix', '');
                $fullIndexName = $indexPrefix ? "{$indexPrefix}_{$indexName}" : $indexName;

                $index = $client->index($fullIndexName);

                // Configure searchable attributes
                if (isset($settings['searchableAttributes'])) {
                    $this->info('  - Setting searchable attributes...');
                    $index->updateSearchableAttributes($settings['searchableAttributes']);
                }

                // Configure displayed attributes
                if (isset($settings['displayedAttributes'])) {
                    $this->info('  - Setting displayed attributes...');
                    $index->updateDisplayedAttributes($settings['displayedAttributes']);
                }

                // Configure filterable attributes
                if (isset($settings['filterableAttributes'])) {
                    $this->info('  - Setting filterable attributes...');
                    $index->updateFilterableAttributes($settings['filterableAttributes']);
                }

                // Configure sortable attributes
                if (isset($settings['sortableAttributes'])) {
                    $this->info('  - Setting sortable attributes...');
                    $index->updateSortableAttributes($settings['sortableAttributes']);
                }

                // Configure ranking rules
                if (isset($settings['rankingRules'])) {
                    $this->info('  - Setting ranking rules...');
                    $index->updateRankingRules($settings['rankingRules']);
                }

                // Configure typo tolerance
                if (isset($settings['typoTolerance'])) {
                    $this->info('  - Setting typo tolerance...');
                    $index->updateTypoTolerance($settings['typoTolerance']);
                }

                // Configure synonyms
                if (isset($settings['synonyms'])) {
                    $this->info('  - Setting synonyms...');
                    $index->updateSynonyms($settings['synonyms']);
                }

                // Configure stop words
                if (isset($settings['stopWords'])) {
                    $this->info('  - Setting stop words...');
                    $index->updateStopWords($settings['stopWords']);
                }

                // Configure pagination
                if (isset($settings['pagination'])) {
                    $this->info('  - Setting pagination...');
                    $index->updatePagination($settings['pagination']);
                }

                $this->info("✓ Index '{$fullIndexName}' configured successfully");
                $this->newLine();
            }

            $this->info('All indexes configured successfully!');
            $this->newLine();
            $this->info('Next steps:');
            $this->info('1. Import your data: php artisan scout:import "App\Models\Post"');
            $this->info('2. Check index status in Meilisearch dashboard');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to configure Meilisearch indexes: '.$e->getMessage());
            $this->newLine();
            $this->info('Make sure Meilisearch is running and accessible at: '.config('scout.meilisearch.host'));

            return self::FAILURE;
        }
    }
}

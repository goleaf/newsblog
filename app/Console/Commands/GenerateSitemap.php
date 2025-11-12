<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate XML sitemap for the website';

    /**
     * Execute the console command.
     */
    public function handle(SitemapService $sitemapService): int
    {
        $this->info('Generating sitemap...');

        try {
            $files = $sitemapService->generate();

            $this->info('Sitemap generated successfully!');
            $this->newLine();

            if (count($files) > 1) {
                $this->info('Generated '.count($files).' sitemap files:');
                foreach ($files as $file) {
                    $this->line('  - '.$file['url']);
                }
                $this->newLine();
                $this->info('Sitemap index: '.asset('storage/sitemap-index.xml'));
            } else {
                $this->info('Sitemap URL: '.$files[0]['url']);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate sitemap: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

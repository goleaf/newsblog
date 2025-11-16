<?php

namespace App\Console\Commands;

use App\Jobs\CheckBrokenLinks;
use Illuminate\Console\Command;

class CheckBrokenLinksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all published posts for broken external links';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting broken link check...');

        CheckBrokenLinks::dispatch();

        $this->info('Broken link check job dispatched successfully.');

        return Command::SUCCESS;
    }
}

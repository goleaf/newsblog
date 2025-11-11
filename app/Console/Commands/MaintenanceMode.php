<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MaintenanceMode extends Command
{
    protected $signature = 'maintenance {action : enable or disable}';

    protected $description = 'Enable or disable maintenance mode';

    public function handle(): int
    {
        $action = $this->argument('action');
        $file = storage_path('framework/down');

        if ($action === 'enable') {
            if (File::exists($file)) {
                $this->info('Maintenance mode is already enabled.');

                return Command::SUCCESS;
            }

            File::put($file, json_encode([
                'time' => now()->timestamp,
                'retry' => 60,
                'secret' => bin2hex(random_bytes(16)),
            ], JSON_PRETTY_PRINT));

            $this->info('Maintenance mode enabled.');
        } elseif ($action === 'disable') {
            if (! File::exists($file)) {
                $this->info('Maintenance mode is not enabled.');

                return Command::SUCCESS;
            }

            File::delete($file);
            $this->info('Maintenance mode disabled.');
        } else {
            $this->error('Invalid action. Use "enable" or "disable".');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}

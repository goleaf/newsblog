<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--retention=30 : Days to keep backups}';

    protected $description = 'Backup the database file (sqlite) to storage/app/backups and prune old backups';

    public function handle(): int
    {
        $connection = Config::get('database.default', 'sqlite');

        if ($connection !== 'sqlite') {
            $this->warn('Only sqlite backups are supported by this command. Current connection: '.$connection);

            return self::SUCCESS;
        }

        $dbPath = Config::get('database.connections.sqlite.database');

        if ($dbPath === ':memory:') {
            $this->warn('SQLite is using in-memory database; creating a placeholder backup.');
            $content = '-- in-memory sqlite (no file) --';
            $this->writeBackup($content);
            $this->prune();
            $this->info('Backup placeholder created.');

            return self::SUCCESS;
        }

        if (! file_exists($dbPath)) {
            $this->error('SQLite database file not found at: '.$dbPath);

            return self::FAILURE;
        }

        $content = file_get_contents($dbPath) ?: '';
        $this->writeBackup($content);
        $this->prune();
        $this->info('Backup created successfully.');

        return self::SUCCESS;
    }

    protected function writeBackup(string $content): void
    {
        $disk = Storage::disk('local');
        $dir = 'backups';
        $disk->makeDirectory($dir);

        $filename = 'sqlite-'.now()->format('Ymd_His').'.sqlite';
        $path = $dir.'/'.$filename;
        $disk->put($path, $content);
    }

    protected function prune(): void
    {
        $retention = (int) $this->option('retention');
        $cutoff = now()->subDays($retention);

        $disk = Storage::disk('local');
        $files = collect($disk->files('backups'));

        $files->each(function ($file) use ($disk, $cutoff) {
            $ts = $disk->lastModified($file);
            if ($ts && $cutoff->isAfter(\Carbon\Carbon::createFromTimestamp($ts))) {
                $disk->delete($file);
            }
        });
    }
}

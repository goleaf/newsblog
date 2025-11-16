<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_database_creates_file_for_sqlite(): void
    {
        // Point sqlite to a file
        $dbFile = database_path('database.sqlite');
        if (! file_exists(dirname($dbFile))) {
            mkdir(dirname($dbFile), 0777, true);
        }
        file_put_contents($dbFile, 'test');
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', $dbFile);

        Storage::fake('local');

        $exit = Artisan::call('backup:database --retention=1');
        $this->assertSame(0, $exit);

        $files = Storage::disk('local')->files('backups');
        $this->assertNotEmpty($files);
    }
}

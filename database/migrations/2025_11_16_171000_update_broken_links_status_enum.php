<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Best-effort: widen enum for MySQL/MariaDB to include ok/broken/ignored
        if (in_array($driver, ['mysql', 'mariadb'])) {
            try {
                DB::statement("ALTER TABLE broken_links MODIFY COLUMN status ENUM('ok','broken','ignored') NOT NULL DEFAULT 'broken'");
            } catch (\Throwable $e) {
                // ignore if not applicable
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            try {
                DB::statement("ALTER TABLE broken_links MODIFY COLUMN status ENUM('pending','fixed','ignored') NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {
                // ignore if not applicable
            }
        }
    }
};

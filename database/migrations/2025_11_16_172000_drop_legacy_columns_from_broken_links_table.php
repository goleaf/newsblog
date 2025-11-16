<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            // Skip dropping legacy columns on SQLite to avoid rebuild complexities.
            return;
        }

        Schema::table('broken_links', function (Blueprint $table) {
            if (Schema::hasColumn('broken_links', 'status_code')) {
                $table->dropColumn('status_code');
            }
            if (Schema::hasColumn('broken_links', 'last_checked_at')) {
                $table->dropColumn('last_checked_at');
            }
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('broken_links', function (Blueprint $table) {
            if (! Schema::hasColumn('broken_links', 'status_code')) {
                $table->integer('status_code')->nullable();
            }
            if (! Schema::hasColumn('broken_links', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable();
            }
        });
    }
};

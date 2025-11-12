<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add full-text index on posts.title for better search performance
        // This complements the existing regular index for fuzzy search operations
        // Note: SQLite doesn't support FULLTEXT indexes, so we only add it for MySQL/MariaDB
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE posts ADD FULLTEXT INDEX posts_title_fulltext (title)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text index
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE posts DROP INDEX posts_title_fulltext');
        }
    }
};

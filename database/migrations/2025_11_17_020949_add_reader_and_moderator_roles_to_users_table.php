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
        // For MySQL, we need to alter the enum column to include new values
        // For other databases (SQLite, PostgreSQL), the role is stored as string
        // so no schema change is needed - the enum is enforced at the application level

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('reader', 'author', 'moderator', 'admin', 'editor', 'user') NOT NULL DEFAULT 'user'");
        }

        // No schema change needed for SQLite/PostgreSQL as they store enums as strings
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum values for MySQL
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // First, update any users with new roles to 'user'
            DB::table('users')
                ->whereIn('role', ['reader', 'moderator'])
                ->update(['role' => 'user']);

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'editor', 'author', 'user') NOT NULL DEFAULT 'user'");
        }
    }
};

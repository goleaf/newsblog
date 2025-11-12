<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we'll just use a string column instead of enum
        // The application will handle validation
        DB::statement("UPDATE newsletters SET status = 'pending' WHERE status NOT IN ('subscribed', 'unsubscribed')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse as we're just updating data
        DB::statement("UPDATE newsletters SET status = 'subscribed' WHERE status = 'pending'");
    }
};

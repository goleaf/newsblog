<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->foreignId('collection_id')->nullable()->after('post_id')->constrained('bookmark_collections')->onDelete('set null');
            $table->integer('order')->default(0)->after('collection_id');

            $table->index(['collection_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite in testing may not support dropping columns; skip in that case
        $driver = config('database.default');
        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropColumn(['collection_id', 'order']);
        });
    }
};

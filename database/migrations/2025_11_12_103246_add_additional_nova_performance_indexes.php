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
        // Categories table - add missing index for parent_id
        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id', 'categories_parent_id_index');
        });

        // Posts table - add missing created_at index
        Schema::table('posts', function (Blueprint $table) {
            $table->index('created_at', 'posts_created_at_index');
        });

        // Pages table - add missing index for display_order
        Schema::table('pages', function (Blueprint $table) {
            $table->index('display_order', 'pages_display_order_index');
        });

        // Newsletters table - add missing index for created_at
        Schema::table('newsletters', function (Blueprint $table) {
            $table->index('created_at', 'newsletters_created_at_index');
        });

        // Activity logs table - add missing indexes for event and created_at
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index('event', 'activity_logs_event_index');
            $table->index('created_at', 'activity_logs_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_parent_id_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_created_at_index');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_display_order_index');
        });

        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropIndex('newsletters_created_at_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_event_index');
            $table->dropIndex('activity_logs_created_at_index');
        });
    }
};

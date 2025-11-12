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
        Schema::table('posts', function (Blueprint $table) {
            // Add index on title for faster text searches
            $table->index('title', 'posts_title_index');

            // Add composite index on (status, published_at) for filtering published posts
            $table->index(['status', 'published_at'], 'posts_status_published_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_title_index');
            $table->dropIndex('posts_status_published_at_index');
        });
    }
};

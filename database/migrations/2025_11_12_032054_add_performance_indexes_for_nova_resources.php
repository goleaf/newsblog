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
        Schema::table('tags', function (Blueprint $table) {
            // Add index on name for faster tag searches
            try {
                $table->index('name', 'tags_name_index');
            } catch (\Exception $e) {
                // Index may already exist, ignore
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            // Add index on name for faster category searches
            try {
                $table->index('name', 'categories_name_index');
            } catch (\Exception $e) {
                // Index may already exist, ignore
            }
        });

        Schema::table('media_library', function (Blueprint $table) {
            // Add index on file_name for faster media searches
            try {
                $table->index('file_name', 'media_library_file_name_index');
            } catch (\Exception $e) {
                // Index may already exist, ignore
            }
            // Add index on created_at for faster sorting
            try {
                $table->index('created_at', 'media_library_created_at_index');
            } catch (\Exception $e) {
                // Index may already exist, ignore
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            try {
                $table->dropIndex('tags_name_index');
            } catch (\Exception $e) {
                // Index may not exist, ignore
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->dropIndex('categories_name_index');
            } catch (\Exception $e) {
                // Index may not exist, ignore
            }
        });

        Schema::table('media_library', function (Blueprint $table) {
            try {
                $table->dropIndex('media_library_file_name_index');
            } catch (\Exception $e) {
                // Index may not exist, ignore
            }
            try {
                $table->dropIndex('media_library_created_at_index');
            } catch (\Exception $e) {
                // Index may not exist, ignore
            }
        });
    }
};

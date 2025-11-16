<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            if (!Schema::hasColumn('bookmarks', 'reader_token')) {
                $table->string('reader_token', 64)->nullable()->after('id');
                $table->index('reader_token');
            }
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $exists = collect(Schema::getIndexes('bookmarks'))
                ->pluck('name')
                ->contains('bookmarks_reader_post_unique');

            if (! $exists) {
                $table->unique(['reader_token', 'post_id'], 'bookmarks_reader_post_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            if (Schema::hasColumn('bookmarks', 'reader_token')) {
                $table->dropUnique('bookmarks_reader_post_unique');
                $table->dropIndex(['reader_token']);
                $table->dropColumn('reader_token');
            }
        });
    }
};




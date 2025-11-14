<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_tag', function (Blueprint $table) {
            $table->index('tag_id');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::table('post_tag', function (Blueprint $table) {
            $table->dropIndex(['tag_id']);
            $table->dropIndex(['post_id']);
        });
    }
};

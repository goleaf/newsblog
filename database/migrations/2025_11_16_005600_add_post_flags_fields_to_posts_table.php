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
            $table->boolean('is_breaking')->default(false)->after('is_trending');
            $table->boolean('is_sponsored')->default(false)->after('is_breaking');
            $table->boolean('is_editors_pick')->default(false)->after('is_sponsored');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn([
                'is_breaking',
                'is_sponsored',
                'is_editors_pick',
            ]);
        });
    }
};

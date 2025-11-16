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
            $table->unsignedInteger('editors_pick_order')->nullable()->after('is_editors_pick');
            $table->index(['is_editors_pick', 'editors_pick_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['is_editors_pick', 'editors_pick_order']);
            $table->dropColumn('editors_pick_order');
        });
    }
};



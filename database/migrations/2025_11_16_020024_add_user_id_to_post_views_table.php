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
        Schema::table('post_views', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('post_id')->constrained()->onDelete('cascade');
            $table->index(['user_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_views', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'viewed_at']);
            $table->dropColumn('user_id');
        });
    }
};

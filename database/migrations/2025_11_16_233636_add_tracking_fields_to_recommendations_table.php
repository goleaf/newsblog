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
        Schema::table('recommendations', function (Blueprint $table) {
            $table->boolean('clicked')->default(false)->after('score');
            $table->timestamp('generated_at')->nullable()->after('clicked');
            $table->timestamp('clicked_at')->nullable()->after('generated_at');
            $table->integer('impressions')->default(0)->after('clicked_at');

            $table->index('clicked');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropIndex(['clicked']);
            $table->dropIndex(['generated_at']);
            $table->dropColumn(['clicked', 'generated_at', 'clicked_at', 'impressions']);
        });
    }
};

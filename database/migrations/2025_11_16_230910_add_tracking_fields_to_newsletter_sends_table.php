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
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->timestamp('opened_at')->nullable()->after('sent_at');
            $table->timestamp('clicked_at')->nullable()->after('opened_at');
            $table->integer('click_count')->default(0)->after('clicked_at');
            $table->json('clicked_links')->nullable()->after('click_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropColumn(['opened_at', 'clicked_at', 'click_count', 'clicked_links']);
        });
    }
};

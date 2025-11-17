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
        Schema::table('newsletters', function (Blueprint $table) {
            $table->string('verification_token')->nullable()->after('token');
            $table->timestamp('verification_token_expires_at')->nullable()->after('verification_token');
            $table->string('unsubscribe_token')->nullable()->after('verification_token_expires_at');
            $table->timestamp('unsubscribed_at')->nullable()->after('unsubscribe_token');

            $table->index('verification_token');
            $table->index('unsubscribe_token');
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
        Schema::table('newsletters', function (Blueprint $table) {
            $table->dropIndex(['verification_token']);
            $table->dropIndex(['unsubscribe_token']);
            $table->dropColumn([
                'verification_token',
                'verification_token_expires_at',
                'unsubscribe_token',
                'unsubscribed_at',
            ]);
        });
    }
};

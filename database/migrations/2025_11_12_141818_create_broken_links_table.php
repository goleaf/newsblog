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
        Schema::create('broken_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->integer('status_code')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('last_checked_at');
            $table->enum('status', ['pending', 'fixed', 'ignored'])->default('pending');
            $table->timestamps();

            $table->index(['post_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broken_links');
    }
};

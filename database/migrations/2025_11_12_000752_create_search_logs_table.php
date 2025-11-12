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
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 500);
            $table->integer('result_count')->default(0);
            $table->float('execution_time')->nullable();
            $table->string('search_type', 50)->default('posts');
            $table->boolean('fuzzy_enabled')->default(true);
            $table->integer('threshold')->nullable();
            $table->json('filters')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            
            $table->index(['query', 'created_at']);
            $table->index('result_count');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};

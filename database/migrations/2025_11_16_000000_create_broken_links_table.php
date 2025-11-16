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
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('url', 2048);
            $table->string('status', 32)->default('ok')->index(); // ok, broken, ignored
            $table->timestamp('checked_at')->nullable()->index();
            $table->smallInteger('response_code')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'url']);
            $table->index(['post_id', 'status']);
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



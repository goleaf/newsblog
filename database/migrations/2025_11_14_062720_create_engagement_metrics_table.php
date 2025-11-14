<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Requirement: 16.3
     */
    public function up(): void
    {
        Schema::create('engagement_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Time tracking
            $table->integer('time_on_page')->default(0)->comment('Time spent on page in seconds');
            $table->integer('scroll_depth')->default(0)->comment('Maximum scroll depth percentage (0-100)');
            
            // Interaction tracking
            $table->boolean('clicked_bookmark')->default(false);
            $table->boolean('clicked_share')->default(false);
            $table->boolean('clicked_reaction')->default(false);
            $table->boolean('clicked_comment')->default(false);
            $table->boolean('clicked_related_post')->default(false);
            
            // Metadata
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referer')->nullable();
            
            $table->timestamps();
            
            // Indexes for analytics queries
            $table->index(['post_id', 'created_at']);
            $table->index(['session_id', 'post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('engagement_metrics');
    }
};

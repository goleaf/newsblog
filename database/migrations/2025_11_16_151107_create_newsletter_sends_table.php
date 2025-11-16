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
        Schema::create('newsletter_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('newsletters')->onDelete('cascade');
            $table->string('batch_id')->nullable(); // group sends by campaign/batch
            $table->string('subject');
            $table->longText('content');
            $table->string('status')->default('queued'); // queued, sending, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['subscriber_id', 'status']);
            $table->index('batch_id');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletter_sends');
    }
};

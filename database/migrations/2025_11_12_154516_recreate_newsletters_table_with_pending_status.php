<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backup existing data
        $newsletters = DB::table('newsletters')->get();

        // Drop and recreate the table
        Schema::dropIfExists('newsletters');

        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('status')->default('pending'); // Changed from enum to string
            $table->timestamp('verified_at')->nullable();
            $table->string('token')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->string('unsubscribe_token')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
            $table->index('verification_token');
            $table->index('unsubscribe_token');
        });

        // Restore data
        foreach ($newsletters as $newsletter) {
            DB::table('newsletters')->insert((array) $newsletter);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backup existing data
        $newsletters = DB::table('newsletters')->get();

        // Drop and recreate the table with old schema
        Schema::dropIfExists('newsletters');

        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->enum('status', ['subscribed', 'unsubscribed'])->default('subscribed');
            $table->timestamp('verified_at')->nullable();
            $table->string('token')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });

        // Restore data (only subscribed and unsubscribed)
        foreach ($newsletters as $newsletter) {
            if (in_array($newsletter->status, ['subscribed', 'unsubscribed'])) {
                DB::table('newsletters')->insert([
                    'id' => $newsletter->id,
                    'email' => $newsletter->email,
                    'status' => $newsletter->status,
                    'verified_at' => $newsletter->verified_at,
                    'token' => $newsletter->token,
                    'created_at' => $newsletter->created_at,
                    'updated_at' => $newsletter->updated_at,
                ]);
            }
        }
    }
};

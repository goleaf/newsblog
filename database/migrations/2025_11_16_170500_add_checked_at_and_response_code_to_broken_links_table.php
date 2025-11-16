<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broken_links', function (Blueprint $table) {
            if (! Schema::hasColumn('broken_links', 'checked_at')) {
                $table->timestamp('checked_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('broken_links', 'response_code')) {
                $table->integer('response_code')->nullable()->after('checked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('broken_links', function (Blueprint $table) {
            if (Schema::hasColumn('broken_links', 'response_code')) {
                $table->dropColumn('response_code');
            }
            if (Schema::hasColumn('broken_links', 'checked_at')) {
                $table->dropColumn('checked_at');
            }
        });
    }
};

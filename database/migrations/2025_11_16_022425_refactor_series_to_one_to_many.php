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
        // 1) Add new columns to posts
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->constrained()->nullOnDelete()->after('category_id');
            $table->integer('order_in_series')->default(0)->after('series_id');
        });

        // 2) Migrate existing data from post_series pivot to posts (choose the lowest order entry if multiple)
        if (Schema::hasTable('post_series')) {
            $pivotRows = DB::table('post_series')
                ->select('post_id', DB::raw('MIN(series_id) as series_id'), DB::raw('MIN(`order`) as order_in_series'))
                ->groupBy('post_id')
                ->get();

            foreach ($pivotRows as $row) {
                DB::table('posts')
                    ->where('id', $row->post_id)
                    ->update([
                        'series_id' => $row->series_id,
                        'order_in_series' => $row->order_in_series ?? 0,
                    ]);
            }

            // 3) Drop pivot table
            Schema::dropIfExists('post_series');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1) Recreate pivot table
        if (! Schema::hasTable('post_series')) {
            Schema::create('post_series', function (Blueprint $table) {
                $table->id();
                $table->foreignId('post_id')->constrained()->onDelete('cascade');
                $table->foreignId('series_id')->constrained()->onDelete('cascade');
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->unique(['post_id', 'series_id']);
            });
        }

        // 2) Migrate back from posts to pivot
        $rows = DB::table('posts')
            ->whereNotNull('series_id')
            ->select('id as post_id', 'series_id', 'order_in_series')
            ->get();

        foreach ($rows as $row) {
            // Use insert ignore-like behavior
            $exists = DB::table('post_series')
                ->where('post_id', $row->post_id)
                ->where('series_id', $row->series_id)
                ->exists();
            if (! $exists) {
                DB::table('post_series')->insert([
                    'post_id' => $row->post_id,
                    'series_id' => $row->series_id,
                    'order' => $row->order_in_series ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3) Drop columns from posts
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'order_in_series')) {
                $table->dropColumn('order_in_series');
            }
            if (Schema::hasColumn('posts', 'series_id')) {
                $table->dropConstrainedForeignId('series_id');
            }
        });
    }
};

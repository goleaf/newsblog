<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SearchLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::limit(5)->get();
        $posts = \App\Models\Post::published()->limit(20)->get();

        if ($users->isEmpty() || $posts->isEmpty()) {
            $this->command->warn('No users or posts found. Skipping search log seeding.');

            return;
        }

        // Create search logs with varied scenarios
        \App\Models\SearchLog::factory()
            ->count(50)
            ->create([
                'user_id' => fn () => $users->random()->id,
            ]);

        // Create some searches with no results
        \App\Models\SearchLog::factory()
            ->noResults()
            ->count(10)
            ->create([
                'user_id' => fn () => $users->random()->id,
            ]);

        // Create some slow searches
        \App\Models\SearchLog::factory()
            ->slow()
            ->count(5)
            ->create([
                'user_id' => fn () => $users->random()->id,
            ]);

        // Create search clicks for some logs
        $searchLogs = \App\Models\SearchLog::where('result_count', '>', 0)
            ->limit(20)
            ->get();

        foreach ($searchLogs as $log) {
            $clickCount = min($log->result_count, fake()->numberBetween(1, 3));

            for ($i = 1; $i <= $clickCount; $i++) {
                \App\Models\SearchClick::factory()->create([
                    'search_log_id' => $log->id,
                    'post_id' => $posts->random()->id,
                    'position' => $i,
                ]);
            }
        }

        $this->command->info('Search logs and clicks seeded successfully.');
    }
}

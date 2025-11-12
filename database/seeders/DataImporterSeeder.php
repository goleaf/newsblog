<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DataImporterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dataFile = database_path('data.txt');

        if (! File::exists($dataFile)) {
            $this->command->error("Data file not found: {$dataFile}");

            return;
        }

        $user = User::first();
        if (! $user) {
            $this->command->error('No user found. Please seed users first.');

            return;
        }

        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please seed categories first.');

            return;
        }

        $this->command->info('Importing articles from data.txt...');

        $imported = 0;
        $skipped = 0;
        $lineNumber = 0;
        $maxImports = 5000;

        foreach (File::lines($dataFile) as $title) {
            $lineNumber++;
            $title = trim($title);

            if (empty($title)) {
                $skipped++;

                continue;
            }

            if ($imported >= $maxImports) {
                $this->command->info("Reached maximum import limit of {$maxImports} articles.");
                break;
            }

            $slug = Str::slug($title);
            $counter = 1;
            $originalSlug = $slug;
            $maxAttempts = 100;

            try {
                while ($counter <= $maxAttempts) {
                    try {
                        Post::create([
                            'user_id' => $user->id,
                            'category_id' => $categories->random()->id,
                            'title' => $title,
                            'slug' => $slug,
                            'excerpt' => Str::limit($title, 150),
                            'content' => '<p>'.e($title).'</p>',
                            'status' => 'published',
                            'is_featured' => false,
                            'is_trending' => false,
                            'view_count' => 0,
                            'published_at' => now()->subDays(rand(0, 365)),
                            'reading_time' => 1,
                            'meta_title' => $title,
                            'meta_description' => Str::limit($title, 160),
                        ]);

                        $imported++;
                        break;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'UNIQUE constraint')) {
                            $slug = $originalSlug.'-'.$counter;
                            $counter++;
                        } else {
                            throw $e;
                        }
                    }
                }

                if ($counter > $maxAttempts) {
                    $this->command->warn("Could not create unique slug for line {$lineNumber}: {$title}");
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->command->newLine();
                $this->command->warn("Error importing line {$lineNumber}: {$e->getMessage()}");
                $skipped++;
            }

            if ($imported % 1000 === 0) {
                $this->command->info("Imported {$imported} articles so far...");
            }
        }

        $this->command->newLine();
        $this->command->info('Import completed!');
        $this->command->info("Imported: {$imported} articles");
        if ($skipped > 0) {
            $this->command->warn("Skipped: {$skipped} articles");
        }
    }
}

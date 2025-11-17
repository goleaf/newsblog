<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(6, true),
            'status' => 'published',
            'is_featured' => false,
            'is_trending' => false,
            'is_breaking' => false,
            'is_sponsored' => false,
            'is_editors_pick' => false,
            'view_count' => fake()->numberBetween(0, 1000),
            'published_at' => now()->subDays(fake()->numberBetween(0, 7)),
            'reading_time' => fake()->numberBetween(1, 12),
            'meta_title' => $title,
            'meta_description' => fake()->sentence(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(0, 7)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(10, true),
            'status' => 'published',
            'is_featured' => false,
            'is_trending' => false,
            'is_breaking' => false,
            'is_sponsored' => false,
            'is_editors_pick' => false,
            'view_count' => fake()->numberBetween(0, 1000),
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'reading_time' => fake()->numberBetween(1, 15),
            'meta_title' => $title,
            'meta_description' => fake()->sentence(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trending' => true,
        ]);
    }

    public function breaking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_breaking' => true,
        ]);
    }
}

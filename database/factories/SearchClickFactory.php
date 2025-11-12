<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchClick>
 */
class SearchClickFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'search_log_id' => \App\Models\SearchLog::factory(),
            'post_id' => \App\Models\Post::factory(),
            'position' => fake()->numberBetween(1, 10),
        ];
    }
}

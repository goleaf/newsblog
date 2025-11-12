<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchLog>
 */
class SearchLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $queries = [
            'Laravel',
            'PHP',
            'JavaScript',
            'React',
            'Vue',
            'Tailwind CSS',
            'Docker',
            'API development',
            'database optimization',
            'authentication',
            'testing',
            'deployment',
        ];

        return [
            'query' => fake()->randomElement($queries),
            'result_count' => fake()->numberBetween(0, 50),
            'execution_time' => fake()->randomFloat(2, 0.01, 2.5),
            'search_type' => fake()->randomElement(['posts', 'tags', 'categories']),
            'fuzzy_enabled' => fake()->boolean(80),
            'threshold' => fake()->optional(0.7)->numberBetween(50, 90),
            'filters' => fake()->optional(0.3)->passthrough([
                'category' => fake()->word(),
                'author' => fake()->name(),
            ]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'user_id' => fake()->optional(0.6)->numberBetween(1, 10),
        ];
    }

    public function noResults(): static
    {
        return $this->state(fn (array $attributes) => [
            'result_count' => 0,
        ]);
    }

    public function withUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => \App\Models\User::factory(),
        ]);
    }

    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_time' => fake()->randomFloat(2, 1.5, 5.0),
        ]);
    }
}

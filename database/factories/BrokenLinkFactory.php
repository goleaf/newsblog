<?php

namespace Database\Factories;

use App\Models\BrokenLink;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BrokenLink>
 */
class BrokenLinkFactory extends Factory
{
    protected $model = BrokenLink::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'url' => fake()->url(),
            'status' => fake()->randomElement(['ok', 'broken', 'ignored']),
            'response_code' => fake()->optional()->numberBetween(200, 599),
            'checked_at' => now(),
        ];
    }

    public function broken(): static
    {
        return $this->state(fn () => [
            'status' => 'broken',
            'response_code' => 404,
        ]);
    }

    public function ok(): static
    {
        return $this->state(fn () => [
            'status' => 'ok',
            'response_code' => 200,
        ]);
    }

    public function ignored(): static
    {
        return $this->state(fn () => [
            'status' => 'ignored',
        ]);
    }
}

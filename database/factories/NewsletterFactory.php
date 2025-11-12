<?php

namespace Database\Factories;

use App\Models\Newsletter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\Newsletter
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class NewsletterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Newsletter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'status' => fake()->randomElement(['subscribed', 'unsubscribed']),
            'verified_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'token' => \Illuminate\Support\Str::random(32),
        ];
    }
}

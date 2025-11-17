<?php

namespace Database\Factories;

use App\Models\SocialShare;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\SocialShare
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class SocialShareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = SocialShare::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => \App\Models\Post::factory(),
            'user_id' => $this->faker->boolean(70) ? \App\Models\User::factory() : null,
            'provider' => $this->faker->randomElement(['twitter', 'facebook', 'linkedin', 'reddit', 'hackernews']),
            'shared_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}

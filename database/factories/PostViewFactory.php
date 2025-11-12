<?php

namespace Database\Factories;

use App\Models\PostView;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\PostView
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class PostViewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = PostView::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => \App\Models\Post::factory(),
            'session_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referer' => fake()->optional()->url(),
            'viewed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}

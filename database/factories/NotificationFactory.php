<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\Notification
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            Notification::TYPE_COMMENT_REPLY,
            Notification::TYPE_POST_PUBLISHED,
            Notification::TYPE_COMMENT_APPROVED,
            Notification::TYPE_SERIES_UPDATED,
        ];

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement($types),
            'title' => fake()->sentence(),
            'message' => fake()->sentence(),
            'action_url' => fake()->url(),
            'icon' => fake()->randomElement(['bell', 'chat', 'check', 'book']),
            'data' => null,
            'read_at' => null,
        ];
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ActivityLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'log_name' => 'Nova',
            'description' => fake()->sentence(),
            'subject_type' => Post::class,
            'subject_id' => 1,
            'event' => fake()->randomElement(['created', 'updated', 'deleted']),
            'causer_type' => User::class,
            'causer_id' => 1,
            'properties' => [],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}

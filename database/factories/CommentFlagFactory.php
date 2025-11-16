<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommentFlag>
 */
class CommentFlagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(CommentFlag::REASONS),
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'status' => 'open',
        ];
    }
}

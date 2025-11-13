<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostRevision>
 */
class PostRevisionFactory extends Factory
{
    protected $model = PostRevision::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'excerpt' => fake()->sentences(2, true),
            'meta_data' => [
                'slug' => fake()->slug(),
                'status' => 'published',
                'featured_image' => null,
                'meta_title' => fake()->sentence(),
                'meta_description' => fake()->sentence(),
                'meta_keywords' => 'test,keywords',
            ],
            'revision_note' => fake()->sentence(),
        ];
    }
}

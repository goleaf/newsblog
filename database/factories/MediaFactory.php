<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->word().'.'.fake()->fileExtension();

        return [
            'user_id' => User::factory(),
            'file_name' => $fileName,
            'file_path' => 'media/'.$fileName,
            'file_type' => fake()->randomElement(['image', 'document', 'video']),
            'file_size' => fake()->numberBetween(1000, 10000000),
            'mime_type' => fake()->mimeType(),
            'alt_text' => fake()->sentence(),
            'title' => fake()->sentence(),
            'caption' => fake()->paragraph(),
            'metadata' => [],
        ];
    }
}

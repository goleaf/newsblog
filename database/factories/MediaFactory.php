<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $filename = $this->faker->unique()->lexify('image_????????').'.jpg';

        return [
            'user_id' => User::query()->inRandomOrder()->value('id') ?? User::factory(),
            'file_name' => $filename,
            'file_path' => 'media/'.$filename,
            'file_type' => 'image',
            'file_size' => $this->faker->numberBetween(10_000, 5_000_000),
            'mime_type' => 'image/jpeg',
            'alt_text' => $this->faker->optional()->sentence(4),
            'title' => $this->faker->optional()->sentence(3),
            'caption' => $this->faker->optional()->sentence(8),
            'metadata' => [
                'variants' => [
                    'thumbnail' => [
                        'path' => 'media/variants/'.str_replace('.jpg', '_thumbnail.jpg', $filename),
                        'width' => 320,
                        'height' => 240,
                    ],
                ],
            ],
        ];
    }
}

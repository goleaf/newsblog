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
            'filename' => $filename,
            'path' => 'public/media/'.$filename,
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10_000, 5_000_000),
            'alt_text' => $this->faker->sentence(4),
            'caption' => $this->faker->optional()->sentence(8),
            'metadata' => [
                'width' => $this->faker->numberBetween(320, 3840),
                'height' => $this->faker->numberBetween(240, 2160),
            ],
            'user_id' => User::query()->inRandomOrder()->value('id'),
        ];
    }
}

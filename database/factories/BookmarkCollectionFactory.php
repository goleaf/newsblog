<?php

namespace Database\Factories;

use App\Models\BookmarkCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\BookmarkCollection
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class BookmarkCollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = BookmarkCollection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'is_public' => fake()->boolean(20),
            'order' => fake()->numberBetween(0, 100),
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }
}

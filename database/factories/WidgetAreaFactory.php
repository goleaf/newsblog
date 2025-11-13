<?php

namespace Database\Factories;

use App\Models\WidgetArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\WidgetArea
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class WidgetAreaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = WidgetArea::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }
}

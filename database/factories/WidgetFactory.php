<?php

namespace Database\Factories;

use App\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\Widget
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class WidgetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Widget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'widget_area_id' => \App\Models\WidgetArea::factory(),
            'type' => fake()->randomElement(['recent-posts', 'popular-posts', 'categories', 'tags-cloud', 'newsletter', 'search', 'custom-html']),
            'title' => fake()->words(3, true),
            'settings' => [
                'count' => fake()->numberBetween(3, 10),
                'show_count' => fake()->boolean(),
                'content' => fake()->paragraph(),
            ],
            'order' => fake()->numberBetween(1, 10),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function customHtml(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'custom-html',
            'settings' => [
                'content' => '<p>'.fake()->paragraph().'</p>',
            ],
        ]);
    }
}

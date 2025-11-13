<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\Page
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'content' => fake()->paragraphs(5, true),
            'meta_title' => $title,
            'meta_description' => fake()->sentence(10),
            'status' => fake()->randomElement(['draft', 'published']),
            'template' => fake()->randomElement(['default', 'full-width', 'contact', 'about']),
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }
}

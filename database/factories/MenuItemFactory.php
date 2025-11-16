<?php

namespace Database\Factories;

use App\Enums\MenuItemType;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'parent_id' => null,
            'type' => $this->faker->randomElement([
                MenuItemType::Link->value,
                MenuItemType::Page->value,
                MenuItemType::Category->value,
                MenuItemType::Tag->value,
            ]),
            'title' => $this->faker->sentence(3),
            'url' => $this->faker->optional()->url(),
            'reference_id' => null,
            'order' => $this->faker->numberBetween(0, 100),
            'css_class' => $this->faker->optional()->word(),
            'target' => $this->faker->optional()->randomElement(['_self', '_blank']),
        ];
    }
}



<?php

namespace Database\Factories;

use App\Enums\MenuLocation;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'location' => $this->faker->randomElement([
                MenuLocation::Header,
                MenuLocation::Footer,
                MenuLocation::Mobile,
            ])->value,
        ];
    }
}



<?php

namespace Tests\Feature;

use App\Enums\MenuItemType;
use App\Enums\MenuLocation;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_menu_with_location(): void
    {
        $menu = Menu::factory()->create([
            'name' => 'Main Navigation',
            'location' => MenuLocation::Header->value,
        ]);

        $this->assertDatabaseHas('menus', [
            'id' => $menu->id,
            'name' => 'Main Navigation',
            'location' => MenuLocation::Header->value,
        ]);
    }

    public function test_can_create_nested_menu_items_and_ordering(): void
    {
        $menu = Menu::factory()->create(['location' => MenuLocation::Header->value]);
        $parent = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'type' => MenuItemType::Link->value,
            'title' => 'Parent',
            'url' => '/parent',
            'order' => 1,
        ]);

        $childA = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child A',
            'order' => 2,
        ]);

        $childB = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'title' => 'Child B',
            'order' => 1,
        ]);

        $this->assertDatabaseHas('menu_items', ['id' => $childA->id, 'parent_id' => $parent->id]);
        $this->assertDatabaseHas('menu_items', ['id' => $childB->id, 'parent_id' => $parent->id]);

        $children = $parent->children()->pluck('title')->all();
        $this->assertSame(['Child B', 'Child A'], $children, 'Children should be ordered by order ascending');
    }

    public function test_deleting_parent_cascades_to_children(): void
    {
        $menu = Menu::factory()->create();
        $parent = MenuItem::factory()->create(['menu_id' => $menu->id]);
        $child = MenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => $parent->id]);

        $parent->delete();

        $this->assertDatabaseMissing('menu_items', ['id' => $parent->id]);
        $this->assertDatabaseMissing('menu_items', ['id' => $child->id]);
    }
}

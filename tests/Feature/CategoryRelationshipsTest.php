<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_have_parent_and_children(): void
    {
        $parent = Category::factory()->create([
            'name' => 'Parent',
            'status' => 'active',
            'parent_id' => null,
        ]);

        $childA = Category::factory()->create([
            'name' => 'Child A',
            'parent_id' => $parent->id,
        ]);

        $childB = Category::factory()->create([
            'name' => 'Child B',
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue($childA->parent->is($parent));
        $this->assertTrue($childB->parent->is($parent));
        $this->assertCount(2, $parent->children);
        $this->assertEqualsCanonicalizing(
            ['Child A', 'Child B'],
            $parent->children->pluck('name')->all()
        );
    }

    public function test_active_scope_returns_only_active_categories(): void
    {
        Category::factory()->create(['status' => 'active']);
        Category::factory()->create(['status' => 'inactive']);

        $activeCategories = Category::active()->get();

        $this->assertCount(1, $activeCategories);
        $this->assertEquals('active', $activeCategories->first()->status);
    }

    public function test_parents_and_parent_scopes_return_only_root_categories(): void
    {
        $root = Category::factory()->create(['parent_id' => null]);
        Category::factory()->create(['parent_id' => $root->id]);

        $parents = Category::parents()->get();
        $parentAlias = Category::query()->parent()->get();

        $this->assertCount(1, $parents);
        $this->assertTrue($parents->first()->is($root));

        $this->assertCount(1, $parentAlias);
        $this->assertTrue($parentAlias->first()->is($root));
    }

    public function test_ordered_scope_orders_by_display_order_then_name(): void
    {
        $categoryB = Category::factory()->create([
            'name' => 'B name',
            'display_order' => 2,
        ]);
        $categoryA = Category::factory()->create([
            'name' => 'A name',
            'display_order' => 1,
        ]);
        $categoryC = Category::factory()->create([
            'name' => 'C name',
            'display_order' => 1,
        ]);

        $ordered = Category::ordered()->get();

        $this->assertEquals(
            [$categoryA->id, $categoryC->id, $categoryB->id],
            $ordered->pluck('id')->all()
        );
    }

    public function test_get_posts_count_uses_published_scope_on_posts(): void
    {
        $category = Category::factory()->create();

        Post::factory()->count(2)->published()->create([
            'category_id' => $category->id,
        ]);

        Post::factory()->count(1)->draft()->create([
            'category_id' => $category->id,
        ]);

        $this->assertSame(2, $category->getPostsCount());
    }

    public function test_category_hierarchical_structure_allows_multiple_levels(): void
    {
        $root = Category::factory()->create(['parent_id' => null, 'name' => 'Root']);
        $level1 = Category::factory()->create(['parent_id' => $root->id, 'name' => 'Level 1']);
        $level2 = Category::factory()->create(['parent_id' => $level1->id, 'name' => 'Level 2']);

        $this->assertNull($root->parent_id);
        $this->assertEquals($root->id, $level1->parent_id);
        $this->assertEquals($level1->id, $level2->parent_id);

        $this->assertTrue($level2->parent->is($level1));
        $this->assertTrue($level1->parent->is($root));
        $this->assertCount(1, $root->children);
        $this->assertCount(1, $level1->children);
    }

    public function test_category_children_are_ordered_by_display_order(): void
    {
        $parent = Category::factory()->create(['parent_id' => null]);

        $child3 = Category::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Third',
            'display_order' => 3,
        ]);
        $child1 = Category::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'First',
            'display_order' => 1,
        ]);
        $child2 = Category::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'Second',
            'display_order' => 2,
        ]);

        $orderedChildren = $parent->children()->get();

        $this->assertEquals(
            [$child1->id, $child2->id, $child3->id],
            $orderedChildren->pluck('id')->all()
        );
    }
}

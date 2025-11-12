<?php

namespace Tests\Feature\Nova;

use App\Models\Category;
use App\Models\User;
use App\Nova\Category as CategoryResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\Category::class, CategoryResource::$model);
    }

    public function test_category_resource_has_correct_title(): void
    {
        $this->assertEquals('name', CategoryResource::$title);
    }

    public function test_category_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'name', 'description'];
        $this->assertEquals($expected, CategoryResource::$search);
    }

    public function test_admin_can_view_any_categories(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/categories', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(CategoryResource::authorizedToViewAny($request));
    }

    public function test_editor_can_view_any_categories(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/categories', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(CategoryResource::authorizedToViewAny($request));
    }

    public function test_author_cannot_view_any_categories(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/categories', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(CategoryResource::authorizedToViewAny($request));
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/categories', 'POST');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(CategoryResource::authorizedToCreate($request));
    }

    public function test_editor_can_create_category(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/categories', 'POST');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(CategoryResource::authorizedToCreate($request));
    }

    public function test_category_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();
        $resource = new CategoryResource($category);

        $request = NovaRequest::create('/nova-api/categories', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(10, count($fields));
    }

    public function test_category_index_query_orders_by_display_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Category::factory()->create(['name' => 'Category C', 'display_order' => 3]);
        Category::factory()->create(['name' => 'Category A', 'display_order' => 1]);
        Category::factory()->create(['name' => 'Category B', 'display_order' => 2]);

        $request = NovaRequest::create('/nova-api/categories', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = CategoryResource::indexQuery($request, Category::query());
        $categories = $query->get();

        $this->assertEquals('Category A', $categories->first()->name);
        $this->assertEquals('Category C', $categories->last()->name);
    }
}

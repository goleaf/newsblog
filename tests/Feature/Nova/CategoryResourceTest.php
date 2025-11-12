<?php

namespace Tests\Feature\Nova;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
    }

    public function test_admin_can_view_categories_index(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    }

    public function test_editor_can_view_categories_index(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->actingAs($this->editor)
            ->getJson('/nova-api/categories');

        $response->assertOk();
    }

    public function test_author_can_view_categories_index(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->actingAs($this->author)
            ->getJson('/nova-api/categories');

        $response->assertOk();
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/categories', [
                'name' => 'Technology',
                'description' => 'Tech related posts',
                'status' => 'active',
                'display_order' => 1,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Technology',
            'slug' => 'technology',
        ]);
    }

    public function test_editor_can_create_category(): void
    {
        $response = $this->actingAs($this->editor)
            ->postJson('/nova-api/categories', [
                'name' => 'Business',
                'description' => 'Business posts',
                'status' => 'active',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Business',
        ]);
    }

    public function test_author_cannot_create_category(): void
    {
        $response = $this->actingAs($this->author)
            ->postJson('/nova-api/categories', [
                'name' => 'Technology',
                'description' => 'Tech posts',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($this->admin)
            ->putJson("/nova-api/categories/{$category->id}", [
                'name' => 'Updated Name',
                'description' => $category->description,
                'status' => $category->status,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_editor_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($this->editor)
            ->putJson("/nova-api/categories/{$category->id}", [
                'name' => 'Editor Updated',
                'description' => $category->description,
                'status' => $category->status,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Editor Updated',
        ]);
    }

    public function test_author_cannot_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($this->author)
            ->putJson("/nova-api/categories/{$category->id}", [
                'name' => 'Unauthorized Update',
                'description' => $category->description,
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/nova-api/categories?resources[]={$category->id}");

        $response->assertOk();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_editor_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->editor)
            ->deleteJson("/nova-api/categories?resources[]={$category->id}");

        $response->assertOk();
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_author_cannot_delete_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->author)
            ->deleteJson("/nova-api/categories?resources[]={$category->id}");

        $response->assertForbidden();
    }

    public function test_category_creation_requires_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/categories', [
                'description' => 'Test description',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_category_slug_is_generated_automatically(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/categories', [
                'name' => 'Test Category Name',
                'description' => 'Test description',
                'status' => 'active',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category Name',
            'slug' => 'test-category-name',
        ]);
    }

    public function test_can_create_hierarchical_categories(): void
    {
        $parent = Category::factory()->create(['name' => 'Parent Category']);

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/categories', [
                'name' => 'Child Category',
                'parent_id' => $parent->id,
                'status' => 'active',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_category_can_have_display_order(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/categories', [
                'name' => 'Ordered Category',
                'status' => 'active',
                'display_order' => 5,
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Ordered Category',
            'display_order' => 5,
        ]);
    }

    public function test_category_can_have_color_code(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/categories', [
                'name' => 'Colored Category',
                'status' => 'active',
                'color_code' => '#FF5733',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('categories', [
            'name' => 'Colored Category',
            'color_code' => '#FF5733',
        ]);
    }
}

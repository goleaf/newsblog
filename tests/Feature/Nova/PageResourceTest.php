<?php

namespace Tests\Feature\Nova;

use App\Models\Page;
use App\Models\User;
use App\Nova\Page as PageResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_resource_has_correct_model(): void
    {
        $this->assertEquals(\App\Models\Page::class, PageResource::$model);
    }

    public function test_page_resource_has_correct_title(): void
    {
        $this->assertEquals('title', PageResource::$title);
    }

    public function test_page_resource_has_correct_search_fields(): void
    {
        $expected = ['id', 'title', 'content'];
        $this->assertEquals($expected, PageResource::$search);
    }

    public function test_admin_can_view_any_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/pages', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(PageResource::authorizedToViewAny($request));
    }

    public function test_editor_can_view_any_pages(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/pages', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(PageResource::authorizedToViewAny($request));
    }

    public function test_author_cannot_view_any_pages(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/pages', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(PageResource::authorizedToViewAny($request));
    }

    public function test_admin_can_create_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/pages', 'POST');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(PageResource::authorizedToCreate($request));
    }

    public function test_editor_can_create_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $request = NovaRequest::create('/nova-api/pages', 'POST');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(PageResource::authorizedToCreate($request));
    }

    public function test_author_cannot_create_page(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $request = NovaRequest::create('/nova-api/pages', 'POST');
        $request->setUserResolver(fn () => $author);

        $this->assertFalse(PageResource::authorizedToCreate($request));
    }

    public function test_page_resource_has_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create();
        $resource = new PageResource($page);

        $request = NovaRequest::create('/nova-api/pages', 'GET');
        $request->setUserResolver(fn () => $admin);

        $fields = $resource->fields($request);

        $this->assertNotEmpty($fields);
        $this->assertGreaterThan(8, count($fields));
    }

    public function test_page_index_query_orders_by_display_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Page::factory()->create(['title' => 'Page C', 'display_order' => 3]);
        Page::factory()->create(['title' => 'Page A', 'display_order' => 1]);
        Page::factory()->create(['title' => 'Page B', 'display_order' => 2]);

        $request = NovaRequest::create('/nova-api/pages', 'GET');
        $request->setUserResolver(fn () => $admin);

        $query = PageResource::indexQuery($request, Page::query());
        $pages = $query->get();

        $this->assertEquals('Page A', $pages->first()->title);
        $this->assertEquals('Page C', $pages->last()->title);
    }

    public function test_admin_can_update_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create();
        $resource = new PageResource($page);

        $request = NovaRequest::create('/nova-api/pages/1', 'PUT');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_editor_can_update_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $page = Page::factory()->create();
        $resource = new PageResource($page);

        $request = NovaRequest::create('/nova-api/pages/1', 'PUT');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToUpdate($request));
    }

    public function test_admin_can_delete_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $page = Page::factory()->create();
        $resource = new PageResource($page);

        $request = NovaRequest::create('/nova-api/pages/1', 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue($resource->authorizedToDelete($request));
    }

    public function test_editor_can_delete_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $page = Page::factory()->create();
        $resource = new PageResource($page);

        $request = NovaRequest::create('/nova-api/pages/1', 'DELETE');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue($resource->authorizedToDelete($request));
    }
}

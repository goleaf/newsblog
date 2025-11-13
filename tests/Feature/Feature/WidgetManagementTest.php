<?php

namespace Tests\Feature\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\Widget;
use App\Models\WidgetArea;
use App\Services\WidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected WidgetArea $widgetArea;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->widgetArea = WidgetArea::create([
            'name' => 'Test Sidebar',
            'slug' => 'test-sidebar',
            'description' => 'Test sidebar area',
        ]);
    }

    public function test_admin_can_view_widget_management_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.widgets.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.widgets.index');
    }

    public function test_admin_can_create_widget(): void
    {
        $widgetData = [
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.widgets.store'), $widgetData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('widgets', [
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
        ]);
    }

    public function test_admin_can_update_widget(): void
    {
        $widget = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson(route('admin.widgets.update', $widget), [
                'title' => 'Updated Title',
                'settings' => ['count' => 10],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('widgets', [
            'id' => $widget->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_admin_can_delete_widget(): void
    {
        $widget = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.widgets.destroy', $widget));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('widgets', [
            'id' => $widget->id,
        ]);
    }

    public function test_admin_can_toggle_widget_status(): void
    {
        $widget = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.widgets.toggle', $widget));

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'active' => false]);

        $this->assertDatabaseHas('widgets', [
            'id' => $widget->id,
            'active' => false,
        ]);
    }

    public function test_admin_can_reorder_widgets(): void
    {
        $widget1 = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Widget 1',
            'settings' => [],
            'order' => 0,
            'active' => true,
        ]);

        $widget2 = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'popular-posts',
            'title' => 'Widget 2',
            'settings' => [],
            'order' => 1,
            'active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.widgets.reorder'), [
                'widgets' => [
                    ['id' => $widget1->id, 'order' => 1, 'widget_area_id' => $this->widgetArea->id],
                    ['id' => $widget2->id, 'order' => 0, 'widget_area_id' => $this->widgetArea->id],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('widgets', [
            'id' => $widget1->id,
            'order' => 1,
        ]);

        $this->assertDatabaseHas('widgets', [
            'id' => $widget2->id,
            'order' => 0,
        ]);
    }

    public function test_widget_service_renders_recent_posts_widget(): void
    {
        Post::factory()->count(3)->create(['status' => 'published']);

        $widget = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => true,
        ]);

        $widgetService = app(WidgetService::class);
        $output = $widgetService->render($widget);

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Recent Posts', $output);
    }

    public function test_widget_service_renders_popular_posts_widget(): void
    {
        Post::factory()->count(3)->create([
            'status' => 'published',
            'view_count' => 100,
        ]);

        $widget = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'popular-posts',
            'title' => 'Popular Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => true,
        ]);

        $widgetService = app(WidgetService::class);
        $output = $widgetService->render($widget);

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Popular Posts', $output);
    }

    public function test_inactive_widget_does_not_render(): void
    {
        $widget = Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => false,
        ]);

        $widgetService = app(WidgetService::class);
        $output = $widgetService->render($widget);

        $this->assertEmpty($output);
    }

    public function test_widget_area_renders_all_active_widgets(): void
    {
        Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'recent-posts',
            'title' => 'Recent Posts',
            'settings' => ['count' => 5],
            'order' => 0,
            'active' => true,
        ]);

        Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'search',
            'title' => 'Search',
            'settings' => [],
            'order' => 1,
            'active' => true,
        ]);

        Widget::create([
            'widget_area_id' => $this->widgetArea->id,
            'type' => 'newsletter',
            'title' => 'Newsletter',
            'settings' => [],
            'order' => 2,
            'active' => false,
        ]);

        $widgetService = app(WidgetService::class);
        $output = $widgetService->renderArea($this->widgetArea->slug);

        $this->assertStringContainsString('Recent Posts', $output);
        $this->assertStringContainsString('Search', $output);
        $this->assertStringNotContainsString('Newsletter', $output);
    }
}

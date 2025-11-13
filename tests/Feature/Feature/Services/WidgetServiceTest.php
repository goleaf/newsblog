<?php

namespace Tests\Feature\Feature\Services;

use App\Models\Widget;
use App\Models\WidgetArea;
use App\Services\WidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class WidgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WidgetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WidgetService::class);
    }

    public function test_renders_active_widget(): void
    {
        View::addLocation(resource_path('views'));

        $widget = Widget::factory()->create([
            'type' => 'custom-html',
            'active' => true,
            'settings' => ['content' => '<p>Test content</p>'],
        ]);

        $output = $this->service->render($widget);

        $this->assertNotEmpty($output);
    }

    public function test_does_not_render_inactive_widget(): void
    {
        $widget = Widget::factory()->create([
            'type' => 'custom-html',
            'active' => false,
            'settings' => ['content' => '<p>Test content</p>'],
        ]);

        $output = $this->service->render($widget);

        $this->assertEmpty($output);
    }

    public function test_caches_widget_output(): void
    {
        View::addLocation(resource_path('views'));

        $widget = Widget::factory()->create([
            'type' => 'custom-html',
            'active' => true,
            'settings' => ['content' => '<p>Test content</p>'],
        ]);

        // First render
        $this->service->render($widget);

        // Check cache exists
        $this->assertTrue(Cache::has("widget.{$widget->id}"));
    }

    public function test_renders_widget_area(): void
    {
        View::addLocation(resource_path('views'));

        $area = WidgetArea::factory()->create(['slug' => 'sidebar']);
        $widget = Widget::factory()->create([
            'widget_area_id' => $area->id,
            'type' => 'custom-html',
            'active' => true,
            'settings' => ['content' => '<p>Test</p>'],
        ]);

        $output = $this->service->renderArea('sidebar');

        $this->assertNotEmpty($output);
    }

    public function test_returns_empty_for_nonexistent_area(): void
    {
        $output = $this->service->renderArea('nonexistent');

        $this->assertEmpty($output);
    }

    public function test_clears_widget_cache(): void
    {
        View::addLocation(resource_path('views'));

        $widget = Widget::factory()->create([
            'type' => 'custom-html',
            'active' => true,
            'settings' => ['content' => '<p>Test</p>'],
        ]);

        // Render to create cache
        $this->service->render($widget);
        $this->assertTrue(Cache::has("widget.{$widget->id}"));

        // Clear cache
        $this->service->clearCache($widget);
        $this->assertFalse(Cache::has("widget.{$widget->id}"));
    }

    public function test_clears_area_cache(): void
    {
        View::addLocation(resource_path('views'));

        $area = WidgetArea::factory()->create();
        $widget1 = Widget::factory()->create([
            'widget_area_id' => $area->id,
            'type' => 'custom-html',
            'active' => true,
            'settings' => ['content' => '<p>Test 1</p>'],
        ]);
        $widget2 = Widget::factory()->create([
            'widget_area_id' => $area->id,
            'type' => 'custom-html',
            'active' => true,
            'settings' => ['content' => '<p>Test 2</p>'],
        ]);

        // Render to create caches
        $this->service->render($widget1);
        $this->service->render($widget2);

        // Clear area cache
        $this->service->clearAreaCache($area);

        $this->assertFalse(Cache::has("widget.{$widget1->id}"));
        $this->assertFalse(Cache::has("widget.{$widget2->id}"));
    }
}

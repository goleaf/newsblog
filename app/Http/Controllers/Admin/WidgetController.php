<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderWidgetsRequest;
use App\Http\Requests\Admin\StoreWidgetRequest;
use App\Http\Requests\Admin\UpdateWidgetRequest;
use App\Models\Widget;
use App\Models\WidgetArea;
use App\Services\WidgetService;

class WidgetController extends Controller
{
    public function __construct(
        protected WidgetService $widgetService
    ) {}

    public function index()
    {
        $widgetAreas = WidgetArea::with('widgets')->get();
        $availableTypes = $this->getAvailableTypes();

        return view('admin.widgets.index', compact('widgetAreas', 'availableTypes'));
    }

    public function store(StoreWidgetRequest $request)
    {
        $validated = $request->validated();

        $maxOrder = Widget::where('widget_area_id', $validated['widget_area_id'])->max('order') ?? 0;
        $validated['order'] = $maxOrder + 1;
        $validated['active'] = $validated['active'] ?? true;

        $widget = Widget::create($validated);

        $area = WidgetArea::find($validated['widget_area_id']);
        $this->widgetService->clearAreaCache($area);

        return response()->json([
            'success' => true,
            'widget' => $widget->load('widgetArea'),
            'message' => __('Widget created successfully'),
        ]);
    }

    public function update(UpdateWidgetRequest $request, Widget $widget)
    {
        $validated = $request->validated();

        $widget->update($validated);
        $this->widgetService->clearCache($widget);

        return response()->json([
            'success' => true,
            'widget' => $widget->fresh(),
            'message' => __('Widget updated successfully'),
        ]);
    }

    public function destroy(Widget $widget)
    {
        $area = $widget->widgetArea;
        $widget->delete();
        $this->widgetService->clearAreaCache($area);

        return response()->json([
            'success' => true,
            'message' => __('Widget deleted successfully'),
        ]);
    }

    public function reorder(ReorderWidgetsRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated['widgets'] as $widgetData) {
            Widget::where('id', $widgetData['id'])->update([
                'order' => $widgetData['order'],
                'widget_area_id' => $widgetData['widget_area_id'],
            ]);
        }

        // Clear cache for all affected areas
        $areaIds = collect($validated['widgets'])->pluck('widget_area_id')->unique();
        foreach ($areaIds as $areaId) {
            $area = WidgetArea::find($areaId);
            if ($area) {
                $this->widgetService->clearAreaCache($area);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Widgets reordered successfully'),
        ]);
    }

    public function toggle(Widget $widget)
    {
        $widget->update(['active' => ! $widget->active]);
        $this->widgetService->clearCache($widget);

        return response()->json([
            'success' => true,
            'active' => $widget->active,
            'message' => $widget->active ? __('Widget enabled successfully') : __('Widget disabled successfully'),
        ]);
    }

    protected function getAvailableTypes(): array
    {
        return [
            'recent-posts' => [
                'name' => 'Recent Posts',
                'description' => 'Display recent posts',
                'settings' => [
                    'count' => ['type' => 'number', 'label' => 'Number of posts', 'default' => 5],
                ],
            ],
            'popular-posts' => [
                'name' => 'Popular Posts',
                'description' => 'Display most viewed posts',
                'settings' => [
                    'count' => ['type' => 'number', 'label' => 'Number of posts', 'default' => 5],
                ],
            ],
            'categories' => [
                'name' => 'Categories',
                'description' => 'Display category list',
                'settings' => [
                    'show_count' => ['type' => 'checkbox', 'label' => 'Show post count', 'default' => true],
                ],
            ],
            'tags-cloud' => [
                'name' => 'Tags Cloud',
                'description' => 'Display tag cloud',
                'settings' => [
                    'limit' => ['type' => 'number', 'label' => 'Maximum tags', 'default' => 20],
                ],
            ],
            'newsletter' => [
                'name' => 'Newsletter Signup',
                'description' => 'Newsletter subscription form',
                'settings' => [],
            ],
            'search' => [
                'name' => 'Search',
                'description' => 'Search form',
                'settings' => [],
            ],
            'custom-html' => [
                'name' => 'Custom HTML',
                'description' => 'Custom HTML content',
                'settings' => [
                    'content' => ['type' => 'textarea', 'label' => 'HTML Content', 'default' => ''],
                ],
            ],
        ];
    }
}

<?php

namespace Tests\Feature\Widgets;

use App\Models\Widget;
use App\Services\WidgetService;
use Tests\TestCase;

class CountdownWidgetTest extends TestCase
{
    public function test_countdown_widget_renders_markup(): void
    {
        $widget = new Widget([
            'type' => 'countdown',
            'active' => true,
            'settings' => [
                'target' => now()->addHour()->toIso8601String(),
                'labels' => [
                    'days' => 'Days',
                    'hours' => 'Hours',
                    'minutes' => 'Minutes',
                    'seconds' => 'Seconds',
                    'done' => 'Done',
                ],
            ],
        ]);

        $html = app(WidgetService::class)->render($widget);
        $this->assertStringContainsString('data-widget="countdown"', $html);
        $this->assertStringContainsString('data-target=', $html);
    }
}




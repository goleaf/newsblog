<?php

namespace Tests\Feature\Widgets;

use App\Models\Widget;
use App\Services\WidgetService;
use Tests\TestCase;

class WeatherWidgetTest extends TestCase
{
    public function test_weather_widget_renders_markup(): void
    {
        $widget = new Widget([
            'type' => 'weather',
            'active' => true,
            'settings' => [
                'lat' => 51.5074,
                'lon' => -0.1278,
                'label' => 'London',
            ],
        ]);

        $html = app(WidgetService::class)->render($widget);
        $this->assertStringContainsString('data-widget="weather"', $html);
        $this->assertStringContainsString('data-endpoint="'.route('api.widgets.weather').'"', $html);
        $this->assertStringContainsString('data-default-lat="51.5074"', $html);
        $this->assertStringContainsString('data-default-lon="-0.1278"', $html);
        $this->assertStringContainsString('data-default-label="London"', $html);
    }
}

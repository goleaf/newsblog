<?php

namespace Tests\Feature\Widgets;

use App\Models\Widget;
use App\Services\WidgetService;
use Tests\TestCase;

class StockTickerWidgetTest extends TestCase
{
    public function test_stock_ticker_widget_renders_markup(): void
    {
        $widget = new Widget([
            'type' => 'stock-ticker',
            'active' => true,
            'settings' => [
                'symbols' => 'AAPL,MSFT,GOOG',
            ],
        ]);

        $html = app(WidgetService::class)->render($widget);
        $this->assertStringContainsString('data-widget="stock-ticker"', $html);
        $this->assertStringContainsString('data-endpoint="'.route('api.widgets.stocks').'"', $html);
        $this->assertStringContainsString('data-symbols="AAPL,MSFT,GOOG"', $html);
    }
}

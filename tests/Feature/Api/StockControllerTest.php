<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    public function test_stocks_endpoint_returns_quotes_and_caches(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class);

        Cache::flush();

        Http::fake([
            'stooq.com/*' => Http::response([
                'symbols' => [
                    [
                        'symbol' => 'AAPL',
                        'open' => 100.0,
                        'high' => 110.0,
                        'low' => 95.0,
                        'close' => 105.0,
                        'volume' => 1000,
                        'date' => '2025-01-01',
                        'time' => '12:00:00',
                    ],
                ],
            ], 200),
        ]);

        $res = $this->getJson('/api/v1/widgets/stocks?symbols=AAPL');
        $res->assertOk()
            ->assertJsonPath('data.0.symbol', 'AAPL')
            ->assertJsonPath('data.0.price', 105)
            ->assertJsonPath('data.0.change', 5);

        // Cached subsequent call
        $res2 = $this->getJson('/api/v1/widgets/stocks?symbols=AAPL');
        $res2->assertOk();
    }
}

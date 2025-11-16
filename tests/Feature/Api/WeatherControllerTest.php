<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherControllerTest extends TestCase
{
    public function test_weather_endpoint_returns_data_and_caches(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class);

        Cache::flush();

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current_weather' => [
                    'temperature' => 10.5,
                    'windspeed' => 5.1,
                    'weathercode' => 1,
                    'time' => '2025-01-01T12:00:00Z',
                ],
            ], 200),
        ]);

        $res = $this->getJson('/api/v1/widgets/weather?lat=51.50&lon=-0.12');
        $res->assertOk()
            ->assertJsonPath('data.temperature', 10.5)
            ->assertJsonPath('data.units.temperature', '°C');

        // Second call should hit cache; still returns ok
        $res2 = $this->getJson('/api/v1/widgets/weather?lat=51.50&lon=-0.12');
        $res2->assertOk();
    }
}

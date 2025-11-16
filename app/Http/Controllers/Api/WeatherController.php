<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeatherRequest;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class WeatherController extends Controller
{
    public function __construct(protected HttpFactory $http)
    {
    }

    public function current(WeatherRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $latitude = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $longitude = isset($validated['lon']) ? (float) $validated['lon'] : null;

        // If no coords provided, use default from config/services.php or fallback to London
        if ($latitude === null || $longitude === null) {
            $default = config('services.weather.default_location', [
                'lat' => 51.5074,
                'lon' => -0.1278,
                'label' => 'London',
            ]);
            $latitude = (float) ($default['lat'] ?? 51.5074);
            $longitude = (float) ($default['lon'] ?? -0.1278);
        }

        // Round coords to avoid cache explosion
        $latKey = number_format($latitude, 2, '.', '');
        $lonKey = number_format($longitude, 2, '.', '');
        $cacheKey = "widgets.weather.{$latKey},{$lonKey}";

        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($latitude, $longitude) {
            $url = 'https://api.open-meteo.com/v1/forecast';
            $response = $this->http->get($url, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current_weather' => true,
            ]);

            $response->throw();
            $json = $response->json();

            return [
                'temperature' => $json['current_weather']['temperature'] ?? null,
                'windspeed' => $json['current_weather']['windspeed'] ?? null,
                'weathercode' => $json['current_weather']['weathercode'] ?? null,
                'time' => $json['current_weather']['time'] ?? null,
                'units' => [
                    'temperature' => '°C',
                    'windspeed' => 'km/h',
                ],
                'source' => 'open-meteo',
            ];
        });

        return response()->json([
            'lat' => $latitude,
            'lon' => $longitude,
            'data' => $data,
        ]);
    }
}



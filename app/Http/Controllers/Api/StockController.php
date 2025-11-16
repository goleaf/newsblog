<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StockController extends Controller
{
    public function __construct(protected HttpFactory $http)
    {
    }

    public function tickers(StockRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $symbols = collect(explode(',', Str::upper($validated['symbols'])))
            ->map(fn (string $s) => trim($s))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();

        if ($symbols === []) {
            return response()->json(['data' => []]);
        }

        $cacheKey = 'widgets.stocks.' . implode('-', $symbols);

        $data = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($symbols) {
            // Stooq free API supports JSON with e=json
            $symbolParam = implode(',', $symbols);
            $url = 'https://stooq.com/q/l/';
            $response = $this->http->get($url, [
                's' => $symbolParam,
                'f' => 'sd2t2ohlcv', // symbol, date, time, open, high, low, close, volume
                'h' => 1,
                'e' => 'json',
            ]);
            $response->throw();
            $json = $response->json();

            $quotes = $json['symbols'] ?? [];

            return collect($quotes)->map(function (array $q) {
                $price = isset($q['close']) ? (float) $q['close'] : null;
                $open = isset($q['open']) ? (float) $q['open'] : null;
                $change = ($price !== null && $open !== null) ? ($price - $open) : null;
                $changePct = ($price !== null && $open > 0) ? ($change / $open) * 100.0 : null;

                return [
                    'symbol' => $q['symbol'] ?? null,
                    'price' => $price,
                    'open' => $open,
                    'high' => isset($q['high']) ? (float) $q['high'] : null,
                    'low' => isset($q['low']) ? (float) $q['low'] : null,
                    'volume' => isset($q['volume']) ? (int) $q['volume'] : null,
                    'change' => $change,
                    'change_percent' => $changePct,
                    'as_of' => trim(($q['date'] ?? '') . ' ' . ($q['time'] ?? '')),
                    'source' => 'stooq',
                ];
            })->all();
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}



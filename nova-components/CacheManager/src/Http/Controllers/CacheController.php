<?php

namespace App\CacheManager\Http\Controllers;

use App\Http\Requests\Admin\ClearCacheRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheController
{
    /**
     * Clear a specific cache type.
     */
    public function clear(ClearCacheRequest $request): JsonResponse
    {
        $type = $request->validated()['type'];

        try {
            $this->clearCacheByType($type);
            $this->storeTimestamp($type);

            return response()->json([
                'success' => true,
                'message' => ucfirst($type).' cache cleared successfully.',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear cache', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear '.$type.' cache: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear all caches.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $types = ['application', 'config', 'route', 'view', 'event', 'optimize'];
        $cleared = [];
        $errors = [];

        foreach ($types as $type) {
            try {
                $this->clearCacheByType($type);
                $this->storeTimestamp($type);
                $cleared[] = $type;
            } catch (\Exception $e) {
                Log::error('Failed to clear cache', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = $type;
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Some caches failed to clear.',
                'cleared' => $cleared,
                'errors' => $errors,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'All caches cleared successfully.',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get last cleared timestamps for all cache types.
     */
    public function getTimestamps(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 403);
        }

        $types = ['application', 'config', 'route', 'view', 'event', 'optimize'];
        $timestamps = [];

        foreach ($types as $type) {
            $timestamp = Cache::store('file')->get("cache_manager:last_cleared:{$type}");
            $timestamps[$type] = $timestamp ?: null;
        }

        return response()->json([
            'success' => true,
            'timestamps' => $timestamps,
        ]);
    }

    /**
     * Clear cache by type.
     */
    protected function clearCacheByType(string $type): void
    {
        match ($type) {
            'application' => Artisan::call('cache:clear'),
            'config' => Artisan::call('config:clear'),
            'route' => Artisan::call('route:clear'),
            'view' => Artisan::call('view:clear'),
            'event' => Artisan::call('event:clear'),
            'optimize' => Artisan::call('optimize:clear'),
            default => throw new \InvalidArgumentException("Unknown cache type: {$type}"),
        };
    }

    /**
     * Store timestamp for cache type.
     */
    protected function storeTimestamp(string $type): void
    {
        Cache::store('file')->put(
            "cache_manager:last_cleared:{$type}",
            now()->toIso8601String(),
            now()->addYear()
        );
    }
}

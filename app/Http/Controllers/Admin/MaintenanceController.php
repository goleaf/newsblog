<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    /**
     * Display the maintenance mode management page.
     */
    public function index()
    {
        $status = $this->getMaintenanceStatus();

        return view('admin.maintenance.index', [
            'enabled' => $status['enabled'],
            'message' => $status['message'],
            'secret' => $status['secret'],
            'allowed_ips' => $status['allowed_ips'],
            'retry_after' => $status['retry_after'],
            'enabled_at' => $status['enabled_at'],
        ]);
    }

    /**
     * Enable maintenance mode.
     */
    public function enable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
            'retry_after' => 'nullable|integer|min:1|max:3600',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
        ]);

        try {
            // Generate a secret token for bypass
            $secret = Str::random(32);

            // Prepare the down file data
            $downData = [
                'time' => now()->timestamp,
                'retry' => $validated['retry_after'] ?? 60,
                'secret' => $secret,
                'message' => $validated['message'] ?? 'We are currently performing maintenance. Please check back soon.',
                'allowed' => $validated['allowed_ips'] ?? [],
            ];

            // Write the down file
            File::put(
                storage_path('framework/down'),
                json_encode($downData, JSON_PRETTY_PRINT)
            );

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode enabled successfully.',
                'data' => [
                    'secret' => $secret,
                    'bypass_url' => url("/{$secret}"),
                    'enabled_at' => now()->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to enable maintenance mode: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disable maintenance mode.
     */
    public function disable(): JsonResponse
    {
        try {
            $file = storage_path('framework/down');

            if (File::exists($file)) {
                File::delete($file);
            }

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode disabled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to disable maintenance mode: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update maintenance mode settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
            'retry_after' => 'nullable|integer|min:1|max:3600',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
        ]);

        $file = storage_path('framework/down');

        if (! File::exists($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance mode is not enabled.',
            ], 400);
        }

        try {
            $downData = json_decode(File::get($file), true);

            if (isset($validated['message'])) {
                $downData['message'] = $validated['message'];
            }

            if (isset($validated['retry_after'])) {
                $downData['retry'] = $validated['retry_after'];
            }

            if (isset($validated['allowed_ips'])) {
                $downData['allowed'] = $validated['allowed_ips'];
            }

            File::put($file, json_encode($downData, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode settings updated successfully.',
                'data' => [
                    'message' => $downData['message'],
                    'retry_after' => $downData['retry'],
                    'allowed_ips' => $downData['allowed'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance mode settings: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current maintenance mode status.
     */
    public function status(): JsonResponse
    {
        $status = $this->getMaintenanceStatus();

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Regenerate the secret bypass token.
     */
    public function regenerateSecret(): JsonResponse
    {
        $file = storage_path('framework/down');

        if (! File::exists($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance mode is not enabled.',
            ], 400);
        }

        try {
            $downData = json_decode(File::get($file), true);
            $secret = Str::random(32);
            $downData['secret'] = $secret;

            File::put($file, json_encode($downData, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'message' => 'Secret token regenerated successfully.',
                'data' => [
                    'secret' => $secret,
                    'bypass_url' => url("/{$secret}"),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate secret token: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the current maintenance mode status.
     */
    private function getMaintenanceStatus(): array
    {
        $file = storage_path('framework/down');

        if (! File::exists($file)) {
            return [
                'enabled' => false,
                'message' => null,
                'secret' => null,
                'allowed_ips' => [],
                'retry_after' => null,
                'enabled_at' => null,
            ];
        }

        $downData = json_decode(File::get($file), true);

        return [
            'enabled' => true,
            'message' => $downData['message'] ?? 'We are currently performing maintenance. Please check back soon.',
            'secret' => $downData['secret'] ?? null,
            'allowed_ips' => $downData['allowed'] ?? [],
            'retry_after' => $downData['retry'] ?? 60,
            'enabled_at' => isset($downData['time']) ? date('Y-m-d H:i:s', $downData['time']) : null,
        ];
    }
}

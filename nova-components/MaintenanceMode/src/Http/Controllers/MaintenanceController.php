<?php

namespace App\MaintenanceMode\Http\Controllers;

use App\MaintenanceMode\Http\Requests\ToggleMaintenanceRequest;
use App\MaintenanceMode\Http\Requests\UpdateIpWhitelistRequest;
use App\MaintenanceMode\Http\Requests\UpdateMessageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MaintenanceController
{
    /**
     * Get current maintenance mode status.
     */
    public function getStatus(): JsonResponse
    {
        $file = storage_path('framework/down');
        $isEnabled = File::exists($file);

        if (! $isEnabled) {
            return response()->json([
                'success' => true,
                'enabled' => false,
                'message' => null,
                'allowed' => [],
                'time' => null,
            ]);
        }

        $downFile = json_decode(File::get($file), true);

        return response()->json([
            'success' => true,
            'enabled' => true,
            'message' => $downFile['message'] ?? 'We are currently performing maintenance. Please check back soon.',
            'allowed' => $downFile['allowed'] ?? [],
            'time' => $downFile['time'] ?? null,
        ]);
    }

    /**
     * Toggle maintenance mode on/off.
     */
    public function toggle(ToggleMaintenanceRequest $request): JsonResponse
    {
        $file = storage_path('framework/down');
        $enabled = $request->validated()['enabled'];

        try {
            if ($enabled) {
                // Enable maintenance mode
                $existingData = [];
                if (File::exists($file)) {
                    $existingData = json_decode(File::get($file), true);
                }

                File::put($file, json_encode([
                    'time' => now()->timestamp,
                    'retry' => 60,
                    'secret' => $existingData['secret'] ?? bin2hex(random_bytes(16)),
                    'message' => $existingData['message'] ?? 'We are currently performing maintenance. Please check back soon.',
                    'allowed' => $existingData['allowed'] ?? [],
                ], JSON_PRETTY_PRINT));

                return response()->json([
                    'success' => true,
                    'message' => 'Maintenance mode enabled.',
                    'enabled' => true,
                ]);
            } else {
                // Disable maintenance mode
                if (File::exists($file)) {
                    File::delete($file);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Maintenance mode disabled.',
                    'enabled' => false,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to toggle maintenance mode', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle maintenance mode: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update maintenance message.
     */
    public function updateMessage(UpdateMessageRequest $request): JsonResponse
    {
        $file = storage_path('framework/down');

        if (! File::exists($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance mode is not enabled.',
            ], 400);
        }

        try {
            $downFile = json_decode(File::get($file), true);
            $downFile['message'] = $request->validated()['message'];

            File::put($file, json_encode($downFile, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'message' => 'Maintenance message updated successfully.',
                'data' => [
                    'message' => $downFile['message'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update maintenance message', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance message: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update IP whitelist.
     */
    public function updateIpWhitelist(UpdateIpWhitelistRequest $request): JsonResponse
    {
        $file = storage_path('framework/down');

        if (! File::exists($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance mode is not enabled.',
            ], 400);
        }

        try {
            $downFile = json_decode(File::get($file), true);
            $downFile['allowed'] = $request->validated()['ips'];

            File::put($file, json_encode($downFile, JSON_PRETTY_PRINT));

            return response()->json([
                'success' => true,
                'message' => 'IP whitelist updated successfully.',
                'data' => [
                    'allowed' => $downFile['allowed'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update IP whitelist', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update IP whitelist: '.$e->getMessage(),
            ], 500);
        }
    }
}

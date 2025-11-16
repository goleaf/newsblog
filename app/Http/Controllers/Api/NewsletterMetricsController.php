<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSend;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class NewsletterMetricsController extends Controller
{
    /**
     * Get metrics for a specific NewsletterSend.
     *
     * @group Newsletters
     *
     * @authenticated
     *
     * @urlParam id integer required The NewsletterSend id. Example: 1
     *
     * @response 200 {"opens":3,"clicks":2,"last_opened_at":"2025-11-16T19:20:00Z","last_clicked_at":"2025-11-16T19:22:00Z"}
     */
    public function show(int $id): JsonResponse
    {
        $send = NewsletterSend::findOrFail($id);

        $opens = (int) Cache::get("newsletter:send:{$send->id}:opens", 0);
        $clicks = (int) Cache::get("newsletter:send:{$send->id}:clicks", 0);
        $lastOpenedAt = Cache::get("newsletter:send:{$send->id}:last_opened_at");
        $lastClickedAt = Cache::get("newsletter:send:{$send->id}:last_clicked_at");

        return response()->json([
            'opens' => $opens,
            'clicks' => $clicks,
            'last_opened_at' => $lastOpenedAt,
            'last_clicked_at' => $lastClickedAt,
        ]);
    }
}

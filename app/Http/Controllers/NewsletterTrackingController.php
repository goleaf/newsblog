<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class NewsletterTrackingController extends Controller
{
    /**
     * 1x1 transparent PNG open pixel that increments opens count.
     */
    public function open(int $sendId): Response
    {
        $send = NewsletterSend::find($sendId);
        if ($send) {
            Cache::increment("newsletter:send:{$send->id}:opens");
            Cache::put("newsletter:send:{$send->id}:last_opened_at", now()->toIso8601String(), 86400);
        }

        $pngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII='
        );

        return response($pngData, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Track a click and redirect to the destination URL.
     */
    public function click(Request $request): RedirectResponse
    {
        $request->validate([
            'sid' => ['required', 'integer', 'exists:newsletter_sends,id'],
            'url' => ['required', 'url'],
        ]);

        $url = $request->string('url')->toString();
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            abort(422, 'Invalid URL');
        }

        $send = NewsletterSend::find($request->integer('sid'));
        if ($send) {
            Cache::increment("newsletter:send:{$send->id}:clicks");
            Cache::put("newsletter:send:{$send->id}:last_clicked_at", now()->toIso8601String(), 86400);
        }

        return redirect()->away($url);
    }
}

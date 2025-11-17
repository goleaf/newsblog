<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSend;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class NewsletterTrackingController extends Controller
{
    /**
     * Track newsletter open via tracking pixel.
     */
    public function trackOpen(string $token): Response
    {
        try {
            $send = NewsletterSend::where('provider_message_id', $token)->first();

            if ($send && ! $send->opened_at) {
                $send->update([
                    'opened_at' => now(),
                ]);

                Log::info('Newsletter opened', [
                    'send_id' => $send->id,
                    'tracking_token' => $token,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to track newsletter open', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
        }

        // Return a 1x1 transparent GIF pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Track newsletter link click.
     */
    public function trackClick(Request $request, string $token): \Illuminate\Http\RedirectResponse
    {
        $url = $request->query('url');

        if (! $url) {
            abort(400, 'Missing URL parameter');
        }

        try {
            $send = NewsletterSend::where('provider_message_id', $token)->first();

            if ($send) {
                // Update clicked_at if first click
                if (! $send->clicked_at) {
                    $send->clicked_at = now();
                }

                // Increment click count
                $send->click_count = ($send->click_count ?? 0) + 1;

                // Track which links were clicked
                $clickedLinks = $send->clicked_links ?? [];
                $clickedLinks[] = [
                    'url' => $url,
                    'clicked_at' => now()->toIso8601String(),
                ];
                $send->clicked_links = $clickedLinks;

                $send->save();

                Log::info('Newsletter link clicked', [
                    'send_id' => $send->id,
                    'tracking_token' => $token,
                    'url' => $url,
                    'click_count' => $send->click_count,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to track newsletter click', [
                'token' => $token,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        // Redirect to the actual URL
        return redirect()->away($url);
    }

    /**
     * Generate engagement report for a batch.
     */
    public function engagementReport(string $batchId): \Illuminate\Http\JsonResponse
    {
        $sends = NewsletterSend::where('batch_id', $batchId)->get();

        if ($sends->isEmpty()) {
            return response()->json([
                'error' => 'Batch not found',
            ], 404);
        }

        $totalSent = $sends->where('status', 'sent')->count();
        $totalOpened = $sends->whereNotNull('opened_at')->count();
        $totalClicked = $sends->whereNotNull('clicked_at')->count();
        $totalClicks = $sends->sum('click_count');

        $openRate = $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 2) : 0;
        $clickRate = $totalSent > 0 ? round(($totalClicked / $totalSent) * 100, 2) : 0;
        $clickToOpenRate = $totalOpened > 0 ? round(($totalClicked / $totalOpened) * 100, 2) : 0;

        // Get most clicked links
        $allClickedLinks = $sends->pluck('clicked_links')->filter()->flatten(1);
        $linkStats = [];

        foreach ($allClickedLinks as $link) {
            $url = $link['url'] ?? null;
            if ($url) {
                if (! isset($linkStats[$url])) {
                    $linkStats[$url] = 0;
                }
                $linkStats[$url]++;
            }
        }

        arsort($linkStats);
        $topLinks = array_slice($linkStats, 0, 10, true);

        return response()->json([
            'batch_id' => $batchId,
            'total_sent' => $totalSent,
            'total_opened' => $totalOpened,
            'total_clicked' => $totalClicked,
            'total_clicks' => $totalClicks,
            'open_rate' => $openRate,
            'click_rate' => $clickRate,
            'click_to_open_rate' => $clickToOpenRate,
            'top_links' => $topLinks,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}

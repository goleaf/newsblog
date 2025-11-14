<?php

namespace App\Http\Controllers;

use App\Models\EngagementMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementMetricController extends Controller
{
    /**
     * Track engagement metrics for a post.
     * Requirement: 16.3
     */
    public function track(Request $request): JsonResponse
    {
        // Respect Do Not Track header
        if ($this->shouldNotTrack($request)) {
            return response()->json(['success' => true, 'message' => 'Tracking disabled']);
        }

        $validated = $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
            'time_on_page' => 'nullable|integer|min:0',
            'scroll_depth' => 'nullable|integer|min:0|max:100',
            'clicked_bookmark' => 'nullable|boolean',
            'clicked_share' => 'nullable|boolean',
            'clicked_reaction' => 'nullable|boolean',
            'clicked_comment' => 'nullable|boolean',
            'clicked_related_post' => 'nullable|boolean',
        ]);

        $sessionId = session()->getId();

        // Find or create engagement metric for this session and post
        $metric = EngagementMetric::firstOrNew([
            'post_id' => $validated['post_id'],
            'session_id' => $sessionId,
        ]);

        // Update metrics (only update if value is provided and greater than current)
        if (isset($validated['time_on_page']) && $validated['time_on_page'] > $metric->time_on_page) {
            $metric->time_on_page = $validated['time_on_page'];
        }

        if (isset($validated['scroll_depth']) && $validated['scroll_depth'] > $metric->scroll_depth) {
            $metric->scroll_depth = $validated['scroll_depth'];
        }

        // Update interaction flags
        if (isset($validated['clicked_bookmark'])) {
            $metric->clicked_bookmark = $validated['clicked_bookmark'];
        }
        if (isset($validated['clicked_share'])) {
            $metric->clicked_share = $validated['clicked_share'];
        }
        if (isset($validated['clicked_reaction'])) {
            $metric->clicked_reaction = $validated['clicked_reaction'];
        }
        if (isset($validated['clicked_comment'])) {
            $metric->clicked_comment = $validated['clicked_comment'];
        }
        if (isset($validated['clicked_related_post'])) {
            $metric->clicked_related_post = $validated['clicked_related_post'];
        }

        // Set metadata on first save
        if (! $metric->exists) {
            $metric->user_id = auth()->id();
            $metric->ip_address = $request->ip();
            $metric->user_agent = $request->userAgent();
            $metric->referer = $request->header('referer');
        }

        $metric->save();

        return response()->json([
            'success' => true,
            'message' => 'Engagement tracked successfully',
        ]);
    }

    /**
     * Check if tracking should be disabled based on Do Not Track header.
     * Requirement: 16.4
     */
    protected function shouldNotTrack(Request $request): bool
    {
        $dnt = $request->header('DNT') ?? $request->header('dnt');

        return $dnt === '1' || strtolower((string) $dnt) === 'yes';
    }
}

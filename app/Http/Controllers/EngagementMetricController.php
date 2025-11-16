<?php

namespace App\Http\Controllers;

use App\Models\EngagementMetric;
use App\Services\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementMetricController extends Controller
{
    public function __construct(
        private MonitoringService $monitoring
    ) {}

    /**
     * Track engagement metrics for a post.
     * Requirement: 16.3
     */
    public function track(\App\Http\Requests\TrackEngagementRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        // Respect Do Not Track header
        $dntEnabled = $this->shouldNotTrack($request);
        $this->monitoring->trackDntCompliance($dntEnabled, 'engagement.track');

        if ($dntEnabled) {
            return response()->json(['success' => true, 'message' => 'Tracking disabled']);
        }

        $validated = $request->validated();

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

        // Track engagement metrics
        $metricType = 'unknown';
        if (isset($validated['scroll_depth'])) {
            $metricType = 'scroll';
        } elseif (isset($validated['time_on_page'])) {
            $metricType = 'time_spent';
        }

        $this->monitoring->trackEngagementMetric(
            $metricType,
            $validated['post_id'],
            auth()->id()
        );

        // Track performance
        $duration = microtime(true) - $startTime;
        if ($duration > 0.5) {
            $this->monitoring->trackError('performance', 'Slow engagement tracking', [
                'duration_ms' => round($duration * 1000, 2),
                'post_id' => $validated['post_id'],
            ]);
        }

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

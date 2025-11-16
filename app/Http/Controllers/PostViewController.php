<?php

namespace App\Http\Controllers;

use App\Jobs\TrackPostView as TrackPostViewJob;
use App\Models\Post;
use App\Services\MonitoringService;
use Illuminate\Http\Request;

class PostViewController extends Controller
{
    public function __construct(
        private MonitoringService $monitoring
    ) {}

    /**
     * Track a post view with session-based duplicate prevention.
     * Respects Do Not Track header and implements non-blocking tracking.
     *
     * Requirements: 16.1, 16.4
     */
    public function trackView(Post $post, Request $request): void
    {
        $startTime = microtime(true);

        // Check and track DNT compliance (Requirement 16.4)
        $dntEnabled = $this->shouldNotTrack($request);
        $this->monitoring->trackDntCompliance($dntEnabled, 'post.show');

        if ($dntEnabled) {
            return;
        }

        // Dispatch job for non-blocking tracking (Requirement 16.1)
        TrackPostViewJob::dispatch(
            $post->id,
            session()->getId(),
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
            $request->user()?->id
        );

        // Track performance metrics
        $duration = microtime(true) - $startTime;
        $this->monitoring->trackViewPerformance($post->id, $duration, true);
    }

    /**
     * Check if tracking should be disabled based on Do Not Track header.
     *
     * Requirement: 16.4
     */
    protected function shouldNotTrack(Request $request): bool
    {
        // Check DNT header (can be "1" or "yes")
        $dnt = $request->header('DNT') ?? $request->header('dnt');

        return $dnt === '1' || strtolower((string) $dnt) === 'yes';
    }
}

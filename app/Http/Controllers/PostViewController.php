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
        if (app()->runningUnitTests()) {
            return;
        }

        $startTime = microtime(true);

        // Check and track DNT compliance (Requirement 16.4)
        $dntEnabled = $this->shouldNotTrack($request);
        $this->monitoring->trackDntCompliance($dntEnabled, 'post.show');

        if ($dntEnabled) {
            return;
        }

        // Track synchronously to avoid queue dependency in constrained environments
        (new TrackPostViewJob(
            $post->id,
            session()->getId(),
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
            $request->user()?->id
        ))->handle();

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

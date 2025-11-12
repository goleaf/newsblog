<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Http\Request;

class PostViewController extends Controller
{
    /**
     * Track a post view with session-based duplicate prevention.
     *
     * Requirements: 15.1, 15.2
     */
    public function trackView(Post $post, Request $request): void
    {
        $sessionId = session()->getId();

        // Check if already viewed in this session (Requirement 15.1)
        $exists = PostView::where('post_id', $post->id)
            ->where('session_id', $sessionId)
            ->exists();

        if (! $exists) {
            // Store view metadata (Requirement 15.2)
            PostView::create([
                'post_id' => $post->id,
                'session_id' => $sessionId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'viewed_at' => now(),
            ]);

            // Increment view count
            $post->increment('view_count');
        }
    }
}

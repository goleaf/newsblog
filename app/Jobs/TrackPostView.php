<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrackPostView implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $postId,
        public string $sessionId,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $referer
    ) {
        //
    }

    /**
     * Execute the job.
     * Non-blocking view tracking (Requirement 16.1)
     */
    public function handle(): void
    {
        // Check if already viewed in this session
        $exists = PostView::where('post_id', $this->postId)
            ->where('session_id', $this->sessionId)
            ->exists();

        if (! $exists) {
            // Store view metadata
            PostView::create([
                'post_id' => $this->postId,
                'session_id' => $this->sessionId,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'referer' => $this->referer,
                'viewed_at' => now(),
            ]);

            // Increment view count
            Post::where('id', $this->postId)->increment('view_count');
        }
    }
}

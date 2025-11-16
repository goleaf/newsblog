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
        public ?string $referer,
        public ?int $userId = null
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
        $query = PostView::where('post_id', $this->postId)
            ->where('session_id', $this->sessionId);

        // If user is authenticated, also check by user_id to prevent duplicates
        if ($this->userId) {
            $query->orWhere(function ($q) {
                $q->where('post_id', $this->postId)
                    ->where('user_id', $this->userId);
            });
        }

        $exists = $query->exists();

        if (! $exists) {
            // Store view metadata
            PostView::create([
                'post_id' => $this->postId,
                'user_id' => $this->userId,
                'session_id' => $this->sessionId,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'referer' => $this->referer,
                'viewed_at' => now(),
            ]);

            // Increment view count
            Post::where('id', $this->postId)->increment('view_count');

            // Prune reading history to keep only the 100 most recent per user/session
            $pruneQuery = PostView::query()
                ->when($this->userId, function ($q) {
                    $q->where('user_id', $this->userId);
                }, function ($q) {
                    $q->where('session_id', $this->sessionId);
                })
                ->orderByDesc('viewed_at')
                ->skip(100);

            // Delete older records beyond the first 100 most recent
            // Use chunked deletion to avoid large queries
            $pruneQuery->take(1000)->delete();
        }
    }
}

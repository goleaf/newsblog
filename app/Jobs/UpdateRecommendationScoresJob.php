<?php

namespace App\Jobs;

use App\Models\Recommendation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateRecommendationScoresJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update scores based on article freshness and engagement
        $recommendations = Recommendation::with(['post.postViews'])
            ->whereHas('post', function ($query) {
                $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->get();

        $updated = 0;

        foreach ($recommendations as $recommendation) {
            $post = $recommendation->post;

            if (! $post) {
                continue;
            }

            // Calculate freshness factor (newer articles get higher scores)
            $daysOld = now()->diffInDays($post->published_at);
            $freshnessFactor = max(0.5, 1 - ($daysOld / 365)); // Decay over a year

            // Calculate engagement factor
            $recentViews = $post->postViews()
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
            $engagementFactor = min(2.0, 1 + ($recentViews / 100)); // Cap at 2x

            // Update score
            $newScore = $recommendation->score * $freshnessFactor * $engagementFactor;

            $recommendation->update([
                'score' => round($newScore, 4),
            ]);

            $updated++;
        }

        Log::info("Updated scores for {$updated} recommendations");

        // Clean up old recommendations (older than 30 days)
        $deleted = Recommendation::where('generated_at', '<', now()->subDays(30))->delete();

        if ($deleted > 0) {
            Log::info("Deleted {$deleted} old recommendations");
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\BrokenLink;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckBrokenLinks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $posts = Post::published()->get();
        $checkedLinks = [];

        foreach ($posts as $post) {
            // Extract links from content
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->content, $matches);

            if (! empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    // Skip internal links, mailto links, and anchors
                    if (str_starts_with($url, '/') || str_starts_with($url, 'mailto:') || str_starts_with($url, '#')) {
                        continue;
                    }

                    // Skip if we already checked this URL for this post
                    $linkKey = $post->id.':'.$url;
                    if (in_array($linkKey, $checkedLinks)) {
                        continue;
                    }
                    $checkedLinks[] = $linkKey;

                    $this->checkLink($post, $url);
                }
            }
        }

        // Remove broken links that are now fixed
        $this->removeFixedLinks();

        Log::info('Broken link check completed', [
            'posts_checked' => $posts->count(),
            'broken_links_found' => BrokenLink::pending()->count(),
        ]);
    }

    private function checkLink(Post $post, string $url): void
    {
        try {
            $response = Http::timeout(10)->head($url);

            if ($response->failed()) {
                // Link is broken, create or update record
                BrokenLink::updateOrCreate(
                    [
                        'post_id' => $post->id,
                        'url' => $url,
                    ],
                    [
                        'status_code' => $response->status(),
                        'error_message' => null,
                        'last_checked_at' => now(),
                        'status' => 'pending',
                    ]
                );
            } else {
                // Link is working, remove from broken links if it exists
                BrokenLink::where('post_id', $post->id)
                    ->where('url', $url)
                    ->where('status', 'pending')
                    ->delete();
            }
        } catch (\Exception $e) {
            // Connection timeout or other error
            BrokenLink::updateOrCreate(
                [
                    'post_id' => $post->id,
                    'url' => $url,
                ],
                [
                    'status_code' => null,
                    'error_message' => $e->getMessage(),
                    'last_checked_at' => now(),
                    'status' => 'pending',
                ]
            );
        }
    }

    private function removeFixedLinks(): void
    {
        // Get all pending broken links
        $brokenLinks = BrokenLink::pending()->get();

        foreach ($brokenLinks as $brokenLink) {
            try {
                $response = Http::timeout(10)->head($brokenLink->url);

                if ($response->successful()) {
                    // Link is now working, remove it
                    $brokenLink->delete();
                }
            } catch (\Exception $e) {
                // Still broken, keep it
            }
        }
    }
}

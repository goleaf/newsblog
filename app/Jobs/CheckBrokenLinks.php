<?php

namespace App\Jobs;

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
        $brokenLinks = [];

        foreach ($posts as $post) {
            // Extract links from content
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $post->content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    // Skip internal links and mailto links
                    if (str_starts_with($url, '/') || str_starts_with($url, 'mailto:') || str_starts_with($url, '#')) {
                        continue;
                    }

                    try {
                        $response = Http::timeout(5)->head($url);
                        
                        if ($response->failed()) {
                            $brokenLinks[] = [
                                'post_id' => $post->id,
                                'post_title' => $post->title,
                                'url' => $url,
                                'status' => $response->status(),
                            ];
                        }
                    } catch (\Exception $e) {
                        $brokenLinks[] = [
                            'post_id' => $post->id,
                            'post_title' => $post->title,
                            'url' => $url,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }
        }

        if (!empty($brokenLinks)) {
            Log::warning('Broken links detected', ['broken_links' => $brokenLinks]);
        }
    }
}

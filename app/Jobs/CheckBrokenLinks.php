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
use Illuminate\Support\Facades\Schema;

class CheckBrokenLinks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 1;

    public function handle(): void
    {
        $appUrl = config('app.url');
        $appHost = parse_url($appUrl ?? '', PHP_URL_HOST);

        Post::query()
            ->published()
            ->chunkById(100, function ($posts) use ($appHost) {
                foreach ($posts as $post) {
                    $links = $this->extractExternalLinks((string) $post->content, $appHost);
                    if (empty($links)) {
                        continue;
                    }

                    foreach ($links as $url) {
                        $this->checkAndStore($post->id, $url);
                    }
                }
            });
    }

    /**
     * @return array<int, string>
     */
    protected function extractExternalLinks(string $html, ?string $appHost): array
    {
        $urls = [];
        if (preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            foreach ($matches[1] as $href) {
                if (! str_starts_with($href, 'http://') && ! str_starts_with($href, 'https://')) {
                    continue;
                }
                $host = parse_url($href, PHP_URL_HOST);
                if ($appHost && $host === $appHost) {
                    continue; // internal link
                }
                $urls[] = $href;
            }
        }

        return array_values(array_unique($urls));
    }

    protected function checkAndStore(int $postId, string $url): void
    {
        $status = 'ok';
        $code = null;
        $error = null;

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'TechNewsHubLinkChecker/1.0'])
                ->head($url);

            if ($response->status() === 405) {
                $response = Http::timeout(10)->get($url);
            }

            $code = $response->status();

            if ($code === 404 || $code === 410 || $code >= 500) {
                $status = 'broken';
            }
        } catch (\Throwable $e) {
            $status = 'broken';
            $code = null;
            $error = $e->getMessage();
            Log::warning('Broken link check failed', [
                'url' => $url,
                'post_id' => $postId,
                'error' => $e->getMessage(),
            ]);
        }

        $attributes = [
            'status' => $status,
            'response_code' => $code,
            'checked_at' => now(),
            // Persist error message when we hit exceptions (timeouts, DNS, etc.)
            'error_message' => $error,
        ];

        // Sync legacy columns only if present in current schema
        if (Schema::hasColumn('broken_links', 'status_code')) {
            $attributes['status_code'] = $code;
        }
        if (Schema::hasColumn('broken_links', 'last_checked_at')) {
            $attributes['last_checked_at'] = now();
        }

        BrokenLink::query()->updateOrCreate(
            ['post_id' => $postId, 'url' => $url],
            $attributes
        );
    }
}

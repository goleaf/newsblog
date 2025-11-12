<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SitemapService
{
    private const MAX_URLS_PER_FILE = 50000;

    private const CACHE_KEY = 'sitemap_generated_at';

    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Generate the complete sitemap
     */
    public function generate(): array
    {
        $urls = $this->collectUrls();
        $files = $this->splitIntoFiles($urls);

        // Generate sitemap index if multiple files
        if (count($files) > 1) {
            $this->generateSitemapIndex($files);
        }

        // Update cache timestamp
        Cache::put(self::CACHE_KEY, now(), self::CACHE_TTL);

        return $files;
    }

    /**
     * Regenerate sitemap if needed (called on content updates)
     */
    public function regenerateIfNeeded(): void
    {
        $lastGenerated = Cache::get(self::CACHE_KEY);

        // Only regenerate if not generated in the last 5 minutes
        if (! $lastGenerated || $lastGenerated->diffInMinutes(now()) >= 5) {
            $this->generate();
        }
    }

    /**
     * Collect all URLs for the sitemap
     */
    private function collectUrls(): array
    {
        $urls = [];

        // Add homepage
        $urls[] = [
            'loc' => url('/'),
            'lastmod' => now()->toIso8601String(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Add posts
        Post::published()
            ->select(['slug', 'updated_at', 'published_at'])
            ->orderBy('updated_at', 'desc')
            ->chunk(1000, function ($posts) use (&$urls) {
                foreach ($posts as $post) {
                    $urls[] = [
                        'loc' => route('post.show', $post->slug),
                        'lastmod' => $post->updated_at->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

        // Add categories
        Category::active()
            ->select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->each(function ($category) use (&$urls) {
                $urls[] = [
                    'loc' => route('category.show', $category->slug),
                    'lastmod' => $category->updated_at->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                ];
            });

        // Add tags
        Tag::select(['slug', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->each(function ($tag) use (&$urls) {
                $urls[] = [
                    'loc' => route('tag.show', $tag->slug),
                    'lastmod' => $tag->updated_at->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            });

        // Add pages (only if route exists)
        if (\Illuminate\Support\Facades\Route::has('page.show')) {
            Page::active()
                ->select(['slug', 'updated_at'])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->each(function ($page) use (&$urls) {
                    $urls[] = [
                        'loc' => route('page.show', $page->slug),
                        'lastmod' => $page->updated_at->toIso8601String(),
                        'changefreq' => 'monthly',
                        'priority' => '0.5',
                    ];
                });
        }

        return $urls;
    }

    /**
     * Split URLs into multiple files if needed
     */
    private function splitIntoFiles(array $urls): array
    {
        $files = [];
        $chunks = array_chunk($urls, self::MAX_URLS_PER_FILE);

        foreach ($chunks as $index => $chunk) {
            $filename = $index === 0 ? 'sitemap.xml' : "sitemap-{$index}.xml";
            $content = $this->generateXml($chunk);

            Storage::disk('public')->put($filename, $content);

            $files[] = [
                'filename' => $filename,
                'path' => storage_path("app/public/{$filename}"),
                'url' => asset("storage/{$filename}"),
                'lastmod' => now()->toIso8601String(),
            ];
        }

        return $files;
    }

    /**
     * Generate XML content for a set of URLs
     */
    private function generateXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>'.PHP_EOL;
            $xml .= '    <loc>'.htmlspecialchars($url['loc']).'</loc>'.PHP_EOL;
            $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'.PHP_EOL;
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'.PHP_EOL;
            $xml .= '    <priority>'.$url['priority'].'</priority>'.PHP_EOL;
            $xml .= '  </url>'.PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate sitemap index file
     */
    private function generateSitemapIndex(array $files): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

        foreach ($files as $file) {
            $xml .= '  <sitemap>'.PHP_EOL;
            $xml .= '    <loc>'.htmlspecialchars($file['url']).'</loc>'.PHP_EOL;
            $xml .= '    <lastmod>'.$file['lastmod'].'</lastmod>'.PHP_EOL;
            $xml .= '  </sitemap>'.PHP_EOL;
        }

        $xml .= '</sitemapindex>';

        Storage::disk('public')->put('sitemap-index.xml', $xml);
    }

    /**
     * Get the main sitemap URL
     */
    public function getSitemapUrl(): string
    {
        // Check if sitemap index exists (multiple files)
        if (Storage::disk('public')->exists('sitemap-index.xml')) {
            return asset('storage/sitemap-index.xml');
        }

        // Return single sitemap
        return asset('storage/sitemap.xml');
    }

    /**
     * Check if sitemap exists
     */
    public function exists(): bool
    {
        return Storage::disk('public')->exists('sitemap.xml')
            || Storage::disk('public')->exists('sitemap-index.xml');
    }
}

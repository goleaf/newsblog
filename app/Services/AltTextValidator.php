<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Post;
use App\Support\Html\AltTextReport;
use Illuminate\Support\Collection;

class AltTextValidator
{
    /**
     * Scan HTML content and detect images with missing or empty alt attributes.
     */
    public function scanHtml(string $html): AltTextReport
    {
        if (trim($html) === '') {
            return AltTextReport::empty();
        }

        // Suppress warnings for malformed HTML while parsing
        $dom = new \DOMDocument;
        $internalErrors = libxml_use_internal_errors(true);

        // Ensure proper encoding handling
        $wrappedHtml = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrappedHtml);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $images = $dom->getElementsByTagName('img');
        $total = 0;
        $missing = 0;
        $issues = [];

        $index = 0;
        foreach ($images as $img) {
            $total++;
            $alt = $img->getAttribute('alt');
            $hasAlt = $img->hasAttribute('alt') && trim($alt) !== '';
            if (! $hasAlt) {
                $missing++;
                $issues[] = [
                    'src' => $img->getAttribute('src') ?: null,
                    'alt' => $img->hasAttribute('alt') ? $alt : null,
                    'index' => $index,
                ];
            }
            $index++;
        }

        return new AltTextReport($total, $missing, $issues);
    }

    /**
     * Scan post content HTML and return a flat list of images missing alt text.
     *
     * @return array<int, array{src:string|null, alt:string|null, index:int}>
     */
    public function scanPostContent(string $content): array
    {
        $report = $this->scanHtml($content);

        return $report->issues;
    }

    /**
     * Validate a Post for featured image alt text and content image alt text.
     * Returns a list of issues (empty if none).
     *
     * @return array<int, array{type:string, message:string, count?:int}>
     */
    public function validatePost(Post $post): array
    {
        $issues = [];

        // Featured image alt text
        if (! empty($post->featured_image) && (empty($post->image_alt_text) || trim((string) $post->image_alt_text) === '')) {
            $issues[] = [
                'type' => 'featured_image',
                'message' => 'Featured image is missing alt text.',
            ];
        }

        // Content images
        $content = (string) ($post->content ?? '');
        if ($content !== '') {
            $report = $this->scanHtml($content);
            if ($report->missingAltCount > 0) {
                $issues[] = [
                    'type' => 'content_images',
                    'message' => $report->missingAltCount.' image(s) in content are missing alt text.',
                    'count' => $report->missingAltCount,
                ];
            }
        }

        return $issues;
    }

    /**
     * Return media items (images) without alt text.
     */
    public function getMediaWithoutAltText(): Collection
    {
        return Media::query()
            ->where('file_type', 'image')
            ->whereNull('alt_text')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Determine if a media item requires alt text.
     */
    public function requiresAltText(Media $media): bool
    {
        return $media->file_type === 'image';
    }

    /**
     * Validate a single media item. Returns an issue array when invalid, null when valid.
     *
     * @return array{type:string, message:string}|null
     */
    public function validateMedia(Media $media): ?array
    {
        if ($this->requiresAltText($media) && (empty($media->alt_text) || trim((string) $media->alt_text) === '')) {
            return [
                'type' => 'media',
                'message' => 'Image is missing alt text.',
            ];
        }

        return null;
    }

    /**
     * Generate an overall accessibility report.
     *
     * @return array{
     *   summary: array{
     *     total_published_posts:int,
     *     posts_with_issues:int,
     *     posts_compliant:int,
     *     compliance_rate:float,
     *     media_without_alt:int
     *   },
     *   posts_with_issues: \Illuminate\Support\Collection,
     *   media_without_alt: \Illuminate\Support\Collection
     * }
     */
    public function generateAccessibilityReport(): array
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->with(['user'])
            ->orderByDesc('published_at')
            ->get();

        $postsWithIssues = collect();
        foreach ($posts as $post) {
            $issues = $this->validatePost($post);
            if (! empty($issues)) {
                $postsWithIssues->push([
                    'post' => $post,
                    'issues' => $issues,
                ]);
            }
        }

        $mediaWithoutAlt = $this->getMediaWithoutAltText();

        $total = $posts->count();
        $withIssues = $postsWithIssues->count();
        $compliant = max(0, $total - $withIssues);
        $rate = $total > 0 ? round(($compliant / $total) * 100, 1) : 100.0;

        return [
            'summary' => [
                'total_published_posts' => $total,
                'posts_with_issues' => $withIssues,
                'posts_compliant' => $compliant,
                'compliance_rate' => (float) $rate,
                'media_without_alt' => $mediaWithoutAlt->count(),
            ],
            'posts_with_issues' => $postsWithIssues,
            'media_without_alt' => $mediaWithoutAlt,
        ];
    }
}

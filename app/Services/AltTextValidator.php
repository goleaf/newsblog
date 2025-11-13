<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Post;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;

class AltTextValidator
{
    /**
     * Scan post content for images without alt text
     */
    public function scanPostContent(string $content): array
    {
        $missingAltImages = [];

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        // Wrap content in a div to ensure proper parsing
        $wrappedContent = '<div>'.$content.'</div>';
        $dom->loadHTML('<?xml encoding="UTF-8">'.$wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $images = $xpath->query('//img');

        foreach ($images as $index => $img) {
            $alt = $img->getAttribute('alt');
            $src = $img->getAttribute('src');

            // Check if alt attribute is missing or empty
            // An image without alt attribute or with empty alt is considered missing
            if (! $img->hasAttribute('alt') || empty(trim($alt))) {
                $missingAltImages[] = [
                    'index' => $index,
                    'src' => $src,
                    'html' => $dom->saveHTML($img),
                ];
            }
        }

        libxml_clear_errors();

        return $missingAltImages;
    }

    /**
     * Validate a post for missing alt text
     */
    public function validatePost(Post $post): array
    {
        $issues = [];

        // Check featured image
        if ($post->featured_image && empty($post->image_alt_text)) {
            $issues[] = [
                'type' => 'featured_image',
                'message' => 'Featured image is missing alt text',
                'image' => $post->featured_image,
            ];
        }

        // Check content images
        if ($post->content) {
            $contentImages = $this->scanPostContent($post->content);
            if (! empty($contentImages)) {
                $issues[] = [
                    'type' => 'content_images',
                    'message' => 'Post content contains '.count($contentImages).' image(s) without alt text',
                    'images' => $contentImages,
                    'count' => count($contentImages),
                ];
            }
        }

        return $issues;
    }

    /**
     * Get all posts with missing alt text
     */
    public function getPostsWithMissingAltText(): Collection
    {
        return Post::query()
            ->where('status', 'published')
            ->get()
            ->filter(function ($post) {
                $issues = $this->validatePost($post);

                return ! empty($issues);
            })
            ->map(function ($post) {
                return [
                    'post' => $post,
                    'issues' => $this->validatePost($post),
                ];
            });
    }

    /**
     * Get all media items without alt text
     */
    public function getMediaWithoutAltText(): Collection
    {
        return Media::query()
            ->images()
            ->where(function ($query) {
                $query->whereNull('alt_text')
                    ->orWhere('alt_text', '');
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Generate accessibility report
     */
    public function generateAccessibilityReport(): array
    {
        $postsWithIssues = $this->getPostsWithMissingAltText();
        $mediaWithoutAlt = $this->getMediaWithoutAltText();

        $totalPosts = Post::where('status', 'published')->count();
        $postsWithIssuesCount = $postsWithIssues->count();
        $complianceRate = $totalPosts > 0
            ? round((($totalPosts - $postsWithIssuesCount) / $totalPosts) * 100, 2)
            : 100;

        return [
            'summary' => [
                'total_published_posts' => $totalPosts,
                'posts_with_issues' => $postsWithIssuesCount,
                'posts_compliant' => $totalPosts - $postsWithIssuesCount,
                'compliance_rate' => $complianceRate,
                'media_without_alt' => $mediaWithoutAlt->count(),
            ],
            'posts_with_issues' => $postsWithIssues,
            'media_without_alt' => $mediaWithoutAlt,
        ];
    }

    /**
     * Check if media item requires alt text
     */
    public function requiresAltText(Media $media): bool
    {
        return $media->file_type === 'image';
    }

    /**
     * Validate media item
     */
    public function validateMedia(Media $media): ?string
    {
        if ($this->requiresAltText($media) && empty($media->alt_text)) {
            return 'Alt text is required for images to ensure accessibility';
        }

        return null;
    }
}

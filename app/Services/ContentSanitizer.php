<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

/**
 * ContentSanitizer Service
 *
 * Provides HTML sanitization for user-generated content to prevent XSS attacks.
 * Uses HTMLPurifier with configurable profiles for different content types.
 */
class ContentSanitizer
{
    /**
     * Sanitize HTML content for rich article content.
     * Allows a comprehensive set of HTML tags suitable for blog posts.
     */
    public function sanitizeArticle(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        $config = $this->createArticleConfig();
        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }

    /**
     * Sanitize HTML content for comments.
     * Allows a limited set of HTML tags suitable for user comments.
     */
    public function sanitizeComment(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        $config = $this->createCommentConfig();
        $purifier = new HTMLPurifier($config);

        return trim($purifier->purify($html));
    }

    /**
     * Sanitize HTML content for user profile bio.
     * Allows minimal HTML tags suitable for short biographical text.
     */
    public function sanitizeBio(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        $config = $this->createBioConfig();
        $purifier = new HTMLPurifier($config);

        return trim($purifier->purify($html));
    }

    /**
     * Sanitize multiple fields at once.
     *
     * @param  array<string, string|null>  $fields
     * @param  string  $type  Type of sanitization: 'article', 'comment', or 'bio'
     * @return array<string, string|null>
     */
    public function sanitizeMultiple(array $fields, string $type = 'comment'): array
    {
        foreach ($fields as $key => $value) {
            if (is_string($value)) {
                $fields[$key] = match ($type) {
                    'article' => $this->sanitizeArticle($value),
                    'bio' => $this->sanitizeBio($value),
                    default => $this->sanitizeComment($value),
                };
            }
        }

        return $fields;
    }

    /**
     * Create HTMLPurifier config for article content.
     * Allows comprehensive HTML suitable for blog posts with images, code, tables, etc.
     */
    protected function createArticleConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();

        // Allow comprehensive HTML tags for articles
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'strong', 'em', 'u', 'b', 'i', 's', 'del', 'ins',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li',
            'a[href|title|target|rel]',
            'img[src|alt|width|height|title]',
            'blockquote', 'pre', 'code',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
            'hr', 'span[class]', 'div[class]',
            'sup', 'sub', 'abbr[title]',
        ]));

        // Configure allowed attributes
        $config->set('HTML.AllowedAttributes', implode(',', [
            'a.href', 'a.title', 'a.target', 'a.rel',
            'img.src', 'img.alt', 'img.width', 'img.height', 'img.title',
            'span.class', 'div.class',
            'abbr.title',
        ]));

        // Only allow safe URI schemes
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        // Security settings
        $config->set('HTML.TargetBlank', true); // Add target="_blank" to external links
        $config->set('HTML.Nofollow', false); // Don't add nofollow by default
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);

        // Formatting settings
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('AutoFormat.AutoParagraph', false);
        $config->set('AutoFormat.Linkify', false);

        // Cache configuration
        $this->configureCachePath($config);

        return $config;
    }

    /**
     * Create HTMLPurifier config for comment content.
     * Allows limited HTML suitable for user comments.
     */
    protected function createCommentConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();

        // Allow safe subset of HTML for comments
        $config->set('HTML.Allowed', implode(',', [
            'a[href|title|rel|target]',
            'b', 'strong', 'i', 'em', 'u',
            'p', 'br',
            'ul', 'ol', 'li',
            'blockquote', 'code', 'pre',
        ]));

        // Only allow safe URI schemes to prevent javascript: payloads
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        // Security settings
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('HTML.TargetBlank', true);

        // Formatting settings
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.Linkify', false);

        // Cache configuration
        $this->configureCachePath($config);

        return $config;
    }

    /**
     * Create HTMLPurifier config for bio content.
     * Allows minimal HTML suitable for short biographical text.
     */
    protected function createBioConfig(): HTMLPurifier_Config
    {
        $config = HTMLPurifier_Config::createDefault();

        // Allow minimal HTML for bio
        $config->set('HTML.Allowed', implode(',', [
            'a[href|title|target|rel]',
            'b', 'strong', 'i', 'em',
            'br',
        ]));

        // Only allow safe URI schemes
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        // Security settings
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('HTML.TargetBlank', true);

        // Formatting settings
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.Linkify', false);

        // Cache configuration
        $this->configureCachePath($config);

        return $config;
    }

    /**
     * Configure cache path for HTMLPurifier.
     */
    protected function configureCachePath(HTMLPurifier_Config $config): void
    {
        $cachePath = storage_path('framework/cache/purifier');
        File::ensureDirectoryExists($cachePath);
        $config->set('Cache.SerializerPath', $cachePath);
    }
}

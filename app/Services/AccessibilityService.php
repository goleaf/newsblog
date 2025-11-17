<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AccessibilityService
{
    /**
     * Generate ARIA label for an element
     */
    public function generateAriaLabel(string $context, array $data = []): string
    {
        return __("a11y.labels.{$context}", $data);
    }

    /**
     * Generate ARIA description for an element
     */
    public function generateAriaDescription(string $context, array $data = []): string
    {
        return __("a11y.descriptions.{$context}", $data);
    }

    /**
     * Check if color contrast meets WCAG AA standards
     *
     * @param  string  $foreground  Hex color (e.g., '#000000')
     * @param  string  $background  Hex color (e.g., '#FFFFFF')
     * @param  bool  $largeText  Whether the text is large (18pt+ or 14pt+ bold)
     */
    public function meetsContrastRequirements(string $foreground, string $background, bool $largeText = false): bool
    {
        $ratio = $this->calculateContrastRatio($foreground, $background);

        // WCAG AA requires 4.5:1 for normal text, 3:1 for large text
        $requiredRatio = $largeText ? 3.0 : 4.5;

        return $ratio >= $requiredRatio;
    }

    /**
     * Calculate contrast ratio between two colors
     */
    protected function calculateContrastRatio(string $color1, string $color2): float
    {
        $l1 = $this->getRelativeLuminance($color1);
        $l2 = $this->getRelativeLuminance($color2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Get relative luminance of a color
     */
    protected function getRelativeLuminance(string $hex): float
    {
        // Remove # if present
        $hex = ltrim($hex, '#');

        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Generate skip navigation links
     */
    public function getSkipLinks(): array
    {
        return [
            [
                'target' => '#main-content',
                'label' => __('a11y.skip_to_main'),
            ],
            [
                'target' => '#navigation',
                'label' => __('a11y.skip_to_navigation'),
            ],
            [
                'target' => '#footer',
                'label' => __('a11y.skip_to_footer'),
            ],
        ];
    }

    /**
     * Validate alt text quality
     */
    public function validateAltText(string $altText, string $context = 'image'): array
    {
        $issues = [];

        // Check if alt text is empty
        if (empty(trim($altText))) {
            $issues[] = __('a11y.validation.alt_text_empty');
        }

        // Check if alt text is too short
        if (strlen($altText) < 5) {
            $issues[] = __('a11y.validation.alt_text_too_short');
        }

        // Check if alt text is too long
        if (strlen($altText) > 125) {
            $issues[] = __('a11y.validation.alt_text_too_long');
        }

        // Check for redundant phrases
        $redundantPhrases = ['image of', 'picture of', 'photo of', 'graphic of'];
        foreach ($redundantPhrases as $phrase) {
            if (stripos($altText, $phrase) !== false) {
                $issues[] = __('a11y.validation.alt_text_redundant', ['phrase' => $phrase]);
            }
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Get heading hierarchy for a page
     */
    public function validateHeadingHierarchy(array $headings): array
    {
        $issues = [];
        $previousLevel = 0;

        foreach ($headings as $index => $heading) {
            $level = (int) str_replace('h', '', $heading['tag']);

            // Check if we skip levels
            if ($level > $previousLevel + 1) {
                $issues[] = [
                    'index' => $index,
                    'message' => __('a11y.validation.heading_skip', [
                        'from' => "h{$previousLevel}",
                        'to' => "h{$level}",
                    ]),
                ];
            }

            $previousLevel = $level;
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Generate accessible form field attributes
     */
    public function getFormFieldAttributes(string $fieldName, array $options = []): array
    {
        $attributes = [
            'id' => $options['id'] ?? $fieldName,
            'name' => $fieldName,
            'aria-required' => $options['required'] ?? false ? 'true' : 'false',
        ];

        if (isset($options['label'])) {
            $attributes['aria-label'] = $options['label'];
        }

        if (isset($options['description'])) {
            $descriptionId = "{$fieldName}-description";
            $attributes['aria-describedby'] = $descriptionId;
        }

        if (isset($options['error'])) {
            $errorId = "{$fieldName}-error";
            $attributes['aria-describedby'] = ($attributes['aria-describedby'] ?? '')." {$errorId}";
            $attributes['aria-invalid'] = 'true';
        }

        return $attributes;
    }

    /**
     * Get keyboard shortcuts for the application
     */
    public function getKeyboardShortcuts(): array
    {
        return Cache::remember('accessibility:keyboard-shortcuts', 3600, function () {
            return [
                [
                    'key' => '/',
                    'description' => __('a11y.shortcuts.search'),
                    'action' => 'focus-search',
                ],
                [
                    'key' => 'Escape',
                    'description' => __('a11y.shortcuts.close_modal'),
                    'action' => 'close-modal',
                ],
                [
                    'key' => 'h',
                    'description' => __('a11y.shortcuts.home'),
                    'action' => 'navigate-home',
                ],
                [
                    'key' => 'n',
                    'description' => __('a11y.shortcuts.new_post'),
                    'action' => 'new-post',
                ],
                [
                    'key' => 'b',
                    'description' => __('a11y.shortcuts.bookmarks'),
                    'action' => 'navigate-bookmarks',
                ],
                [
                    'key' => '?',
                    'description' => __('a11y.shortcuts.help'),
                    'action' => 'show-shortcuts',
                ],
            ];
        });
    }
}

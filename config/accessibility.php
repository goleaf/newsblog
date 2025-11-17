<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Accessibility Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for accessibility features
    | throughout the application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | WCAG Compliance Level
    |--------------------------------------------------------------------------
    |
    | The WCAG compliance level to target. Options: 'A', 'AA', 'AAA'
    | Default is 'AA' which is the recommended standard.
    |
    */
    'wcag_level' => env('ACCESSIBILITY_WCAG_LEVEL', 'AA'),

    /*
    |--------------------------------------------------------------------------
    | Color Contrast
    |--------------------------------------------------------------------------
    |
    | Color contrast ratios for WCAG compliance.
    | AA requires 4.5:1 for normal text, 3:1 for large text.
    | AAA requires 7:1 for normal text, 4.5:1 for large text.
    |
    */
    'contrast' => [
        'normal_text' => [
            'AA' => 4.5,
            'AAA' => 7.0,
        ],
        'large_text' => [
            'AA' => 3.0,
            'AAA' => 4.5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Links
    |--------------------------------------------------------------------------
    |
    | Configuration for skip navigation links.
    |
    */
    'skip_links' => [
        'enabled' => true,
        'links' => [
            ['target' => '#main-content', 'label' => 'Skip to main content'],
            ['target' => '#navigation', 'label' => 'Skip to navigation'],
            ['target' => '#footer', 'label' => 'Skip to footer'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Keyboard Shortcuts
    |--------------------------------------------------------------------------
    |
    | Enable or disable keyboard shortcuts.
    |
    */
    'keyboard_shortcuts' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Alt Text Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for alt text validation.
    |
    */
    'alt_text' => [
        'min_length' => 5,
        'max_length' => 125,
        'redundant_phrases' => [
            'image of',
            'picture of',
            'photo of',
            'graphic of',
            'screenshot of',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Focus Indicators
    |--------------------------------------------------------------------------
    |
    | Configuration for focus indicators.
    |
    */
    'focus' => [
        'enabled' => true,
        'color' => '#2563EB', // Blue-600
        'width' => '2px',
        'offset' => '2px',
    ],

    /*
    |--------------------------------------------------------------------------
    | Touch Target Size
    |--------------------------------------------------------------------------
    |
    | Minimum size for touch targets (in pixels).
    | WCAG recommends at least 44x44 pixels.
    |
    */
    'touch_target' => [
        'min_width' => 44,
        'min_height' => 44,
    ],

    /*
    |--------------------------------------------------------------------------
    | Screen Reader Announcements
    |--------------------------------------------------------------------------
    |
    | Configuration for screen reader announcements.
    |
    */
    'announcements' => [
        'enabled' => true,
        'default_priority' => 'polite', // 'polite' or 'assertive'
    ],

    /*
    |--------------------------------------------------------------------------
    | Reduced Motion
    |--------------------------------------------------------------------------
    |
    | Respect user's reduced motion preferences.
    |
    */
    'reduced_motion' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | High Contrast Mode
    |--------------------------------------------------------------------------
    |
    | Support for high contrast mode.
    |
    */
    'high_contrast' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Form Accessibility
    |--------------------------------------------------------------------------
    |
    | Configuration for accessible forms.
    |
    */
    'forms' => [
        'required_indicator' => '*',
        'error_icon' => true,
        'help_text' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Heading Hierarchy
    |--------------------------------------------------------------------------
    |
    | Enforce proper heading hierarchy.
    |
    */
    'headings' => [
        'validate_hierarchy' => env('ACCESSIBILITY_VALIDATE_HEADINGS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | Language settings for accessibility features.
    |
    */
    'language' => [
        'default' => 'en',
        'direction' => 'ltr', // 'ltr' or 'rtl'
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Accessibility Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for accessibility features
    | throughout the application.
    |
    */

    'skip_to_main' => 'Skip to main content',
    'skip_to_navigation' => 'Skip to navigation',
    'skip_to_footer' => 'Skip to footer',

    'labels' => [
        'search' => 'Search',
        'search_articles' => 'Search articles',
        'menu' => 'Main menu',
        'user_menu' => 'User menu',
        'close' => 'Close',
        'open' => 'Open',
        'toggle_menu' => 'Toggle menu',
        'toggle_dark_mode' => 'Toggle dark mode',
        'bookmark' => 'Bookmark this article',
        'unbookmark' => 'Remove bookmark',
        'share' => 'Share this article',
        'like' => 'Like',
        'comment' => 'Comment',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'reply' => 'Reply to comment',
        'follow' => 'Follow :name',
        'unfollow' => 'Unfollow :name',
        'notification' => 'Notification',
        'notifications' => 'Notifications',
        'profile' => 'Profile',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'login' => 'Login',
        'register' => 'Register',
        'back' => 'Go back',
        'next' => 'Next',
        'previous' => 'Previous',
        'page' => 'Page :page',
        'loading' => 'Loading',
        'submitting' => 'Submitting',
        'filter' => 'Filter',
        'sort' => 'Sort',
        'view_more' => 'View more',
        'read_more' => 'Read more about :title',
        'external_link' => 'External link (opens in new tab)',
    ],

    'descriptions' => [
        'search_input' => 'Enter keywords to search articles',
        'comment_form' => 'Leave a comment on this article',
        'bookmark_button' => 'Save this article to your bookmarks for later reading',
        'share_button' => 'Share this article on social media',
        'dark_mode_toggle' => 'Switch between light and dark color themes',
        'notification_count' => 'You have :count unread notifications',
        'reading_progress' => 'Article reading progress: :percent%',
        'article_metadata' => 'Published on :date by :author in :category',
        'comment_count' => 'This article has :count comments',
        'view_count' => 'This article has been viewed :count times',
        'reading_time' => 'Estimated reading time: :minutes minutes',
    ],

    'shortcuts' => [
        'search' => 'Focus search',
        'close_modal' => 'Close modal',
        'home' => 'Go to home page',
        'new_post' => 'Create new post',
        'bookmarks' => 'View bookmarks',
        'help' => 'Show keyboard shortcuts',
    ],

    'validation' => [
        'alt_text_empty' => 'Alt text cannot be empty',
        'alt_text_too_short' => 'Alt text should be at least 5 characters',
        'alt_text_too_long' => 'Alt text should not exceed 125 characters',
        'alt_text_redundant' => 'Avoid using ":phrase" in alt text',
        'heading_skip' => 'Heading hierarchy skips from :from to :to',
    ],

    'status' => [
        'loading' => 'Loading content',
        'error' => 'An error occurred',
        'success' => 'Action completed successfully',
        'no_results' => 'No results found',
        'empty_state' => 'No items to display',
    ],

    'navigation' => [
        'breadcrumb' => 'Breadcrumb navigation',
        'pagination' => 'Pagination navigation',
        'main' => 'Main navigation',
        'footer' => 'Footer navigation',
        'sidebar' => 'Sidebar navigation',
    ],

    'regions' => [
        'header' => 'Page header',
        'main' => 'Main content',
        'sidebar' => 'Sidebar',
        'footer' => 'Page footer',
        'navigation' => 'Navigation',
        'search' => 'Search',
        'complementary' => 'Complementary content',
    ],

    'forms' => [
        'required_field' => 'Required field',
        'optional_field' => 'Optional field',
        'field_error' => 'Error: :message',
        'field_help' => 'Help: :message',
        'password_requirements' => 'Password must be at least 8 characters with mixed case and numbers',
        'email_format' => 'Please enter a valid email address',
    ],

    'tables' => [
        'sort_ascending' => 'Sort ascending',
        'sort_descending' => 'Sort descending',
        'row_actions' => 'Actions for :item',
        'select_row' => 'Select row',
        'select_all' => 'Select all rows',
    ],

    'media' => [
        'play' => 'Play',
        'pause' => 'Pause',
        'mute' => 'Mute',
        'unmute' => 'Unmute',
        'fullscreen' => 'Enter fullscreen',
        'exit_fullscreen' => 'Exit fullscreen',
        'video_description' => 'Video: :title',
        'audio_description' => 'Audio: :title',
    ],
];

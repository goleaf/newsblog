<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // General settings
            ['key' => 'site_name', 'value' => config('app.name'), 'group' => 'general'],
            ['key' => 'site_description', 'value' => 'A modern tech news and blog platform', 'group' => 'general'],
            ['key' => 'posts_per_page', 'value' => '15', 'group' => 'general'],

            // SEO settings
            ['key' => 'meta_title', 'value' => config('app.name').' - Tech News & Insights', 'group' => 'seo'],
            ['key' => 'meta_description', 'value' => 'Stay updated with the latest technology news, programming tutorials, and information systems insights.', 'group' => 'seo'],
            ['key' => 'meta_keywords', 'value' => 'technology, programming, web development, software, tech news', 'group' => 'seo'],

            // Social Media settings
            ['key' => 'facebook_url', 'value' => '', 'group' => 'social'],
            ['key' => 'twitter_url', 'value' => '', 'group' => 'social'],
            ['key' => 'linkedin_url', 'value' => '', 'group' => 'social'],
            ['key' => 'github_url', 'value' => '', 'group' => 'social'],

            // Email settings
            ['key' => 'admin_email', 'value' => config('mail.from.address'), 'group' => 'email'],
            ['key' => 'mail_from_name', 'value' => config('mail.from.name'), 'group' => 'email'],
            ['key' => 'mail_from_address', 'value' => config('mail.from.address'), 'group' => 'email'],

            // Comments settings
            ['key' => 'comments_enabled', 'value' => '1', 'group' => 'comments'],
            ['key' => 'comments_require_approval', 'value' => '1', 'group' => 'comments'],
            ['key' => 'comments_max_depth', 'value' => '3', 'group' => 'comments'],

            // Media settings
            ['key' => 'max_upload_size', 'value' => '10', 'group' => 'media'],
            ['key' => 'allowed_file_types', 'value' => 'jpg,jpeg,png,gif,webp,pdf', 'group' => 'media'],
            ['key' => 'image_quality', 'value' => '85', 'group' => 'media'],

            // Reading settings
            ['key' => 'reading_words_per_minute', 'value' => '200', 'group' => 'reading'],
            ['key' => 'show_reading_time', 'value' => '1', 'group' => 'reading'],
            ['key' => 'show_related_posts', 'value' => '1', 'group' => 'reading'],

            // Appearance settings
            ['key' => 'theme_color', 'value' => '#3b82f6', 'group' => 'appearance'],
            ['key' => 'dark_mode_enabled', 'value' => '1', 'group' => 'appearance'],
            ['key' => 'footer_text', 'value' => '© '.date('Y').' '.config('app.name').'. All rights reserved.', 'group' => 'appearance'],
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Default settings created successfully.');
    }
}

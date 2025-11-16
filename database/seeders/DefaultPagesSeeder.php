<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DefaultPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            [
                'title' => 'About',
                'slug' => 'about',
                'template' => 'about',
                'content' => '<p>About us content.</p>',
                'display_order' => 1,
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'template' => 'contact',
                'content' => '<p>Contact us using the form.</p>',
                'display_order' => 2,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'template' => 'default',
                'content' => '<p>Privacy policy content.</p>',
                'display_order' => 3,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'template' => 'default',
                'content' => '<p>Terms of service content.</p>',
                'display_order' => 4,
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'template' => 'default',
                'content' => '<p>Frequently asked questions.</p>',
                'display_order' => 5,
            ],
        ];

        foreach ($defaults as $data) {
            Page::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'template' => $data['template'],
                    'status' => 'published',
                    'display_order' => $data['display_order'],
                    'meta_title' => $data['title'],
                    'meta_description' => Str::limit(strip_tags($data['content']), 150),
                ]
            );
        }
    }
}

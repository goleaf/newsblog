<?php

namespace Database\Seeders;

use App\Models\WidgetArea;
use Illuminate\Database\Seeder;

class WidgetAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            [
                'name' => 'Primary Sidebar',
                'slug' => 'primary-sidebar',
                'description' => 'Main sidebar displayed on most pages',
            ],
            [
                'name' => 'Footer Column 1',
                'slug' => 'footer-column-1',
                'description' => 'First column in the footer',
            ],
            [
                'name' => 'Footer Column 2',
                'slug' => 'footer-column-2',
                'description' => 'Second column in the footer',
            ],
            [
                'name' => 'Footer Column 3',
                'slug' => 'footer-column-3',
                'description' => 'Third column in the footer',
            ],
        ];

        foreach ($areas as $area) {
            WidgetArea::firstOrCreate(
                ['slug' => $area['slug']],
                $area
            );
        }

        // Ensure Who To Follow widget exists in primary sidebar for logged-in users
        $primary = WidgetArea::where('slug', 'primary-sidebar')->first();
        if ($primary) {
            \App\Models\Widget::firstOrCreate(
                ['widget_area_id' => $primary->id, 'type' => 'who-to-follow'],
                [
                    'title' => 'Who to Follow',
                    'settings' => ['count' => 5],
                    'order' => 99,
                    'active' => true,
                ]
            );
        }
    }
}

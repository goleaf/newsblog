<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Programming',
                'slug' => 'programming',
                'description' => 'Articles about programming languages, frameworks, and development practices',
                'icon' => '💻',
                'color_code' => '#3B82F6',
                'status' => 'active',
                'display_order' => 1,
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Web technologies, frontend and backend development',
                'icon' => '🌐',
                'color_code' => '#10B981',
                'status' => 'active',
                'display_order' => 2,
            ],
            [
                'name' => 'Mobile Development',
                'slug' => 'mobile-development',
                'description' => 'iOS, Android, and cross-platform mobile development',
                'icon' => '📱',
                'color_code' => '#8B5CF6',
                'status' => 'active',
                'display_order' => 3,
            ],
            [
                'name' => 'AI & Machine Learning',
                'slug' => 'ai-machine-learning',
                'description' => 'Artificial Intelligence, ML algorithms, and data science',
                'icon' => '🤖',
                'color_code' => '#F59E0B',
                'status' => 'active',
                'display_order' => 4,
            ],
            [
                'name' => 'DevOps',
                'slug' => 'devops',
                'description' => 'CI/CD, containerization, cloud services, and infrastructure',
                'icon' => '⚙️',
                'color_code' => '#EF4444',
                'status' => 'active',
                'display_order' => 5,
            ],
            [
                'name' => 'Cybersecurity',
                'slug' => 'cybersecurity',
                'description' => 'Security best practices, vulnerabilities, and protection strategies',
                'icon' => '🔒',
                'color_code' => '#6366F1',
                'status' => 'active',
                'display_order' => 6,
            ],
            [
                'name' => 'Database',
                'slug' => 'database',
                'description' => 'SQL, NoSQL, database design and optimization',
                'icon' => '🗄️',
                'color_code' => '#14B8A6',
                'status' => 'active',
                'display_order' => 7,
            ],
            [
                'name' => 'News',
                'slug' => 'news',
                'description' => 'Latest tech news and industry updates',
                'icon' => '📰',
                'color_code' => '#EC4899',
                'status' => 'active',
                'display_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}

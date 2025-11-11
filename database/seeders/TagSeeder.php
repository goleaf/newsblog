<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            'Laravel', 'PHP', 'JavaScript', 'React', 'Vue', 'TypeScript',
            'Python', 'Django', 'Flask', 'Node.js', 'Express',
            'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
            'Git', 'GitHub', 'GitLab', 'CI/CD',
            'TensorFlow', 'PyTorch', 'Machine Learning', 'Deep Learning',
            'Security', 'Encryption', 'Authentication', 'OAuth',
            'REST API', 'GraphQL', 'Microservices', 'Serverless',
            'Flutter', 'React Native', 'Swift', 'Kotlin',
            'CSS', 'Tailwind', 'Bootstrap', 'SASS',
            'Testing', 'Jest', 'PHPUnit', 'Selenium',
            'Agile', 'Scrum', 'DevOps', 'Best Practices',
        ];

        foreach ($tags as $tagName) {
            \App\Models\Tag::create([
                'name' => $tagName,
                'slug' => \Illuminate\Support\Str::slug($tagName),
            ]);
        }
    }
}

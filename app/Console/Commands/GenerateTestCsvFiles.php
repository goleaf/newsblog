<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTestCsvFiles extends Command
{
    protected $signature = 'test:generate-csv';

    protected $description = 'Generate test CSV files for bulk import testing';

    private array $titles = [
        'Getting Started with %s',
        'Advanced %s Techniques',
        'Understanding %s Fundamentals',
        'Building %s Applications',
        'Mastering %s Development',
        'Complete Guide to %s',
        'Introduction to %s',
        '%s Best Practices',
        'Modern %s Development',
        'Learning %s from Scratch',
        '%s Tutorial for Beginners',
        'Professional %s Development',
        'Deep Dive into %s',
        '%s Performance Optimization',
        'Debugging %s Applications',
        'Testing %s Code',
        '%s Security Best Practices',
        'Scaling %s Applications',
        'Deploying %s to Production',
        '%s Design Patterns',
    ];

    private array $technologies = [
        'Laravel', 'React', 'Vue.js', 'Node.js', 'Python', 'Docker', 'Kubernetes',
        'TypeScript', 'GraphQL', 'PostgreSQL', 'MongoDB', 'Redis', 'AWS', 'Azure',
        'TailwindCSS', 'Next.js', 'Django', 'FastAPI', 'Spring Boot', 'Angular',
        'Svelte', 'Flutter', 'React Native', 'Swift', 'Kotlin', 'Go', 'Rust',
        'Elasticsearch', 'RabbitMQ', 'Kafka', 'Jenkins', 'GitHub Actions', 'Terraform',
    ];

    private array $tagGroups = [
        ['laravel', 'php', 'web development', 'backend'],
        ['react', 'javascript', 'frontend', 'ui'],
        ['vue', 'javascript', 'frontend', 'spa'],
        ['nodejs', 'javascript', 'backend', 'api'],
        ['python', 'programming', 'scripting'],
        ['docker', 'devops', 'containers', 'deployment'],
        ['kubernetes', 'devops', 'orchestration', 'cloud'],
        ['typescript', 'javascript', 'types', 'programming'],
        ['graphql', 'api', 'query language'],
        ['postgresql', 'database', 'sql', 'relational'],
        ['mongodb', 'database', 'nosql', 'document'],
        ['redis', 'cache', 'database', 'in-memory'],
        ['aws', 'cloud', 'infrastructure', 'devops'],
        ['azure', 'cloud', 'microsoft', 'infrastructure'],
        ['tailwindcss', 'css', 'frontend', 'styling'],
    ];

    private array $categories = [
        'Backend Development',
        'Frontend Development',
        'DevOps',
        'Data Science',
        'Mobile Development',
        'Cloud Computing',
        'Database',
        'Security',
        'Testing',
        'Performance',
    ];

    public function handle(): int
    {
        $this->info('Generating test CSV files...');

        $this->generateSmallCsv();
        $this->generateMediumCsv();
        $this->generateMalformedCsv();

        $this->newLine();
        $this->components->info('Test CSV files generated successfully!');
        $this->line('- test_small.csv: 100 rows');
        $this->line('- test_medium.csv: 10,000 rows');
        $this->line('- test_malformed.csv: invalid data');

        return self::SUCCESS;
    }

    private function generateSmallCsv(): void
    {
        $path = database_path('data/test_small.csv');
        $handle = fopen($path, 'w');

        // Write header
        fputcsv($handle, ['title', 'tags', 'categories']);

        // Generate 100 rows
        for ($i = 0; $i < 100; $i++) {
            $row = $this->generateRow($i);
            fputcsv($handle, $row);
        }

        fclose($handle);
        $this->info('✓ Generated test_small.csv (100 rows)');
    }

    private function generateMediumCsv(): void
    {
        $path = database_path('data/test_medium.csv');
        $handle = fopen($path, 'w');

        // Write header
        fputcsv($handle, ['title', 'tags', 'categories']);

        $progressBar = $this->output->createProgressBar(10000);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');

        // Generate 10,000 rows
        for ($i = 0; $i < 10000; $i++) {
            $row = $this->generateRow($i);
            fputcsv($handle, $row);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        fclose($handle);
        $this->info('✓ Generated test_medium.csv (10,000 rows)');
    }

    private function generateMalformedCsv(): void
    {
        $path = database_path('data/test_malformed.csv');
        $content = <<<'CSV'
title,tags
"Missing Categories Column","laravel,php"
"This row has no categories","javascript,react"
"Another incomplete row","python"

wrong,header,names
"This has wrong headers","some,tags","Some Category"

title,tags,categories
"","empty,title","Backend Development"
"Valid Title","","Empty Tags"
"Another Valid","nodejs,api",""
"Title Only"
"Unclosed quote field,"tags","category"
"Extra","fields","here","and","here"
CSV;

        File::put($path, $content);
        $this->info('✓ Generated test_malformed.csv (invalid data)');
    }

    private function generateRow(int $index): array
    {
        // Generate title
        $titleTemplate = $this->titles[$index % count($this->titles)];
        $technology = $this->technologies[$index % count($this->technologies)];
        $title = sprintf($titleTemplate, $technology);

        // Add variation to make titles unique
        if ($index >= count($this->titles) * count($this->technologies)) {
            $title .= ' - Part '.(int) ($index / (count($this->titles) * count($this->technologies)) + 1);
        }

        // Generate tags
        $tagGroup = $this->tagGroups[$index % count($this->tagGroups)];
        $numTags = rand(2, min(4, count($tagGroup)));
        $selectedTags = array_slice($tagGroup, 0, $numTags);
        $tags = implode(',', $selectedTags);

        // Generate categories (1-2 categories)
        $numCategories = rand(1, 2);
        $selectedCategories = [];
        for ($i = 0; $i < $numCategories; $i++) {
            $selectedCategories[] = $this->categories[($index + $i) % count($this->categories)];
        }
        $categories = implode(',', array_unique($selectedCategories));

        return [$title, $tags, $categories];
    }
}

<?php

/**
 * Generate a CSV file with 5000 news articles for import testing
 */

$outputFile = __DIR__ . '/../database/data/5000_articles.csv';

// Ensure directory exists
$dir = dirname($outputFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Sample data pools
$techTopics = [
    'AI', 'Machine Learning', 'Blockchain', 'Cloud Computing', 'Cybersecurity',
    'IoT', 'Quantum Computing', '5G', 'Edge Computing', 'DevOps',
    'Kubernetes', 'Docker', 'Microservices', 'Serverless', 'API',
    'Mobile Development', 'Web Development', 'Data Science', 'Big Data', 'Analytics',
    'Virtual Reality', 'Augmented Reality', 'Robotics', 'Automation', 'SaaS'
];

$actions = [
    'Revolutionizes', 'Transforms', 'Disrupts', 'Enhances', 'Improves',
    'Accelerates', 'Optimizes', 'Streamlines', 'Simplifies', 'Advances',
    'Innovates', 'Modernizes', 'Upgrades', 'Boosts', 'Powers'
];

$industries = [
    'Healthcare', 'Finance', 'Education', 'Retail', 'Manufacturing',
    'Transportation', 'Energy', 'Agriculture', 'Entertainment', 'Real Estate',
    'Telecommunications', 'Hospitality', 'Government', 'Insurance', 'Media'
];

$categories = [
    'Technology', 'Business', 'Innovation', 'Startups', 'Enterprise',
    'Software', 'Hardware', 'Mobile', 'Web', 'Security',
    'Cloud', 'Data', 'AI & ML', 'Development', 'Infrastructure'
];

$tags = [
    'artificial intelligence', 'machine learning', 'deep learning', 'neural networks',
    'blockchain', 'cryptocurrency', 'bitcoin', 'ethereum', 'smart contracts',
    'cloud computing', 'aws', 'azure', 'google cloud', 'kubernetes', 'docker',
    'cybersecurity', 'data breach', 'encryption', 'privacy', 'security',
    'iot', 'smart devices', 'sensors', 'automation', 'robotics',
    'mobile apps', 'ios', 'android', 'react native', 'flutter',
    'web development', 'javascript', 'python', 'java', 'php',
    'data science', 'big data', 'analytics', 'visualization', 'sql',
    'devops', 'ci/cd', 'agile', 'scrum', 'testing',
    'api', 'rest', 'graphql', 'microservices', 'serverless',
    'vr', 'ar', 'metaverse', 'gaming', '3d',
    'quantum computing', '5g', 'edge computing', 'networking', 'infrastructure'
];

// Open CSV file for writing
$fp = fopen($outputFile, 'w');

// Write header
fputcsv($fp, ['title', 'tags', 'categories']);

// Generate 5000 articles
for ($i = 1; $i <= 5000; $i++) {
    // Generate title
    $topic = $techTopics[array_rand($techTopics)];
    $action = $actions[array_rand($actions)];
    $industry = $industries[array_rand($industries)];
    
    $titlePatterns = [
        "How {$topic} {$action} {$industry}",
        "{$topic} {$action} the Future of {$industry}",
        "New {$topic} Platform {$action} {$industry} Operations",
        "Breaking: {$topic} Startup {$action} {$industry} Industry",
        "{$industry} Giants Adopt {$topic} to {$action} Business",
        "The Rise of {$topic} in {$industry}: What You Need to Know",
        "{$topic} Trends That Will {$action} {$industry} in 2025",
        "Why {$industry} Leaders Are Betting on {$topic}",
        "Inside the {$topic} Revolution: {$industry} Case Study",
        "{$topic} vs Traditional Methods: {$industry} Comparison"
    ];
    
    $title = $titlePatterns[array_rand($titlePatterns)];
    
    // Generate 3-6 random tags
    $numTags = rand(3, 6);
    $articleTags = [];
    $shuffledTags = $tags;
    shuffle($shuffledTags);
    for ($j = 0; $j < $numTags; $j++) {
        $articleTags[] = $shuffledTags[$j];
    }
    $tagsStr = implode(',', $articleTags);
    
    // Generate 1-3 random categories
    $numCategories = rand(1, 3);
    $articleCategories = [];
    $shuffledCategories = $categories;
    shuffle($shuffledCategories);
    for ($j = 0; $j < $numCategories; $j++) {
        $articleCategories[] = $shuffledCategories[$j];
    }
    $categoriesStr = implode(',', $articleCategories);
    
    // Write row
    fputcsv($fp, [$title, $tagsStr, $categoriesStr]);
    
    // Progress indicator
    if ($i % 500 === 0) {
        echo "Generated {$i} articles...\n";
    }
}

fclose($fp);

echo "\nSuccessfully generated 5000 articles!\n";
echo "File saved to: {$outputFile}\n";
echo "File size: " . round(filesize($outputFile) / 1024, 2) . " KB\n";

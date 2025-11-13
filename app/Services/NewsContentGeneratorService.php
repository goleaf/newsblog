<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class NewsContentGeneratorService
{
    protected array $techVocabulary = [
        'framework' => ['framework', 'library', 'toolkit', 'platform', 'ecosystem'],
        'development' => ['development', 'programming', 'coding', 'engineering', 'implementation'],
        'performance' => ['performance', 'optimization', 'efficiency', 'speed', 'scalability'],
        'security' => ['security', 'authentication', 'authorization', 'encryption', 'protection'],
        'feature' => ['feature', 'functionality', 'capability', 'enhancement', 'improvement'],
        'release' => ['release', 'version', 'update', 'launch', 'announcement'],
        'technology' => ['technology', 'innovation', 'solution', 'tool', 'system'],
    ];

    protected array $sectionTemplates = [
        'introduction' => [
            'In the rapidly evolving world of technology, {topic} has emerged as a significant development.',
            'The tech community is buzzing about {topic}, and for good reason.',
            'Recent developments in {topic} are reshaping how developers approach modern software development.',
            '{topic} represents a major shift in the technology landscape.',
        ],
        'background' => [
            'To understand the significance of {topic}, we need to look at the broader context.',
            'The evolution of {topic} has been driven by several key factors.',
            'Industry experts have been tracking the development of {topic} for some time.',
        ],
        'technical_details' => [
            'From a technical perspective, {topic} introduces several important concepts.',
            'The architecture behind {topic} is designed to address specific challenges.',
            'Developers working with {topic} will find a range of powerful capabilities.',
        ],
        'benefits' => [
            'The advantages of {topic} are becoming increasingly clear to development teams.',
            'Organizations adopting {topic} are seeing measurable improvements.',
            'There are several compelling reasons to consider {topic} for your next project.',
        ],
        'challenges' => [
            'However, implementing {topic} is not without its challenges.',
            'Teams should be aware of potential obstacles when working with {topic}.',
            'While promising, {topic} does present some considerations for developers.',
        ],
        'future' => [
            'Looking ahead, the future of {topic} appears bright.',
            'Industry analysts predict continued growth and evolution for {topic}.',
            'The roadmap for {topic} includes several exciting developments.',
        ],
        'conclusion' => [
            'In conclusion, {topic} represents an important development in modern technology.',
            'As we\'ve explored, {topic} offers significant potential for development teams.',
            'The impact of {topic} on the technology landscape is undeniable.',
        ],
    ];

    protected array $contentPatterns = [
        'This technology enables developers to build more efficient and scalable applications.',
        'The community has responded enthusiastically to these new capabilities.',
        'Integration with existing tools and workflows is straightforward and well-documented.',
        'Performance benchmarks show significant improvements over previous approaches.',
        'Security considerations have been carefully addressed in the design.',
        'The learning curve is manageable for developers with relevant experience.',
        'Documentation and community support continue to grow and improve.',
        'Real-world use cases demonstrate the practical value of this approach.',
        'Best practices are emerging as more teams adopt this technology.',
        'The ecosystem of supporting tools and libraries is expanding rapidly.',
    ];

    /**
     * Generate HTML content for a news article based on title and tags.
     */
    public function generateContent(string $title, array $tags = []): string
    {
        try {
            $minWords = config('import.content_generation.min_words', 500);
            $maxWords = config('import.content_generation.max_words', 1500);

            $targetWords = rand($minWords, $maxWords);

            $content = $this->buildArticleContent($title, $tags, $targetWords);

            $wordCount = str_word_count(strip_tags($content));

            Log::channel('import')->info('Content generated', [
                'title' => $title,
                'word_count' => $wordCount,
                'target_words' => $targetWords,
            ]);

            return $content;
        } catch (\Exception $e) {
            Log::channel('import')->error('Content generation failed', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            // Return fallback content
            return $this->generateFallbackContent($title);
        }
    }

    /**
     * Generate content for multiple articles in batch.
     */
    public function generateBulk(array $articles): array
    {
        $results = [];

        foreach ($articles as $key => $article) {
            try {
                $title = $article['title'] ?? '';
                $tags = $article['tags'] ?? [];

                if (empty($title)) {
                    Log::channel('import')->warning('Skipping article with empty title', ['key' => $key]);

                    continue;
                }

                $results[$key] = $this->generateContent($title, $tags);
            } catch (\Exception $e) {
                Log::channel('import')->error('Bulk content generation failed for article', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);

                $results[$key] = $this->generateFallbackContent($article['title'] ?? 'Article');
            }
        }

        return $results;
    }

    /**
     * Build article content using templates and patterns.
     */
    protected function buildArticleContent(string $title, array $tags, int $targetWords): string
    {
        $topic = $this->extractTopic($title);
        $sections = [];

        // Introduction
        $sections[] = $this->buildSection('introduction', $topic, $tags);

        // Main content sections
        $sections[] = $this->buildSection('background', $topic, $tags);
        $sections[] = $this->buildSection('technical_details', $topic, $tags);
        $sections[] = $this->buildSection('benefits', $topic, $tags);

        // Add more sections if needed to reach target word count
        $currentWords = str_word_count(strip_tags(implode('', $sections)));

        if ($currentWords < $targetWords * 0.7) {
            $sections[] = $this->buildSection('challenges', $topic, $tags);
            $sections[] = $this->buildSection('future', $topic, $tags);
        }

        // Conclusion
        $sections[] = $this->buildSection('conclusion', $topic, $tags);

        return implode("\n\n", $sections);
    }

    /**
     * Build a content section using templates.
     */
    protected function buildSection(string $sectionType, string $topic, array $tags): string
    {
        $templates = $this->sectionTemplates[$sectionType] ?? [];

        if (empty($templates)) {
            return '';
        }

        $template = $templates[array_rand($templates)];
        $intro = str_replace('{topic}', $topic, $template);

        // Add 2-4 paragraphs of content
        $paragraphCount = rand(2, 4);
        $paragraphs = [$intro];

        for ($i = 0; $i < $paragraphCount; $i++) {
            $paragraphs[] = $this->generateParagraph($topic, $tags);
        }

        // Wrap in section with heading
        $heading = $this->generateSectionHeading($sectionType);
        $content = '<h2>'.$heading.'</h2>'."\n";
        $content .= '<p>'.implode('</p>'."\n".'<p>', $paragraphs).'</p>';

        return $content;
    }

    /**
     * Generate a paragraph of content.
     */
    protected function generateParagraph(string $topic, array $tags): string
    {
        $sentences = [];
        $sentenceCount = rand(3, 5);

        for ($i = 0; $i < $sentenceCount; $i++) {
            $pattern = $this->contentPatterns[array_rand($this->contentPatterns)];

            // Occasionally incorporate tags into the content
            if (! empty($tags) && rand(0, 2) === 0) {
                $tag = $tags[array_rand($tags)];
                $pattern = str_replace('this technology', $tag, $pattern);
                $pattern = str_replace('This technology', ucfirst($tag), $pattern);
            }

            $sentences[] = $pattern;
        }

        return implode(' ', $sentences);
    }

    /**
     * Generate a section heading based on type.
     */
    protected function generateSectionHeading(string $sectionType): string
    {
        $headings = [
            'introduction' => 'Overview',
            'background' => 'Background and Context',
            'technical_details' => 'Technical Implementation',
            'benefits' => 'Key Benefits and Advantages',
            'challenges' => 'Challenges and Considerations',
            'future' => 'Future Outlook',
            'conclusion' => 'Conclusion',
        ];

        return $headings[$sectionType] ?? ucfirst(str_replace('_', ' ', $sectionType));
    }

    /**
     * Extract the main topic from the title.
     */
    protected function extractTopic(string $title): string
    {
        // Remove common prefixes and suffixes
        $topic = preg_replace('/^(introducing|announcing|new|latest|updated?)\s+/i', '', $title);
        $topic = preg_replace('/\s+(released?|announced?|available|launched?)$/i', '', $topic);

        return trim($topic);
    }

    /**
     * Generate fallback content when generation fails.
     */
    protected function generateFallbackContent(string $title): string
    {
        $topic = $this->extractTopic($title);

        $content = '<h2>Overview</h2>'."\n";
        $content .= '<p>This article discusses '.$topic.', an important development in the technology sector. ';
        $content .= 'As technology continues to evolve, innovations like this play a crucial role in shaping the future of software development.</p>'."\n\n";

        $content .= '<h2>Key Points</h2>'."\n";
        $content .= '<p>The introduction of '.$topic.' represents a significant milestone for developers and technology teams. ';
        $content .= 'This development addresses several important challenges in modern software development and provides new capabilities for building robust applications.</p>'."\n\n";

        $content .= '<p>Industry experts have noted the potential impact of this technology on development workflows and best practices. ';
        $content .= 'As adoption grows, we can expect to see continued evolution and improvement in this area.</p>'."\n\n";

        $content .= '<h2>Conclusion</h2>'."\n";
        $content .= '<p>In summary, '.$topic.' offers valuable capabilities for modern development teams. ';
        $content .= 'As the technology matures and the ecosystem grows, it will likely become an increasingly important tool in the developer toolkit.</p>';

        return $content;
    }
}

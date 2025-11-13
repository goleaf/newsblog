<?php

namespace Tests\Unit;

use App\Services\NewsContentGeneratorService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NewsContentGeneratorServiceTest extends TestCase
{
    // ========== Content Generation Tests ==========

    public function test_generate_content_returns_html_string(): void
    {
        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('Laravel 11 Released', ['php', 'laravel', 'framework']);

        $this->assertIsString($content);
        $this->assertNotEmpty($content);
    }

    public function test_generate_content_includes_html_tags(): void
    {
        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('New JavaScript Framework', ['javascript', 'frontend']);

        $this->assertStringContainsString('<h2>', $content);
        $this->assertStringContainsString('</h2>', $content);
        $this->assertStringContainsString('<p>', $content);
        $this->assertStringContainsString('</p>', $content);
    }

    public function test_generate_content_includes_topic_from_title(): void
    {
        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('React 19 Announced', ['react', 'javascript']);

        $this->assertStringContainsString('React 19', $content);
    }

    public function test_generate_content_handles_empty_tags_array(): void
    {
        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('Python 3.12 Features', []);

        $this->assertIsString($content);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('<h2>', $content);
    }

    // ========== Content Length Validation Tests ==========

    public function test_generate_content_meets_minimum_word_count(): void
    {
        Config::set('import.content_generation.min_words', 500);
        Config::set('import.content_generation.max_words', 1500);

        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('TypeScript 5.0 Released', ['typescript', 'javascript']);

        $wordCount = str_word_count(strip_tags($content));

        $this->assertGreaterThanOrEqual(500, $wordCount);
    }

    public function test_generate_content_does_not_exceed_maximum_word_count(): void
    {
        Config::set('import.content_generation.min_words', 500);
        Config::set('import.content_generation.max_words', 1500);

        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('Vue 3 Composition API', ['vue', 'javascript', 'frontend']);

        $wordCount = str_word_count(strip_tags($content));

        $this->assertLessThanOrEqual(1500, $wordCount);
    }

    public function test_generate_content_respects_custom_word_count_config(): void
    {
        Config::set('import.content_generation.min_words', 300);
        Config::set('import.content_generation.max_words', 800);

        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('Docker Updates', ['docker', 'devops']);

        $wordCount = str_word_count(strip_tags($content));

        $this->assertGreaterThanOrEqual(300, $wordCount);
        $this->assertLessThanOrEqual(800, $wordCount);
    }

    // ========== HTML Formatting Tests ==========

    public function test_generate_content_has_proper_html_structure(): void
    {
        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('Kubernetes Best Practices', ['kubernetes', 'devops']);

        // Check for proper heading structure
        $this->assertMatchesRegularExpression('/<h2>.*<\/h2>/', $content);

        // Check for proper paragraph structure
        $this->assertMatchesRegularExpression('/<p>.*<\/p>/', $content);

        // Ensure no unclosed tags
        $h2Open = substr_count($content, '<h2>');
        $h2Close = substr_count($content, '</h2>');
        $this->assertEquals($h2Open, $h2Close);

        $pOpen = substr_count($content, '<p>');
        $pClose = substr_count($content, '</p>');
        $this->assertEquals($pOpen, $pClose);
    }

    public function test_generate_content_includes_multiple_sections(): void
    {
        $service = new NewsContentGeneratorService;

        $content = $service->generateContent('GraphQL vs REST', ['graphql', 'api', 'rest']);

        // Should have multiple h2 headings for different sections
        $h2Count = substr_count($content, '<h2>');
        $this->assertGreaterThanOrEqual(3, $h2Count);
    }

    // ========== Error Handling Tests ==========

    public function test_generate_content_returns_fallback_on_exception(): void
    {
        $service = new NewsContentGeneratorService;

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('error')->once();

        // Force an exception by mocking a method
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('sectionTemplates');
        $property->setAccessible(true);
        $property->setValue($service, []); // Empty templates to trigger fallback

        $content = $service->generateContent('Test Article', ['test']);

        $this->assertIsString($content);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Overview', $content);
    }

    public function test_generate_content_logs_successful_generation(): void
    {
        $service = new NewsContentGeneratorService;

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('info')->once()->with('Content generated', \Mockery::on(function ($context) {
            return isset($context['title']) &&
                   isset($context['word_count']) &&
                   isset($context['target_words']);
        }));

        $service->generateContent('Test Article', ['test']);
    }

    public function test_generate_content_logs_error_on_failure(): void
    {
        $service = new NewsContentGeneratorService;

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('error')->once()->with('Content generation failed', \Mockery::on(function ($context) {
            return isset($context['title']) && isset($context['error']);
        }));

        // Force an exception
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('sectionTemplates');
        $property->setAccessible(true);
        $property->setValue($service, []); // Empty templates to trigger fallback

        $service->generateContent('Test Article', ['test']);
    }

    // ========== Bulk Generation Tests ==========

    public function test_generate_bulk_processes_multiple_articles(): void
    {
        $service = new NewsContentGeneratorService;

        $articles = [
            ['title' => 'Article 1', 'tags' => ['tag1']],
            ['title' => 'Article 2', 'tags' => ['tag2']],
            ['title' => 'Article 3', 'tags' => ['tag3']],
        ];

        $results = $service->generateBulk($articles);

        $this->assertCount(3, $results);
        $this->assertArrayHasKey(0, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertArrayHasKey(2, $results);
    }

    public function test_generate_bulk_returns_content_for_each_article(): void
    {
        $service = new NewsContentGeneratorService;

        $articles = [
            ['title' => 'First Article', 'tags' => ['php']],
            ['title' => 'Second Article', 'tags' => ['javascript']],
        ];

        $results = $service->generateBulk($articles);

        foreach ($results as $content) {
            $this->assertIsString($content);
            $this->assertNotEmpty($content);
            $this->assertStringContainsString('<h2>', $content);
        }
    }

    public function test_generate_bulk_skips_articles_with_empty_title(): void
    {
        $service = new NewsContentGeneratorService;

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('warning')->once()->with('Skipping article with empty title', ['key' => 1]);
        Log::shouldReceive('info')->atLeast()->once();

        $articles = [
            ['title' => 'Valid Article', 'tags' => ['tag1']],
            ['title' => '', 'tags' => ['tag2']],
            ['title' => 'Another Valid Article', 'tags' => ['tag3']],
        ];

        $results = $service->generateBulk($articles);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey(0, $results);
        $this->assertArrayNotHasKey(1, $results);
        $this->assertArrayHasKey(2, $results);
    }

    public function test_generate_bulk_handles_missing_title_key(): void
    {
        $service = new NewsContentGeneratorService;

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')->atLeast()->once();

        $articles = [
            ['title' => 'Valid Article', 'tags' => ['tag1']],
            ['tags' => ['tag2']], // Missing title key
        ];

        $results = $service->generateBulk($articles);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey(0, $results);
    }

    public function test_generate_bulk_uses_fallback_on_individual_failure(): void
    {
        $service = new NewsContentGeneratorService;

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('error')->atLeast()->once();

        $articles = [
            ['title' => 'Valid Article', 'tags' => ['tag1']],
        ];

        // Force an exception for one article
        $reflection = new \ReflectionClass($service);
        $property = $reflection->getProperty('sectionTemplates');
        $property->setAccessible(true);
        $property->setValue($service, []); // Empty templates to trigger fallback

        $results = $service->generateBulk($articles);

        $this->assertCount(1, $results);
        $this->assertIsString($results[0]);
    }

    // ========== Fallback Content Tests ==========

    public function test_generate_fallback_content_returns_valid_html(): void
    {
        $service = new NewsContentGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateFallbackContent');
        $method->setAccessible(true);

        $content = $method->invoke($service, 'Test Article Title');

        $this->assertIsString($content);
        $this->assertStringContainsString('<h2>', $content);
        $this->assertStringContainsString('<p>', $content);
        $this->assertStringContainsString('Test Article Title', $content);
    }

    public function test_generate_fallback_content_has_minimum_structure(): void
    {
        $service = new NewsContentGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('generateFallbackContent');
        $method->setAccessible(true);

        $content = $method->invoke($service, 'Fallback Test');

        $this->assertStringContainsString('Overview', $content);
        $this->assertStringContainsString('Key Points', $content);
        $this->assertStringContainsString('Conclusion', $content);
    }

    // ========== Topic Extraction Tests ==========

    public function test_extract_topic_removes_common_prefixes(): void
    {
        $service = new NewsContentGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractTopic');
        $method->setAccessible(true);

        $this->assertEquals('Laravel 11', $method->invoke($service, 'Introducing Laravel 11'));
        $this->assertEquals('React Hooks', $method->invoke($service, 'Announcing React Hooks'));
        $this->assertEquals('TypeScript', $method->invoke($service, 'New TypeScript'));
    }

    public function test_extract_topic_removes_common_suffixes(): void
    {
        $service = new NewsContentGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractTopic');
        $method->setAccessible(true);

        $this->assertEquals('Vue 3', $method->invoke($service, 'Vue 3 Released'));
        $this->assertEquals('Docker', $method->invoke($service, 'Docker Announced'));
        $this->assertEquals('Python 3.12', $method->invoke($service, 'Python 3.12 Available'));
    }

    public function test_extract_topic_handles_title_without_prefixes_or_suffixes(): void
    {
        $service = new NewsContentGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractTopic');
        $method->setAccessible(true);

        $this->assertEquals('GraphQL Best Practices', $method->invoke($service, 'GraphQL Best Practices'));
    }
}

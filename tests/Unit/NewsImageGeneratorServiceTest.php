<?php

namespace Tests\Unit;

use App\Services\NewsImageGeneratorService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NewsImageGeneratorServiceTest extends TestCase
{
    // ========== Image URL Generation Tests ==========

    public function test_assign_image_returns_array_with_path_and_alt(): void
    {
        Config::set('import.image_generation.service', 'unsplash');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Laravel 11 Released', ['php', 'laravel']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('alt', $result);
        $this->assertIsString($result['path']);
        $this->assertIsString($result['alt']);
    }

    public function test_assign_image_generates_unsplash_url(): void
    {
        Config::set('import.image_generation.service', 'unsplash');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('React 19 Features', ['react', 'javascript']);

        $this->assertStringContainsString('source.unsplash.com', $result['path']);
        $this->assertStringContainsString('1200x630', $result['path']);
    }

    public function test_assign_image_generates_picsum_url(): void
    {
        Config::set('import.image_generation.service', 'picsum');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Python 3.12 Updates', ['python']);

        $this->assertStringContainsString('picsum.photos', $result['path']);
        $this->assertStringContainsString('1200/630', $result['path']);
    }

    public function test_assign_image_uses_local_image_for_known_tags(): void
    {
        Config::set('import.image_generation.service', 'local');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Laravel Best Practices', ['laravel', 'php']);

        $this->assertStringContainsString('images/tech/laravel.jpg', $result['path']);
    }

    public function test_assign_image_uses_fallback_for_unknown_local_tags(): void
    {
        Config::set('import.image_generation.service', 'local');
        Config::set('import.image_generation.fallback_image', 'images/default-post.jpg');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Unknown Topic', ['unknown-tag']);

        $this->assertEquals('images/default-post.jpg', $result['path']);
    }

    public function test_assign_image_uses_fallback_for_invalid_service(): void
    {
        Config::set('import.image_generation.service', 'invalid-service');
        Config::set('import.image_generation.fallback_image', 'images/default-post.jpg');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Test Article', ['test']);

        $this->assertEquals('images/default-post.jpg', $result['path']);
    }

    public function test_assign_image_handles_empty_tags_array(): void
    {
        Config::set('import.image_generation.service', 'unsplash');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Article Without Tags', []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('alt', $result);
        $this->assertNotEmpty($result['path']);
    }

    // ========== Alt Text Generation Tests ==========

    public function test_generate_alt_text_returns_string(): void
    {
        $service = new NewsImageGeneratorService;

        $altText = $service->generateAltText('Laravel 11 Released');

        $this->assertIsString($altText);
        $this->assertNotEmpty($altText);
    }

    public function test_generate_alt_text_includes_title(): void
    {
        $service = new NewsImageGeneratorService;

        $altText = $service->generateAltText('React 19 Features');

        $this->assertStringContainsString('React 19 Features', $altText);
    }

    public function test_generate_alt_text_adds_context_prefix(): void
    {
        $service = new NewsImageGeneratorService;

        $altText = $service->generateAltText('Python Updates');

        $this->assertStringContainsString('Featured image for:', $altText);
    }

    public function test_generate_alt_text_does_not_add_prefix_when_already_descriptive(): void
    {
        $service = new NewsImageGeneratorService;

        $altText = $service->generateAltText('Image showing Laravel architecture');

        $this->assertStringNotContainsString('Featured image for:', $altText);
        $this->assertStringContainsString('Image showing Laravel architecture', $altText);
    }

    public function test_generate_alt_text_limits_length(): void
    {
        $service = new NewsImageGeneratorService;

        $longTitle = str_repeat('Very Long Title ', 20);
        $altText = $service->generateAltText($longTitle);

        $this->assertLessThanOrEqual(120, strlen($altText)); // 100 char limit + prefix
    }

    public function test_generate_alt_text_trims_whitespace(): void
    {
        $service = new NewsImageGeneratorService;

        $altText = $service->generateAltText('  Title with spaces  ');

        $this->assertEquals(trim($altText), $altText);
        $this->assertStringContainsString('Title with spaces', $altText);
    }

    public function test_generate_alt_text_handles_empty_title(): void
    {
        $service = new NewsImageGeneratorService;

        $altText = $service->generateAltText('');

        $this->assertIsString($altText);
        $this->assertNotEmpty($altText);
    }

    // ========== Fallback Behavior Tests ==========

    public function test_assign_image_uses_fallback_on_exception(): void
    {
        Config::set('import.image_generation.service', 'unsplash');
        Config::set('import.image_generation.fallback_image', 'images/default-post.jpg');

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('warning')->once()->with('Image generation failed', \Mockery::on(function ($context) {
            return isset($context['title']) &&
                   isset($context['service']) &&
                   isset($context['error']);
        }));

        $service = new NewsImageGeneratorService;

        // Force an exception by using reflection to break the service
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getUnsplashImage');
        $method->setAccessible(true);

        // Mock the method to throw an exception
        $mockService = $this->getMockBuilder(NewsImageGeneratorService::class)
            ->onlyMethods(['getUnsplashImage'])
            ->getMock();

        $mockService->method('getUnsplashImage')
            ->willThrowException(new \Exception('Test exception'));

        $result = $mockService->assignImage('Test Article', ['test']);

        $this->assertEquals('images/default-post.jpg', $result['path']);
        $this->assertArrayHasKey('alt', $result);
    }

    public function test_assign_image_logs_warning_on_failure(): void
    {
        Config::set('import.image_generation.service', 'unsplash');
        Config::set('import.image_generation.fallback_image', 'images/default-post.jpg');

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $mockService = $this->getMockBuilder(NewsImageGeneratorService::class)
            ->onlyMethods(['getUnsplashImage'])
            ->getMock();

        $mockService->method('getUnsplashImage')
            ->willThrowException(new \Exception('Service unavailable'));

        $result = $mockService->assignImage('Test Article', ['test']);

        $this->assertIsArray($result);
    }

    public function test_assign_image_continues_with_fallback_after_error(): void
    {
        Config::set('import.image_generation.service', 'unsplash');
        Config::set('import.image_generation.fallback_image', 'images/fallback.jpg');

        Log::shouldReceive('channel')->with('import')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $mockService = $this->getMockBuilder(NewsImageGeneratorService::class)
            ->onlyMethods(['getUnsplashImage'])
            ->getMock();

        $mockService->method('getUnsplashImage')
            ->willThrowException(new \Exception('Network error'));

        $result = $mockService->assignImage('Article Title', ['tag']);

        $this->assertEquals('images/fallback.jpg', $result['path']);
        $this->assertStringContainsString('Article Title', $result['alt']);
    }

    // ========== Bulk Assignment Tests ==========

    public function test_assign_bulk_processes_multiple_articles(): void
    {
        Config::set('import.image_generation.service', 'picsum');

        $service = new NewsImageGeneratorService;

        $articles = [
            1 => ['title' => 'Article 1', 'tags' => ['tag1']],
            2 => ['title' => 'Article 2', 'tags' => ['tag2']],
            3 => ['title' => 'Article 3', 'tags' => ['tag3']],
        ];

        $results = $service->assignBulk($articles);

        $this->assertCount(3, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertArrayHasKey(2, $results);
        $this->assertArrayHasKey(3, $results);
    }

    public function test_assign_bulk_returns_valid_data_for_each_article(): void
    {
        Config::set('import.image_generation.service', 'unsplash');

        $service = new NewsImageGeneratorService;

        $articles = [
            10 => ['title' => 'First Article', 'tags' => ['php']],
            20 => ['title' => 'Second Article', 'tags' => ['javascript']],
        ];

        $results = $service->assignBulk($articles);

        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('path', $result);
            $this->assertArrayHasKey('alt', $result);
            $this->assertNotEmpty($result['path']);
            $this->assertNotEmpty($result['alt']);
        }
    }

    public function test_assign_bulk_handles_missing_title_key(): void
    {
        Config::set('import.image_generation.service', 'picsum');

        $service = new NewsImageGeneratorService;

        $articles = [
            1 => ['title' => 'Valid Article', 'tags' => ['tag1']],
            2 => ['tags' => ['tag2']], // Missing title
        ];

        $results = $service->assignBulk($articles);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey(1, $results);
        $this->assertArrayHasKey(2, $results);
        $this->assertNotEmpty($results[2]['path']);
    }

    public function test_assign_bulk_handles_missing_tags_key(): void
    {
        Config::set('import.image_generation.service', 'unsplash');

        $service = new NewsImageGeneratorService;

        $articles = [
            1 => ['title' => 'Article Without Tags'],
        ];

        $results = $service->assignBulk($articles);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey('path', $results[1]);
        $this->assertArrayHasKey('alt', $results[1]);
    }

    // ========== Keyword Extraction Tests ==========

    public function test_extract_keywords_includes_tags(): void
    {
        $service = new NewsImageGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractKeywords');
        $method->setAccessible(true);

        $keywords = $method->invoke($service, 'Test Title', ['laravel', 'php']);

        $this->assertContains('laravel', $keywords);
        $this->assertContains('php', $keywords);
    }

    public function test_extract_keywords_filters_common_words(): void
    {
        $service = new NewsImageGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractKeywords');
        $method->setAccessible(true);

        $keywords = $method->invoke($service, 'The best way to learn programming', []);

        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('to', $keywords);
        $this->assertContains('best', $keywords);
        $this->assertContains('learn', $keywords);
        $this->assertContains('programming', $keywords);
    }

    public function test_extract_keywords_removes_short_words(): void
    {
        $service = new NewsImageGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractKeywords');
        $method->setAccessible(true);

        $keywords = $method->invoke($service, 'AI is the new era', []);

        $this->assertNotContains('ai', $keywords);
        $this->assertNotContains('is', $keywords);
        $this->assertNotContains('the', $keywords);
        $this->assertNotContains('new', $keywords);
    }

    public function test_extract_keywords_returns_unique_values(): void
    {
        $service = new NewsImageGeneratorService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractKeywords');
        $method->setAccessible(true);

        $keywords = $method->invoke($service, 'Laravel Laravel Framework', ['laravel']);

        $this->assertEquals(count($keywords), count(array_unique($keywords)));
    }

    // ========== Service-Specific URL Tests ==========

    public function test_unsplash_url_includes_keywords_from_tags(): void
    {
        Config::set('import.image_generation.service', 'unsplash');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Article Title', ['docker', 'kubernetes']);

        $this->assertStringContainsString('docker', $result['path']);
    }

    public function test_picsum_url_includes_seed_for_consistency(): void
    {
        Config::set('import.image_generation.service', 'picsum');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Test Article', ['test']);

        $this->assertMatchesRegularExpression('/seed\/\d+/', $result['path']);
    }

    public function test_local_image_maps_javascript_tag(): void
    {
        Config::set('import.image_generation.service', 'local');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('JS Article', ['javascript']);

        $this->assertEquals('images/tech/javascript.jpg', $result['path']);
    }

    public function test_local_image_maps_python_tag(): void
    {
        Config::set('import.image_generation.service', 'local');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Python Article', ['python']);

        $this->assertEquals('images/tech/python.jpg', $result['path']);
    }

    public function test_local_image_maps_docker_tag(): void
    {
        Config::set('import.image_generation.service', 'local');

        $service = new NewsImageGeneratorService;

        $result = $service->assignImage('Docker Article', ['docker']);

        $this->assertEquals('images/tech/docker.jpg', $result['path']);
    }
}

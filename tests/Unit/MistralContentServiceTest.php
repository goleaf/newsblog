<?php

namespace Tests\Unit;

use App\Services\MistralContentService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MistralContentServiceTest extends TestCase
{
    // ========== Constructor Tests ==========

    public function test_constructor_throws_exception_when_api_key_is_missing(): void
    {
        Config::set('mistral.api_key', '');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');

        new MistralContentService;
    }

    public function test_constructor_throws_exception_when_api_key_is_null(): void
    {
        Config::set('mistral.api_key', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');

        new MistralContentService;
    }

    // ========== Prompt Building Tests ==========

    public function test_build_prompt_includes_title(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', null);

        $this->assertStringContainsString('Test Article Title', $prompt);
        $this->assertStringContainsString('Write a comprehensive technical article about: Test Article Title', $prompt);
    }

    public function test_build_prompt_includes_category_when_provided(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', 'Technology');

        $this->assertStringContainsString('Technology', $prompt);
        $this->assertStringContainsString('This article is in the Technology category', $prompt);
    }

    public function test_build_prompt_excludes_category_when_null(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', null);

        $this->assertStringNotContainsString('This article is in the', $prompt);
    }

    public function test_build_prompt_includes_markdown_requirements(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', null);

        $this->assertStringContainsString('Write in markdown format', $prompt);
        $this->assertStringContainsString('Include a clear introduction', $prompt);
        $this->assertStringContainsString('Organize content with appropriate headings', $prompt);
        $this->assertStringContainsString('Provide technical details and examples', $prompt);
        $this->assertStringContainsString('Include a conclusion', $prompt);
    }

    // ========== Markdown Validation Tests ==========

    public function test_validate_markdown_returns_false_for_empty_content(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $result = $method->invoke($service, '');

        $this->assertFalse($result);
    }

    public function test_validate_markdown_returns_false_for_content_shorter_than_100_characters(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'Short content');

        $this->assertFalse($result);
    }

    public function test_validate_markdown_returns_true_for_valid_markdown_with_headers(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $content = "# Introduction\n\nThis is a comprehensive article about testing.\n\n## Main Section\n\nContent here with more details.\n\n## Conclusion\n\nFinal thoughts.";

        $result = $method->invoke($service, $content);

        $this->assertTrue($result);
    }

    public function test_validate_markdown_returns_true_for_valid_markdown_with_paragraphs(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $content = str_repeat('This is a paragraph with enough content. ', 10)."\n\n".str_repeat('Another paragraph. ', 10);

        $result = $method->invoke($service, $content);

        $this->assertTrue($result);
    }

    public function test_validate_markdown_validates_markdown_header_patterns(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $contentWithH1 = "# Header\n\n".str_repeat('Content. ', 20);
        $contentWithH2 = "## Header\n\n".str_repeat('Content. ', 20);
        $contentWithH6 = "###### Header\n\n".str_repeat('Content. ', 20);

        $this->assertTrue($method->invoke($service, $contentWithH1));
        $this->assertTrue($method->invoke($service, $contentWithH2));
        $this->assertTrue($method->invoke($service, $contentWithH6));
    }

    public function test_validate_markdown_returns_false_for_content_without_structure(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        // Content with 100+ chars but no markdown structure
        $content = str_repeat('a', 150);

        $result = $method->invoke($service, $content);

        $this->assertFalse($result);
    }

    // ========== Retry Logic Tests ==========

    public function test_retry_with_backoff_successful_call_on_first_attempt(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $callback = function () {
            return 'success';
        };

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
    }

    public function test_retry_with_backoff_retries_on_failure_then_succeeds(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        Config::set('mistral.retry_delay', 1); // Set to 1ms for faster tests
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $attempts = 0;
        $callback = function () use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new \RuntimeException('First attempt failed');
            }

            return 'success';
        };

        Log::shouldReceive('channel')->with('mistral')->andReturnSelf();
        Log::shouldReceive('warning')->once();

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
        $this->assertEquals(2, $attempts);
    }

    public function test_retry_with_backoff_throws_exception_after_max_retries(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        Config::set('mistral.retry_delay', 1); // Set to 1ms for faster tests
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $exception = new \RuntimeException('Final failure');
        $callback = function () use ($exception) {
            throw $exception;
        };

        Log::shouldReceive('channel')->with('mistral')->andReturnSelf();
        Log::shouldReceive('warning')->times(2);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Final failure');

        $method->invoke($service, $callback, 3);
    }

    public function test_retry_with_backoff_uses_exponential_backoff(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        Config::set('mistral.retry_delay', 100); // 100ms base delay
        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $attempts = 0;
        $callback = function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \RuntimeException("Attempt {$attempts} failed");
            }

            return 'success';
        };

        Log::shouldReceive('channel')->with('mistral')->andReturnSelf();
        Log::shouldReceive('warning')->with('API call failed, retrying', \Mockery::on(function ($context) {
            // Verify that delay_ms is present in the log context
            return isset($context['delay_ms']) && isset($context['attempt']);
        }))->twice();

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempts);
    }
}

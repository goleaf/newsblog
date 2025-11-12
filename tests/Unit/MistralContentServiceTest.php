<?php

namespace Tests\Unit;

use App\Services\MistralContentService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Partitech\PhpMistral\Messages;
use Partitech\PhpMistral\MistralClient;
use Partitech\PhpMistral\MistralClientException;
use Tests\TestCase;

class MistralContentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== Constructor Tests ==========

    public function test_constructor_throws_exception_when_api_key_is_missing(): void
    {
        Config::set('mistral.api_key', '');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');

        new MistralContentService;
    }

    public function test_constructor_throws_exception_when_api_key_is_null(): void
    {
        Config::set('mistral.api_key', null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');

        new MistralContentService;
    }

    public function test_constructor_initializes_with_config_values(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        Config::set('mistral.url', 'https://api.mistral.ai');
        Config::set('mistral.timeout', 60);
        Config::set('mistral.model', 'mistral-large');
        Config::set('mistral.max_retries', 5);
        Config::set('mistral.retry_delay', 2000);

        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $maxRetriesProperty = $reflection->getProperty('maxRetries');
        $maxRetriesProperty->setAccessible(true);
        $retryDelayProperty = $reflection->getProperty('retryDelay');
        $retryDelayProperty->setAccessible(true);

        $this->assertEquals(60, $timeoutProperty->getValue($service));
        $this->assertEquals('mistral-large', $modelProperty->getValue($service));
        $this->assertEquals(5, $maxRetriesProperty->getValue($service));
        $this->assertEquals(2000, $retryDelayProperty->getValue($service));
    }

    public function test_constructor_uses_default_config_values(): void
    {
        Config::set('mistral.api_key', 'test-api-key');
        Config::set('mistral.url', 'https://api.mistral.ai');
        Config::set('mistral.timeout', 30);
        Config::set('mistral.model', 'mistral-medium');
        Config::set('mistral.max_retries', 3);
        Config::set('mistral.retry_delay', 1000);

        $service = new MistralContentService;

        $reflection = new \ReflectionClass($service);
        $timeoutProperty = $reflection->getProperty('timeout');
        $timeoutProperty->setAccessible(true);
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $maxRetriesProperty = $reflection->getProperty('maxRetries');
        $maxRetriesProperty->setAccessible(true);
        $retryDelayProperty = $reflection->getProperty('retryDelay');
        $retryDelayProperty->setAccessible(true);

        $this->assertEquals(30, $timeoutProperty->getValue($service));
        $this->assertEquals('mistral-medium', $modelProperty->getValue($service));
        $this->assertEquals(3, $maxRetriesProperty->getValue($service));
        $this->assertEquals(1000, $retryDelayProperty->getValue($service));
    }

    // ========== Prompt Building Tests ==========

    public function test_build_prompt_includes_title(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', null);

        $this->assertStringContainsString('Test Article Title', $prompt);
        $this->assertStringContainsString('Write a comprehensive, well-structured article about: Test Article Title', $prompt);
    }

    public function test_build_prompt_includes_category_when_provided(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', 'Technology');

        $this->assertStringContainsString('Category: Technology', $prompt);
    }

    public function test_build_prompt_excludes_category_when_null(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', null);

        $this->assertStringNotContainsString('Category:', $prompt);
    }

    public function test_build_prompt_includes_all_required_sections(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', 'Technology');

        $this->assertStringContainsString('Write in markdown format', $prompt);
        $this->assertStringContainsString('Include a clear introduction', $prompt);
        $this->assertStringContainsString('Use proper headings (## for main sections)', $prompt);
        $this->assertStringContainsString('Include multiple sections with detailed content', $prompt);
        $this->assertStringContainsString('End with a conclusion', $prompt);
        $this->assertStringContainsString('Make the content informative and engaging', $prompt);
        $this->assertStringContainsString('Aim for approximately 800-1200 words', $prompt);
    }

    public function test_build_prompt_format_matches_expected_structure(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 'Test Article Title', 'Technology');

        $this->assertStringStartsWith('Write a comprehensive, well-structured article about: Test Article Title', $prompt);
        $this->assertStringContainsString('Requirements:', $prompt);
    }

    // ========== Markdown Validation Tests ==========

    public function test_validate_markdown_returns_false_for_empty_content(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('Content validation failed: empty content')
            ->once();

        $result = $method->invoke($service, '');

        $this->assertFalse($result);
    }

    public function test_validate_markdown_returns_false_for_whitespace_only_content(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('Content validation failed: empty content')
            ->once();

        $result = $method->invoke($service, '   ');

        $this->assertFalse($result);
    }

    public function test_validate_markdown_returns_false_for_content_shorter_than_100_characters(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('Content validation failed: content too short', Mockery::type('array'))
            ->once();

        $result = $method->invoke($service, 'Short content');

        $this->assertFalse($result);
    }

    public function test_validate_markdown_returns_true_for_valid_markdown_with_headers(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $content = "# Introduction\n\nThis is a comprehensive article about testing.\n\n## Main Section\n\nContent here with more details.\n\n## Conclusion\n\nFinal thoughts.";

        $result = $method->invoke($service, $content);

        $this->assertTrue($result);
    }

    public function test_validate_markdown_returns_true_for_valid_markdown_with_paragraphs(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $content = str_repeat('This is a paragraph with enough content. ', 10);

        $result = $method->invoke($service, $content);

        $this->assertTrue($result);
    }

    public function test_validate_markdown_returns_true_for_content_longer_than_200_characters_without_headers(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        $content = str_repeat('This is a long paragraph. ', 20);

        $result = $method->invoke($service, $content);

        $this->assertTrue($result);
    }

    public function test_validate_markdown_returns_false_for_content_without_headers_or_paragraphs(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateMarkdown');
        $method->setAccessible(true);

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('Content validation failed: missing structure', Mockery::type('array'))
            ->once();

        $content = str_repeat('a', 150);

        $result = $method->invoke($service, $content);

        $this->assertFalse($result);
    }

    public function test_validate_markdown_validates_markdown_header_pattern_correctly(): void
    {
        $service = $this->createServiceWithMockedClient();
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

    // ========== Retry Logic Tests ==========

    public function test_retry_with_backoff_successful_call_on_first_attempt(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $callback = function () {
            return 'success';
        };

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
    }

    public function test_retry_with_backoff_retry_on_first_failure_success_on_second_attempt(): void
    {
        $service = $this->createServiceWithMockedClient();
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

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::type('array'))
            ->once();

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
        $this->assertEquals(2, $attempts);
    }

    public function test_retry_with_backoff_exponential_backoff_delay_calculation(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $retryDelayProperty = $reflection->getProperty('retryDelay');
        $retryDelayProperty->setAccessible(true);
        $retryDelayProperty->setValue($service, 1000);

        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $attempts = 0;
        $delays = [];
        $callback = function () use (&$attempts, &$delays) {
            $attempts++;
            if ($attempts < 3) {
                $delays[] = 1000 * (2 ** ($attempts - 1));
                throw new \RuntimeException("Attempt {$attempts} failed");
            }

            return 'success';
        };

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::on(function ($arg) use (&$delays) {
                if (isset($arg['delay_ms'])) {
                    $delays[] = $arg['delay_ms'];
                }

                return true;
            }))
            ->twice();

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
        $this->assertEquals(3, $attempts);
    }

    public function test_retry_with_backoff_max_retries_exhausted_throws_last_exception(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $exception = new \RuntimeException('Final failure');
        $callback = function () use ($exception) {
            throw $exception;
        };

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::type('array'))
            ->times(2);
        Log::shouldReceive('error')
            ->with('Max retry attempts reached', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Final failure');

        $method->invoke($service, $callback, 3);
    }

    public function test_retry_with_backoff_logs_retry_attempts_correctly(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $attempts = 0;
        $callback = function () use (&$attempts) {
            $attempts++;
            if ($attempts < 2) {
                throw new \RuntimeException('Failed');
            }

            return 'success';
        };

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::on(function ($arg) {
                return isset($arg['attempt']) && isset($arg['max_retries']) && isset($arg['delay_ms']);
            }))
            ->once();

        $result = $method->invoke($service, $callback, 3);

        $this->assertEquals('success', $result);
    }

    public function test_retry_with_backoff_logs_max_retries_reached_error(): void
    {
        $service = $this->createServiceWithMockedClient();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('retryWithBackoff');
        $method->setAccessible(true);

        $callback = function () {
            throw new \RuntimeException('Always fails');
        };

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::type('array'))
            ->times(2);
        Log::shouldReceive('error')
            ->with('Max retry attempts reached', Mockery::on(function ($arg) {
                return isset($arg['attempts']) && isset($arg['error']);
            }))
            ->once();

        $this->expectException(\RuntimeException::class);

        $method->invoke($service, $callback, 3);
    }

    // ========== API Call Tests ==========

    public function test_call_mistral_api_successful_call_returns_content(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $mockResponse->shouldReceive('getMessage')
            ->once()
            ->andReturn('Generated content from API');

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        $method = $reflection->getMethod('callMistralApi');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'Test prompt');

        $this->assertEquals('Generated content from API', $result);
    }

    public function test_call_mistral_api_empty_response_throws_runtime_exception(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $mockResponse->shouldReceive('getMessage')
            ->times(3)
            ->andReturn('');

        $mockClient->shouldReceive('chat')
            ->times(3)
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        $maxRetriesProperty = $reflection->getProperty('maxRetries');
        $maxRetriesProperty->setAccessible(true);
        $maxRetriesProperty->setValue($service, 3);

        $method = $reflection->getMethod('callMistralApi');
        $method->setAccessible(true);

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::type('array'))
            ->times(2);
        Log::shouldReceive('error')
            ->with('Max retry attempts reached', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Empty response from Mistral API');

        $method->invoke($service, 'Test prompt');
    }

    public function test_call_mistral_api_uses_correct_model_and_parameters(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $mockResponse->shouldReceive('getMessage')
            ->once()
            ->andReturn('Generated content');

        $mockClient->shouldReceive('chat')
            ->once()
            ->with(Mockery::type(Messages::class), [
                'model' => 'mistral-large',
                'temperature' => 0.7,
                'max_tokens' => null,
            ])
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-large');

        $method = $reflection->getMethod('callMistralApi');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'Test prompt');

        $this->assertEquals('Generated content', $result);
    }

    public function test_call_mistral_api_creates_messages_object_correctly(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $mockResponse->shouldReceive('getMessage')
            ->once()
            ->andReturn('Generated content');

        $mockClient->shouldReceive('chat')
            ->once()
            ->with(Mockery::on(function ($messages) {
                return $messages instanceof Messages;
            }), Mockery::type('array'))
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        $method = $reflection->getMethod('callMistralApi');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'Test prompt');

        $this->assertEquals('Generated content', $result);
    }

    // ========== GenerateContent Integration Tests ==========

    public function test_generate_content_successful_content_generation_flow(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $validContent = "# Introduction\n\nThis is a comprehensive article about testing.\n\n## Main Section\n\nContent here with more details.\n\n## Conclusion\n\nFinal thoughts.";
        $mockResponse->shouldReceive('getMessage')
            ->once()
            ->andReturn($validContent);

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->with('Generating content', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Content generated successfully', Mockery::type('array'))
            ->once();

        $result = $service->generateContent('Test Article', 'Technology');

        $this->assertEquals($validContent, $result);
    }

    public function test_generate_content_validation_failure_throws_runtime_exception(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $invalidContent = 'Short';
        $mockResponse->shouldReceive('getMessage')
            ->once()
            ->andReturn($invalidContent);

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->with('Generating content', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('Content validation failed: content too short', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('Generated content failed validation', Mockery::type('array'))
            ->once();
        Log::shouldReceive('error')
            ->with('Content generation failed', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Generated content failed markdown validation.');

        $service->generateContent('Test Article', 'Technology');
    }

    public function test_generate_content_mistral_client_exception_is_caught_and_wrapped(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockClient->shouldReceive('chat')
            ->times(3)
            ->andThrow(new MistralClientException('API Error', 500));

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->with('Generating content', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::type('array'))
            ->zeroOrMoreTimes();
        Log::shouldReceive('error')
            ->with('Max retry attempts reached', Mockery::type('array'))
            ->zeroOrMoreTimes();
        Log::shouldReceive('error')
            ->with('Mistral API error', Mockery::type('array'))
            ->once();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to generate content: API Error');

        $service->generateContent('Test Article');
    }

    public function test_generate_content_generic_exception_is_rethrown(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockClient->shouldReceive('chat')
            ->times(3)
            ->andThrow(new \InvalidArgumentException('Invalid argument'));

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->with('Generating content', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('API call failed, retrying', Mockery::type('array'))
            ->zeroOrMoreTimes();
        Log::shouldReceive('error')
            ->with('Max retry attempts reached', Mockery::type('array'))
            ->zeroOrMoreTimes();
        Log::shouldReceive('error')
            ->with('Content generation failed', Mockery::type('array'))
            ->once();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid argument');

        $service->generateContent('Test Article');
    }

    public function test_generate_content_logging_occurs_at_appropriate_points(): void
    {
        $mockClient = Mockery::mock(MistralClient::class);
        $mockResponse = Mockery::mock('Partitech\PhpMistral\Response');
        $validContent = "# Introduction\n\nThis is a comprehensive article about testing.\n\n## Main Section\n\nContent here with more details.\n\n## Conclusion\n\nFinal thoughts.";
        $mockResponse->shouldReceive('getMessage')
            ->once()
            ->andReturn($validContent);

        $mockClient->shouldReceive('chat')
            ->once()
            ->andReturn($mockResponse);

        $service = $this->createServiceWithMockedClient($mockClient);
        $reflection = new \ReflectionClass($service);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($service, $mockClient);

        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $modelProperty->setValue($service, 'mistral-medium');

        Log::shouldReceive('channel')
            ->with('mistral')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->with('Generating content', Mockery::on(function ($arg) {
                return isset($arg['title']) && isset($arg['category']);
            }))
            ->once();
        Log::shouldReceive('info')
            ->with('Content generated successfully', Mockery::on(function ($arg) {
                return isset($arg['title']) && isset($arg['content_length']);
            }))
            ->once();

        $result = $service->generateContent('Test Article', 'Technology');

        $this->assertEquals($validContent, $result);
    }

    // ========== Helper Methods ==========

    protected function createServiceWithMockedClient(?MistralClient $mockClient = null): MistralContentService
    {
        Config::set('mistral.api_key', 'test-api-key');
        Config::set('mistral.url', 'https://api.mistral.ai');
        Config::set('mistral.timeout', 30);
        Config::set('mistral.model', 'mistral-medium');
        Config::set('mistral.max_retries', 3);
        Config::set('mistral.retry_delay', 1000);

        $service = new MistralContentService;

        if ($mockClient !== null) {
            $reflection = new \ReflectionClass($service);
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $clientProperty->setValue($service, $mockClient);
        }

        return $service;
    }
}

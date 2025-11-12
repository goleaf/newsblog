<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Partitech\PhpMistral\Messages;
use Partitech\PhpMistral\MistralClient;
use Partitech\PhpMistral\MistralClientException;

class MistralContentService
{
    protected MistralClient $client;

    protected string $model;

    protected int $timeout;

    protected int $maxRetries;

    protected int $retryDelay;

    public function __construct()
    {
        $apiKey = config('mistral.api_key');
        $url = config('mistral.url');
        $this->timeout = (int) config('mistral.timeout', 30);
        $this->model = config('mistral.model', 'mistral-medium');
        $this->maxRetries = (int) config('mistral.max_retries', 3);
        $this->retryDelay = (int) config('mistral.retry_delay', 1000);

        if (empty($apiKey)) {
            throw new \InvalidArgumentException('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');
        }

        $this->client = new MistralClient($apiKey, $url, $this->timeout);
    }

    /**
     * Generate content for a post based on title and optional category.
     *
     * @param  string  $title  Post title
     * @param  string|null  $category  Optional category name
     * @return string Generated markdown content
     *
     * @throws \RuntimeException If content generation fails
     */
    public function generateContent(string $title, ?string $category = null): string
    {
        try {
            $prompt = $this->buildPrompt($title, $category);

            Log::channel('mistral')->info('Generating content', [
                'title' => $title,
                'category' => $category,
            ]);

            $content = $this->callMistralApi($prompt);

            if (! $this->validateMarkdown($content)) {
                Log::channel('mistral')->warning('Generated content failed validation', [
                    'title' => $title,
                    'content_length' => strlen($content),
                ]);

                throw new \RuntimeException('Generated content failed markdown validation.');
            }

            Log::channel('mistral')->info('Content generated successfully', [
                'title' => $title,
                'content_length' => strlen($content),
            ]);

            return $content;
        } catch (MistralClientException $e) {
            Log::channel('mistral')->error('Mistral API error', [
                'title' => $title,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new \RuntimeException('Failed to generate content: '.$e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            Log::channel('mistral')->error('Content generation failed', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build a prompt for content generation based on title and category.
     *
     * @param  string  $title  Post title
     * @param  string|null  $category  Optional category name
     * @return string Formatted prompt
     */
    protected function buildPrompt(string $title, ?string $category = null): string
    {
        $prompt = "Write a comprehensive, well-structured article about: {$title}\n\n";

        if ($category !== null) {
            $prompt .= "Category: {$category}\n\n";
        }

        $prompt .= "Requirements:\n";
        $prompt .= "- Write in markdown format\n";
        $prompt .= "- Include a clear introduction\n";
        $prompt .= "- Use proper headings (## for main sections)\n";
        $prompt .= "- Include multiple sections with detailed content\n";
        $prompt .= "- End with a conclusion\n";
        $prompt .= "- Make the content informative and engaging\n";
        $prompt .= "- Aim for approximately 800-1200 words\n";

        return $prompt;
    }

    /**
     * Call the Mistral API with retry logic.
     *
     * @param  string  $prompt  The prompt to send
     * @return string Generated content
     *
     * @throws MistralClientException If API call fails after retries
     */
    protected function callMistralApi(string $prompt): string
    {
        return $this->retryWithBackoff(function () use ($prompt) {
            $messages = new Messages;
            $messages->addUserMessage($prompt);

            $params = [
                'model' => $this->model,
                'temperature' => 0.7,
                'max_tokens' => null,
            ];

            $response = $this->client->chat($messages, $params);
            $content = $response->getMessage();

            if (empty($content)) {
                throw new \RuntimeException('Empty response from Mistral API');
            }

            return $content;
        }, $this->maxRetries);
    }

    /**
     * Retry a callable operation with exponential backoff.
     *
     * @param  callable  $callback  Operation to retry
     * @param  int  $maxRetries  Maximum number of retry attempts
     * @return mixed Result of the callback
     *
     * @throws \Exception If all retries are exhausted
     */
    protected function retryWithBackoff(callable $callback, int $maxRetries): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $attempt++;
                $lastException = $e;

                if ($attempt >= $maxRetries) {
                    Log::channel('mistral')->error('Max retry attempts reached', [
                        'attempts' => $attempt,
                        'error' => $e->getMessage(),
                    ]);

                    break;
                }

                $delay = $this->retryDelay * (2 ** ($attempt - 1));

                Log::channel('mistral')->warning('API call failed, retrying', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage(),
                ]);

                usleep($delay * 1000);
            }
        }

        throw $lastException ?? new \RuntimeException('Retry logic failed without exception');
    }

    /**
     * Validate that content is valid markdown format.
     *
     * @param  string  $content  Content to validate
     * @return bool True if valid, false otherwise
     */
    protected function validateMarkdown(string $content): bool
    {
        if (empty(trim($content))) {
            Log::channel('mistral')->warning('Content validation failed: empty content');

            return false;
        }

        if (strlen($content) < 100) {
            Log::channel('mistral')->warning('Content validation failed: content too short', [
                'length' => strlen($content),
            ]);

            return false;
        }

        $hasHeaders = preg_match('/^#{1,6}\s+.+$/m', $content) === 1;
        $hasParagraphs = preg_match('/\n\n/', $content) === 1 || strlen($content) > 200;

        if (! $hasHeaders && ! $hasParagraphs) {
            Log::channel('mistral')->warning('Content validation failed: missing structure', [
                'has_headers' => $hasHeaders,
                'has_paragraphs' => $hasParagraphs,
            ]);

            return false;
        }

        return true;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Partitech\PhpMistral\MistralClient;
use Partitech\PhpMistral\Resources\ChatResource;

class MistralContentService
{
    protected MistralClient $client;

    protected int $maxRetries;

    protected int $retryDelay;

    /**
     * Initialize the Mistral AI client with configuration.
     */
    public function __construct()
    {
        $apiKey = config('mistral.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');
        }

        $this->client = new MistralClient($apiKey);
        $this->maxRetries = config('mistral.max_retries', 3);
        $this->retryDelay = config('mistral.retry_delay', 1000);
    }

    /**
     * Generate article content based on title and optional category.
     */
    public function generateContent(string $title, ?string $category = null): string
    {
        $prompt = $this->buildPrompt($title, $category);

        try {
            $content = $this->retryWithBackoff(function () use ($prompt) {
                return $this->callMistralApi($prompt);
            }, $this->maxRetries);

            if (! $this->validateMarkdown($content)) {
                Log::channel('mistral')->warning('Generated content failed markdown validation', [
                    'title' => $title,
                    'category' => $category,
                ]);

                throw new \RuntimeException('Generated content is not valid markdown');
            }

            Log::channel('mistral')->info('Content generated successfully', [
                'title' => $title,
                'category' => $category,
                'content_length' => strlen($content),
            ]);

            return $content;
        } catch (\Exception $e) {
            Log::channel('mistral')->error('Failed to generate content', [
                'title' => $title,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build a prompt for content generation based on post data.
     */
    protected function buildPrompt(string $title, ?string $category): string
    {
        $prompt = "Write a comprehensive technical article about: {$title}\n\n";

        if ($category) {
            $prompt .= "This article is in the {$category} category.\n\n";
        }

        $prompt .= "Requirements:\n";
        $prompt .= "- Write in markdown format\n";
        $prompt .= "- Include a clear introduction\n";
        $prompt .= "- Organize content with appropriate headings (##)\n";
        $prompt .= "- Provide technical details and examples where relevant\n";
        $prompt .= "- Include a conclusion\n";
        $prompt .= "- Aim for 500-800 words\n";
        $prompt .= "- Use a professional, informative tone\n";
        $prompt .= "- Focus on practical information for developers\n\n";
        $prompt .= 'Generate only the article content in markdown format, without any preamble or meta-commentary.';

        return $prompt;
    }

    /**
     * Call the Mistral AI API with the given prompt.
     */
    protected function callMistralApi(string $prompt): string
    {
        $model = config('mistral.model', 'mistral-medium');
        $timeout = config('mistral.timeout', 30);

        $chat = new ChatResource($this->client);

        $response = $chat->create([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000,
        ]);

        if (empty($response['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Empty response from Mistral AI API');
        }

        return trim($response['choices'][0]['message']['content']);
    }

    /**
     * Validate that the content is in markdown format.
     */
    protected function validateMarkdown(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        // Check for basic markdown structure
        // Should have at least some text content
        if (strlen($content) < 100) {
            return false;
        }

        // Should contain at least one heading or paragraph structure
        $hasMarkdownElements = preg_match('/^#{1,6}\s+.+$/m', $content) || // Headings
                              preg_match('/\n\n/', $content) || // Paragraph breaks
                              preg_match('/^\*\s+.+$/m', $content) || // Unordered lists
                              preg_match('/^\d+\.\s+.+$/m', $content); // Ordered lists

        return $hasMarkdownElements;
    }

    /**
     * Retry a callback with exponential backoff on failure.
     */
    protected function retryWithBackoff(callable $callback, int $maxRetries): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt >= $maxRetries) {
                    break;
                }

                // Calculate exponential backoff delay in milliseconds
                $delay = $this->retryDelay * pow(2, $attempt - 1);

                Log::channel('mistral')->warning('API call failed, retrying', [
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage(),
                ]);

                // Convert milliseconds to microseconds for usleep
                usleep($delay * 1000);
            }
        }

        throw $lastException;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NewsImageGeneratorService
{
    /**
     * Assign or generate a featured image for an article.
     *
     * @return array{path: string, alt: string}
     */
    public function assignImage(string $title, array $tags = []): array
    {
        $service = config('import.image_generation.service', 'unsplash');
        $fallbackImage = config('import.image_generation.fallback_image', 'images/default-post.jpg');

        try {
            $imagePath = match ($service) {
                'unsplash' => $this->getUnsplashImage($title, $tags),
                'picsum' => $this->getPicsumImage(),
                'local' => $this->getLocalImage($tags),
                default => $fallbackImage,
            };

            $altText = $this->generateAltText($title);

            return [
                'path' => $imagePath,
                'alt' => $altText,
            ];
        } catch (\Exception $e) {
            Log::channel('import')->warning('Image generation failed', [
                'title' => $title,
                'service' => $service,
                'error' => $e->getMessage(),
            ]);

            return [
                'path' => $fallbackImage,
                'alt' => $this->generateAltText($title),
            ];
        }
    }

    /**
     * Generate descriptive alt text from article title.
     */
    public function generateAltText(string $title): string
    {
        // Clean up the title and make it more descriptive for alt text
        $altText = Str::limit($title, 100, '');

        // Add context prefix if not already present
        if (! Str::contains(Str::lower($altText), ['image', 'illustration', 'photo'])) {
            $altText = "Featured image for: {$altText}";
        }

        return trim($altText);
    }

    /**
     * Get image from Unsplash placeholder service.
     */
    protected function getUnsplashImage(string $title, array $tags): string
    {
        // Extract keywords from title and tags for better image matching
        $keywords = $this->extractKeywords($title, $tags);
        $query = ! empty($keywords) ? implode(',', array_slice($keywords, 0, 3)) : 'technology';

        // Use Unsplash Source API for random images based on query
        // Format: https://source.unsplash.com/1200x630/?keyword1,keyword2
        return "https://source.unsplash.com/1200x630/?{$query}";
    }

    /**
     * Get image from Picsum (Lorem Picsum) placeholder service.
     */
    protected function getPicsumImage(): string
    {
        // Generate a random seed for consistent but varied images
        $seed = rand(1, 1000);

        // Format: https://picsum.photos/seed/{seed}/1200/630
        return "https://picsum.photos/seed/{$seed}/1200/630";
    }

    /**
     * Get a local image from predefined assets.
     */
    protected function getLocalImage(array $tags): string
    {
        // Map common tech topics to local images
        $imageMap = [
            'javascript' => 'images/tech/javascript.jpg',
            'python' => 'images/tech/python.jpg',
            'php' => 'images/tech/php.jpg',
            'laravel' => 'images/tech/laravel.jpg',
            'react' => 'images/tech/react.jpg',
            'vue' => 'images/tech/vue.jpg',
            'docker' => 'images/tech/docker.jpg',
            'kubernetes' => 'images/tech/kubernetes.jpg',
            'ai' => 'images/tech/ai.jpg',
            'machine-learning' => 'images/tech/ml.jpg',
            'security' => 'images/tech/security.jpg',
            'cloud' => 'images/tech/cloud.jpg',
        ];

        // Try to match tags to available images
        foreach ($tags as $tag) {
            $tagSlug = Str::slug($tag);
            if (isset($imageMap[$tagSlug])) {
                return $imageMap[$tagSlug];
            }
        }

        // Return default fallback
        return config('import.image_generation.fallback_image', 'images/default-post.jpg');
    }

    /**
     * Extract relevant keywords from title and tags.
     */
    protected function extractKeywords(string $title, array $tags): array
    {
        $keywords = [];

        // Add tags as primary keywords
        foreach ($tags as $tag) {
            $keywords[] = Str::slug($tag);
        }

        // Extract meaningful words from title (remove common words)
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'how', 'what', 'when', 'where', 'why', 'which', 'who', 'whom'];

        $titleWords = explode(' ', Str::lower($title));
        foreach ($titleWords as $word) {
            $word = preg_replace('/[^a-z0-9]/', '', $word);
            if (strlen($word) > 3 && ! in_array($word, $commonWords)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Batch assign images to multiple articles.
     */
    public function assignBulk(array $articles): array
    {
        $results = [];

        foreach ($articles as $id => $article) {
            $title = $article['title'] ?? '';
            $tags = $article['tags'] ?? [];

            $results[$id] = $this->assignImage($title, $tags);
        }

        return $results;
    }
}

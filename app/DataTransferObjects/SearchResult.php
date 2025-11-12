<?php

namespace App\DataTransferObjects;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;

class SearchResult
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $excerpt,
        public readonly ?string $url,
        public readonly float $relevanceScore,
        public readonly array $highlights,
        public readonly array $metadata,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'relevance_score' => $this->relevanceScore,
            'highlights' => $this->highlights,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromPost(Post $post, float $score, array $highlights = []): self
    {
        return new self(
            id: $post->id,
            type: 'post',
            title: $post->title,
            excerpt: $post->excerpt,
            url: route('posts.show', $post->slug),
            relevanceScore: $score,
            highlights: $highlights,
            metadata: [
                'slug' => $post->slug,
                'published_at' => $post->published_at?->toISOString(),
                'author' => $post->user?->name,
                'category' => $post->category?->name,
            ],
        );
    }

    public static function fromTag(Tag $tag, float $score, array $highlights = []): self
    {
        return new self(
            id: $tag->id,
            type: 'tag',
            title: $tag->name,
            excerpt: null,
            url: route('tags.show', $tag->slug),
            relevanceScore: $score,
            highlights: $highlights,
            metadata: [
                'slug' => $tag->slug,
                'post_count' => $tag->posts_count ?? 0,
            ],
        );
    }

    public static function fromCategory(Category $category, float $score, array $highlights = []): self
    {
        return new self(
            id: $category->id,
            type: 'category',
            title: $category->name,
            excerpt: $category->description,
            url: route('categories.show', $category->slug),
            relevanceScore: $score,
            highlights: $highlights,
            metadata: [
                'slug' => $category->slug,
                'post_count' => $category->posts_count ?? 0,
            ],
        );
    }
}

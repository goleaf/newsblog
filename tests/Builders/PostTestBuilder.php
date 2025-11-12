<?php

namespace Tests\Builders;

use App\Models\Category;
use App\Models\Post;

class PostTestBuilder
{
    private array $attributes = [
        'status' => 'published',
    ];

    public static function make(): self
    {
        return new self;
    }

    public function published(): self
    {
        $this->attributes['status'] = 'published';
        $this->attributes['published_at'] = now();

        return $this;
    }

    public function draft(): self
    {
        $this->attributes['status'] = 'draft';
        unset($this->attributes['published_at']);

        return $this;
    }

    public function scheduled(): self
    {
        $this->attributes['status'] = 'scheduled';
        $this->attributes['published_at'] = now()->addDay();

        return $this;
    }

    public function withTitle(string $title): self
    {
        $this->attributes['title'] = $title;

        return $this;
    }

    public function withExcerpt(string $excerpt): self
    {
        $this->attributes['excerpt'] = $excerpt;

        return $this;
    }

    public function inCategory(Category $category): self
    {
        $this->attributes['category_id'] = $category->id;

        return $this;
    }

    public function create(): Post
    {
        return Post::factory()->create($this->attributes);
    }

    public function count(int $count): array
    {
        return Post::factory()->count($count)->create($this->attributes)->all();
    }
}

<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Tests\TestCase;

class PostMetaDataTest extends TestCase
{
    public function test_get_meta_tags_contains_expected_keys_and_values(): void
    {
        // Build in-memory models and relations (no DB)
        $user = new User(['name' => 'Alice Author']);
        $category = new Category(['name' => 'Tech', 'slug' => 'tech']);
        $tag1 = new Tag(['name' => 'Laravel']);
        $tag2 = new Tag(['name' => 'PHP']);

        $post = new Post([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'excerpt' => 'A short intro',
            'content' => str_repeat('content ', 300),
            'published_at' => now()->subHour(),
        ]);
        $post->created_at = now()->subHours(2);
        $post->updated_at = now()->subMinute();

        $post->setRelation('user', $user);
        $post->setRelation('category', $category);
        $post->setRelation('tags', collect([$tag1, $tag2]));

        $meta = $post->getMetaTags();

        $this->assertSame('article', $meta['og:type']);
        $this->assertSame('summary_large_image', $meta['twitter:card']);
        $this->assertSame('Alice Author', $meta['article:author']);
        $this->assertSame('Tech', $meta['article:section']);
        $this->assertIsArray($meta['article:tag']);
        $this->assertContains('Laravel', $meta['article:tag']);
        $this->assertContains('PHP', $meta['article:tag']);
        $this->assertNotEmpty($meta['og:image']);
    }

    public function test_get_structured_data_shape(): void
    {
        $user = new User(['name' => 'Bob Author']);
        $category = new Category(['name' => 'Science', 'slug' => 'science']);
        $post = new Post([
            'title' => 'Deep Dive',
            'slug' => 'deep-dive',
            'excerpt' => 'About science',
            'content' => str_repeat('insight ', 250),
            'published_at' => now()->subDay(),
        ]);
        $post->created_at = now()->subDays(2);
        $post->updated_at = now()->subHours(3);
        $post->reading_time = 10;

        $post->setRelation('user', $user);
        $post->setRelation('category', $category);
        $post->setRelation('tags', collect());

        $data = $post->getStructuredData();

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Article', $data['@type']);
        $this->assertSame('Deep Dive', $data['headline']);
        $this->assertSame('Bob Author', $data['author']['name']);
        $this->assertSame(config('app.name', 'TechNewsHub'), $data['publisher']['name']);
        $this->assertIsInt($data['wordCount']);
        $this->assertArrayHasKey('timeRequired', $data);
    }
}

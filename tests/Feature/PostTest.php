<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_displays_posts(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    public function test_post_page_displays_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertViewIs('posts.show');
        $response->assertSee($post->title);
    }

    public function test_category_page_displays_posts(): void
    {
        $category = Category::factory()->create(['status' => 'active']);

        $response = $this->get("/category/{$category->slug}");

        $response->assertStatus(200);
        $response->assertViewIs('categories.show');
    }

    public function test_search_returns_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Search Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/search?q=Test');

        $response->assertStatus(200);
        $response->assertViewIs('search');
        $response->assertSee('Test Search Post');
    }

    public function test_scope_without_content_filters_posts_with_null_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithEmptyContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with empty content',
            'content' => '',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithEmptyContent->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_scope_without_content_filters_posts_with_empty_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithEmptyContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with empty content',
            'content' => '',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithEmptyContent->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_scope_without_content_filters_posts_with_whitespace_only_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithWhitespaceContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with whitespace content',
            'content' => '   ',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithWhitespaceContent->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_scope_without_content_excludes_posts_with_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithTitle = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with title',
            'content' => '',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithTitle->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_get_meta_tags_returns_all_required_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post Title',
            'excerpt' => 'This is a test excerpt for the post',
            'meta_title' => 'Custom Meta Title',
            'meta_description' => 'Custom meta description for SEO',
            'meta_keywords' => 'test, seo, keywords',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $metaTags = $post->getMetaTags();

        $this->assertArrayHasKey('title', $metaTags);
        $this->assertArrayHasKey('description', $metaTags);
        $this->assertArrayHasKey('og:title', $metaTags);
        $this->assertArrayHasKey('og:description', $metaTags);
        $this->assertArrayHasKey('og:image', $metaTags);
        $this->assertArrayHasKey('og:url', $metaTags);
        $this->assertArrayHasKey('twitter:card', $metaTags);
        $this->assertArrayHasKey('twitter:title', $metaTags);
        $this->assertArrayHasKey('twitter:description', $metaTags);
        $this->assertEquals('Custom Meta Title', $metaTags['title']);
        $this->assertEquals('Custom meta description for SEO', $metaTags['description']);
    }

    public function test_get_meta_description_limits_to_160_characters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $longDescription = str_repeat('This is a very long description that exceeds the maximum allowed length. ', 5);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'meta_description' => $longDescription,
        ]);

        $metaDescription = $post->getMetaDescription();

        $this->assertLessThanOrEqual(160, strlen($metaDescription));
    }

    public function test_get_meta_description_uses_excerpt_when_meta_description_is_null(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'excerpt' => 'This is the post excerpt',
            'meta_description' => null,
        ]);

        $metaDescription = $post->getMetaDescription();

        $this->assertStringContainsString('This is the post excerpt', $metaDescription);
    }

    public function test_get_structured_data_returns_valid_schema_org_format(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $category = Category::factory()->create(['name' => 'Technology']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Article',
            'content' => 'This is the article content with multiple words for testing.',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $structuredData = $post->getStructuredData();

        $this->assertEquals('https://schema.org', $structuredData['@context']);
        $this->assertEquals('Article', $structuredData['@type']);
        $this->assertEquals('Test Article', $structuredData['headline']);
        $this->assertArrayHasKey('author', $structuredData);
        $this->assertEquals('Person', $structuredData['author']['@type']);
        $this->assertEquals('John Doe', $structuredData['author']['name']);
        $this->assertArrayHasKey('publisher', $structuredData);
        $this->assertArrayHasKey('datePublished', $structuredData);
        $this->assertArrayHasKey('dateModified', $structuredData);
    }

    public function test_post_show_page_includes_open_graph_meta_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Meta Tags',
            'meta_title' => 'Custom Meta Title',
            'meta_description' => 'Custom meta description',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertSee('og:title', false);
        $response->assertSee('og:description', false);
        $response->assertSee('og:image', false);
        $response->assertSee('og:url', false);
        $response->assertSee('Custom Meta Title', false);
    }

    public function test_post_show_page_includes_twitter_card_meta_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Twitter Cards',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertSee('twitter:card', false);
        $response->assertSee('twitter:title', false);
        $response->assertSee('twitter:description', false);
        $response->assertSee('twitter:image', false);
        $response->assertSee('summary_large_image', false);
    }

    public function test_post_show_page_includes_structured_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Structured Data',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('https://schema.org', false);
        $response->assertSee('Article', false);
    }

    public function test_post_show_page_includes_reading_progress_indicator(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Reading Progress',
            'content' => 'This is a test post with content to track reading progress.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        // Check for Alpine.js component
        $response->assertSee('x-data="readingProgress()"', false);
        $response->assertSee('x-init="init()"', false);
        // Check for progress bar structure
        $response->assertSee('fixed top-0 left-0 right-0 h-1', false);
        $response->assertSee('bg-indigo-600', false);
        $response->assertSee('transition-all duration-100', false);
        // Check for article ID
        $response->assertSee('id="article-content"', false);
        // Check for JavaScript function
        $response->assertSee('function readingProgress()', false);
        $response->assertSee('calculateProgress()', false);
    }

    public function test_post_show_page_includes_share_buttons(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post with Share Buttons',
            'content' => 'This is a test post with share buttons.',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        // Check for share buttons component
        $response->assertSee('x-data="sharePost()"', false);
        $response->assertSee('Share this post', false);
        // Check for Facebook share button
        $response->assertSee('shareOnFacebook', false);
        $response->assertSee('Share on Facebook', false);
        // Check for Twitter share button
        $response->assertSee('shareOnTwitter', false);
        $response->assertSee('Share on Twitter', false);
        // Check for copy link button
        $response->assertSee('copyLink', false);
        $response->assertSee('Copy link', false);
        // Check for Web Share API button
        $response->assertSee('nativeShare', false);
        // Check for JavaScript functions
        $response->assertSee('function sharePost()', false);
        $response->assertSee('navigator.clipboard.writeText', false);
        $response->assertSee('navigator.share', false);
        // Check for copy confirmation message
        $response->assertSee('Link copied to clipboard!', false);
    }
}

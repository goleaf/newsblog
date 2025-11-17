<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoImplementationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** @test */
    public function homepage_includes_organization_schema(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('@type":"Organization', false);
        $response->assertSee(config('app.name'), false);
    }

    /** @test */
    public function homepage_includes_website_schema(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('@type":"WebSite', false);
        $response->assertSee('SearchAction', false);
    }

    /** @test */
    public function homepage_includes_proper_meta_tags(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:type" content="website"', false);
        $response->assertSee('<meta name="twitter:card"', false);
        $response->assertSee('<link rel="canonical"', false);
    }

    /** @test */
    public function post_page_includes_article_schema(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('@type":"Article', false);
        $response->assertSee($post->title, false);
    }

    /** @test */
    public function post_page_includes_breadcrumb_schema(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('@type":"BreadcrumbList', false);
        $response->assertSee('itemListElement', false);
    }

    /** @test */
    public function post_page_includes_open_graph_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'meta_title' => 'Test Meta Title',
            'meta_description' => 'Test meta description for SEO',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:type" content="article"', false);
        $response->assertSee('<meta property="article:published_time"', false);
        $response->assertSee('<meta property="article:author"', false);
    }

    /** @test */
    public function post_page_includes_twitter_card_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('<meta name="twitter:card"', false);
        $response->assertSee('<meta name="twitter:title"', false);
        $response->assertSee('<meta name="twitter:description"', false);
    }

    /** @test */
    public function post_uses_seo_friendly_slug(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Introduction to Laravel 12 Features',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->assertEquals('introduction-to-laravel-12-features', $post->slug);
        $this->assertTrue(str_contains(route('post.show', $post->slug), 'introduction-to-laravel-12-features'));
    }

    /** @test */
    public function meta_description_is_limited_to_160_characters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $longDescription = str_repeat('This is a very long description that exceeds the recommended length. ', 10);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'meta_description' => $longDescription,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $metaDescription = $post->getMetaDescription();

        $this->assertLessThanOrEqual(160, strlen($metaDescription));
    }

    /** @test */
    public function sitemap_can_be_generated(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(5)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $sitemapService = app(SitemapService::class);
        $files = $sitemapService->generate();

        $this->assertNotEmpty($files);
        Storage::disk('public')->assertExists('sitemap.xml');
    }

    /** @test */
    public function sitemap_includes_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $publishedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'slug' => 'published-post',
        ]);

        $draftPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'draft',
            'slug' => 'draft-post',
        ]);

        $sitemapService = app(SitemapService::class);
        $sitemapService->generate();

        $sitemapContent = Storage::disk('public')->get('sitemap.xml');

        $this->assertStringContainsString('published-post', $sitemapContent);
        $this->assertStringNotContainsString('draft-post', $sitemapContent);
    }

    /** @test */
    public function sitemap_is_accessible_via_route(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee('<urlset', false);
    }

    /** @test */
    public function robots_txt_is_accessible(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee('User-agent: *');
        $response->assertSee('Allow: /');
        $response->assertSee('Sitemap:');
    }

    /** @test */
    public function robots_txt_disallows_admin_areas(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('Disallow: /nova/');
        $response->assertSee('Disallow: /admin/');
        $response->assertSee('Disallow: /dashboard/');
        $response->assertSee('Disallow: /api/');
    }

    /** @test */
    public function robots_txt_includes_sitemap_url(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('Sitemap: ');
        $response->assertSee('sitemap.xml');
    }

    /** @test */
    public function post_has_canonical_url(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('<link rel="canonical" href="'.route('post.show', $post->slug).'"', false);
    }

    /** @test */
    public function post_structured_data_includes_reading_time(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'reading_time' => 5,
        ]);

        $structuredData = $post->getStructuredData();

        $this->assertArrayHasKey('timeRequired', $structuredData);
        $this->assertEquals('PT5M', $structuredData['timeRequired']);
    }

    /** @test */
    public function post_structured_data_includes_word_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'content' => 'This is a test post with some content that has multiple words.',
        ]);

        $structuredData = $post->getStructuredData();

        $this->assertArrayHasKey('wordCount', $structuredData);
        $this->assertGreaterThan(0, $structuredData['wordCount']);
    }

    /** @test */
    public function semantic_html_uses_proper_heading_hierarchy(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);

        // Check for semantic HTML elements
        $response->assertSee('<main', false);
        $response->assertSee('<article', false);
        $response->assertSee('<aside', false);

        // Check for proper heading hierarchy
        $response->assertSee('<h1', false);
        $response->assertSee('<h2', false);
    }

    /** @test */
    public function category_page_has_proper_seo_structure(): void
    {
        $category = Category::factory()->create([
            'name' => 'Web Development',
            'slug' => 'web-development',
        ]);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertStatus(200);
        $response->assertSee('Web Development');

        // Check URL structure
        $this->assertTrue(str_contains(route('category.show', $category->slug), 'web-development'));
    }

    /** @test */
    public function tag_page_has_proper_seo_structure(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $response = $this->get(route('tag.show', $tag->slug));

        $response->assertStatus(200);
        $response->assertSee('Laravel');

        // Check URL structure
        $this->assertTrue(str_contains(route('tag.show', $tag->slug), 'laravel'));
    }
}

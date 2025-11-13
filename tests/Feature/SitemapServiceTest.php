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

class SitemapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SitemapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SitemapService::class);
        Storage::fake('public');
    }

    public function test_generates_sitemap_with_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $files = $this->service->generate();

        $this->assertNotEmpty($files);
        Storage::disk('public')->assertExists('sitemap.xml');
    }

    public function test_includes_homepage_in_sitemap(): void
    {
        $this->service->generate();

        $content = Storage::disk('public')->get('sitemap.xml');

        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $content);
        $this->assertStringContainsString('<priority>1.0</priority>', $content);
    }

    public function test_includes_categories_in_sitemap(): void
    {
        Category::factory()->create(['slug' => 'test-category']);

        $this->service->generate();

        $content = Storage::disk('public')->get('sitemap.xml');

        $this->assertStringContainsString('test-category', $content);
    }

    public function test_includes_tags_in_sitemap(): void
    {
        Tag::factory()->create(['slug' => 'test-tag']);

        $this->service->generate();

        $content = Storage::disk('public')->get('sitemap.xml');

        $this->assertStringContainsString('test-tag', $content);
    }

    public function test_only_includes_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'slug' => 'published-post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'slug' => 'draft-post',
            'status' => 'draft',
        ]);

        $this->service->generate();

        $content = Storage::disk('public')->get('sitemap.xml');

        $this->assertStringContainsString('published-post', $content);
        $this->assertStringNotContainsString('draft-post', $content);
    }

    public function test_generates_valid_xml_structure(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->service->generate();

        $content = Storage::disk('public')->get('sitemap.xml');

        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $content);
        $this->assertStringContainsString('</urlset>', $content);
    }

    public function test_checks_if_sitemap_exists(): void
    {
        $this->assertFalse($this->service->exists());

        $this->service->generate();

        $this->assertTrue($this->service->exists());
    }

    public function test_returns_sitemap_url(): void
    {
        $this->service->generate();

        $url = $this->service->getSitemapUrl();

        $this->assertStringContainsString('sitemap', $url);
    }
}

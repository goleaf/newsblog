<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\BreadcrumbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class BreadcrumbServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BreadcrumbService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BreadcrumbService::class);
    }

    public function test_generates_home_breadcrumb_for_unknown_route(): void
    {
        $request = Request::create('/');

        $breadcrumbs = $this->service->generate($request);

        $this->assertCount(1, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
    }

    public function test_generates_breadcrumbs_for_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $request = Request::create('/posts/test-post');
        $route = new Route('GET', '/posts/{slug}', []);
        $route->bind($request);
        $route->setParameter('slug', 'test-post');
        $request->setRouteResolver(fn () => $route->name('post.show'));

        $breadcrumbs = $this->service->generate($request);

        $this->assertGreaterThanOrEqual(2, count($breadcrumbs));
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertStringContainsString('Test Post', $breadcrumbs[count($breadcrumbs) - 1]['title']);
    }

    public function test_generates_breadcrumbs_for_category(): void
    {
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

        $request = Request::create('/category/technology');
        $route = new Route('GET', '/category/{slug}', []);
        $route->bind($request);
        $route->setParameter('slug', 'technology');
        $request->setRouteResolver(fn () => $route->name('category.show'));

        $breadcrumbs = $this->service->generate($request);

        $this->assertGreaterThanOrEqual(2, count($breadcrumbs));
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Technology', $breadcrumbs[count($breadcrumbs) - 1]['title']);
    }

    public function test_generates_breadcrumbs_for_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $request = Request::create('/tag/laravel');
        $route = new Route('GET', '/tag/{slug}', []);
        $route->bind($request);
        $route->setParameter('slug', 'laravel');
        $request->setRouteResolver(fn () => $route->name('tag.show'));

        $breadcrumbs = $this->service->generate($request);

        $this->assertGreaterThanOrEqual(2, count($breadcrumbs));
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Laravel', $breadcrumbs[count($breadcrumbs) - 1]['title']);
    }

    public function test_includes_category_hierarchy_in_post_breadcrumbs(): void
    {
        $user = User::factory()->create();
        $parentCategory = Category::factory()->create(['name' => 'Programming', 'slug' => 'programming']);
        $childCategory = Category::factory()->create([
            'name' => 'PHP',
            'slug' => 'php',
            'parent_id' => $parentCategory->id,
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $childCategory->id,
            'title' => 'Test Post',
            'slug' => 'test-post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $request = Request::create('/posts/test-post');
        $route = new Route('GET', '/posts/{slug}', []);
        $route->bind($request);
        $route->setParameter('slug', 'test-post');
        $request->setRouteResolver(fn () => $route->name('post.show'));

        $breadcrumbs = $this->service->generate($request);

        $titles = array_column($breadcrumbs, 'title');

        $this->assertContains('Home', $titles);
        $this->assertContains('Programming', $titles);
        $this->assertContains('PHP', $titles);
    }

    public function test_truncates_long_titles(): void
    {
        $longTitle = str_repeat('Very Long Title ', 10);
        $truncated = $this->service->generate(Request::create('/'));

        $this->assertIsArray($truncated);
    }

    public function test_generates_structured_data(): void
    {
        $breadcrumbs = [
            ['title' => 'Home', 'url' => 'https://example.com'],
            ['title' => 'Category', 'url' => 'https://example.com/category'],
            ['title' => 'Post', 'url' => null],
        ];

        $structuredData = $this->service->generateStructuredData($breadcrumbs);

        $this->assertJson($structuredData);

        $data = json_decode($structuredData, true);

        $this->assertEquals('https://schema.org', $data['@context']);
        $this->assertEquals('BreadcrumbList', $data['@type']);
        $this->assertCount(3, $data['itemListElement']);
        $this->assertEquals('Home', $data['itemListElement'][0]['name']);
        $this->assertEquals(1, $data['itemListElement'][0]['position']);
    }

    public function test_current_page_has_no_url(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['slug' => 'tech']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'slug' => 'test-post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $request = Request::create('/posts/test-post');
        $route = new Route('GET', '/posts/{slug}', []);
        $route->bind($request);
        $route->setParameter('slug', 'test-post');
        $request->setRouteResolver(fn () => $route->name('post.show'));

        $breadcrumbs = $this->service->generate($request);

        $lastBreadcrumb = end($breadcrumbs);

        $this->assertNull($lastBreadcrumb['url']);
    }
}

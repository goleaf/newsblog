<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
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

    private BreadcrumbService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BreadcrumbService;
    }

    public function test_generates_home_breadcrumb_for_home_route(): void
    {
        $request = Request::create('/', 'GET');
        $route = new Route('GET', '/', []);
        $route->name('home');
        $request->setRouteResolver(fn () => $route);

        $breadcrumbs = $this->service->generate($request);

        $this->assertCount(1, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals(route('home'), $breadcrumbs[0]['url']);
    }

    public function test_generates_post_breadcrumbs_with_category_hierarchy(): void
    {
        $user = User::factory()->create();
        $parentCategory = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology', 'parent_id' => null]);
        $childCategory = Category::factory()->create(['name' => 'Programming', 'slug' => 'programming', 'parent_id' => $parentCategory->id]);
        $post = Post::factory()->create([
            'title' => 'Laravel Best Practices',
            'slug' => 'laravel-best-practices',
            'category_id' => $childCategory->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));
        $response->assertOk();

        $breadcrumbs = $response->viewData('breadcrumbs');

        $this->assertCount(4, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Technology', $breadcrumbs[1]['title']);
        $this->assertEquals('Programming', $breadcrumbs[2]['title']);
        $this->assertEquals('Laravel Best Practices', $breadcrumbs[3]['title']);
        $this->assertNull($breadcrumbs[3]['url']); // Current page has no URL
    }

    public function test_generates_category_breadcrumbs(): void
    {
        $parentCategory = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology', 'parent_id' => null]);
        $childCategory = Category::factory()->create(['name' => 'Programming', 'slug' => 'programming', 'parent_id' => $parentCategory->id]);

        $response = $this->get(route('category.show', $childCategory->slug));
        $response->assertOk();

        $breadcrumbs = $response->viewData('breadcrumbs');

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Technology', $breadcrumbs[1]['title']);
        $this->assertEquals('Programming', $breadcrumbs[2]['title']);
        $this->assertNull($breadcrumbs[2]['url']); // Current page has no URL
    }

    public function test_generates_tag_breadcrumbs(): void
    {
        $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $response = $this->get(route('tag.show', $tag->slug));
        $response->assertOk();

        $breadcrumbs = $response->viewData('breadcrumbs');

        $this->assertCount(2, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Laravel', $breadcrumbs[1]['title']);
        $this->assertNull($breadcrumbs[1]['url']);
    }

    public function test_generates_series_breadcrumbs(): void
    {
        $series = Series::factory()->create(['name' => 'Laravel Tutorial Series', 'slug' => 'laravel-tutorial-series']);

        $response = $this->get(route('series.show', $series->slug));
        $response->assertOk();

        $breadcrumbs = $response->viewData('breadcrumbs');

        $this->assertCount(3, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Series', $breadcrumbs[1]['title']);
        $this->assertEquals('Laravel Tutorial Series', $breadcrumbs[2]['title']);
        $this->assertNull($breadcrumbs[2]['url']);
    }

    public function test_generates_search_breadcrumbs(): void
    {
        $request = Request::create('/search?q=laravel', 'GET');
        $route = new Route('GET', '/search', []);
        $route->name('search');
        $request->setRouteResolver(fn () => $route);

        $breadcrumbs = $this->service->generate($request);

        $this->assertCount(2, $breadcrumbs);
        $this->assertEquals('Home', $breadcrumbs[0]['title']);
        $this->assertEquals('Search Results', $breadcrumbs[1]['title']);
        $this->assertNull($breadcrumbs[1]['url']);
    }

    public function test_truncates_long_titles_for_mobile(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'title' => 'This is a very long post title that should be truncated for mobile display',
            'slug' => 'long-title',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));
        $response->assertOk();

        $breadcrumbs = $response->viewData('breadcrumbs');

        $lastBreadcrumb = end($breadcrumbs);
        $this->assertStringEndsWith('...', $lastBreadcrumb['title']);
        $this->assertLessThanOrEqual(30, strlen($lastBreadcrumb['title']));
    }

    public function test_generates_structured_data(): void
    {
        $breadcrumbs = [
            ['title' => 'Home', 'url' => 'https://example.com'],
            ['title' => 'Technology', 'url' => 'https://example.com/category/technology'],
            ['title' => 'Current Page', 'url' => null],
        ];

        $structuredData = $this->service->generateStructuredData($breadcrumbs);
        $data = json_decode($structuredData, true);

        $this->assertEquals('https://schema.org', $data['@context']);
        $this->assertEquals('BreadcrumbList', $data['@type']);
        $this->assertCount(3, $data['itemListElement']);
        $this->assertEquals(1, $data['itemListElement'][0]['position']);
        $this->assertEquals('Home', $data['itemListElement'][0]['name']);
        $this->assertEquals('https://example.com', $data['itemListElement'][0]['item']);
        $this->assertEquals('Current Page', $data['itemListElement'][2]['name']);
        $this->assertArrayNotHasKey('item', $data['itemListElement'][2]); // Current page has no item URL
    }
}

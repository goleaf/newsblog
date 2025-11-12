<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreadcrumbDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_breadcrumbs_are_displayed_on_post_page(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $post = Post::factory()->create([
            'title' => 'Laravel Best Practices',
            'slug' => 'laravel-best-practices',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();
        $response->assertSee('Home');
        $response->assertSee('Technology');
        $response->assertSee('Laravel Best Practices');
        $response->assertSee('aria-label="Breadcrumb"', false);
    }

    public function test_breadcrumbs_display_category_hierarchy(): void
    {
        $user = User::factory()->create();
        $parentCategory = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology', 'parent_id' => null]);
        $childCategory = Category::factory()->create(['name' => 'Programming', 'slug' => 'programming', 'parent_id' => $parentCategory->id]);
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'category_id' => $childCategory->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();
        $response->assertSeeInOrder(['Home', 'Technology', 'Programming', 'Test Post']);
    }

    public function test_breadcrumbs_include_structured_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();
        $response->assertSee('application/ld+json', false);

        $content = $response->getContent();
        $this->assertStringContainsString('BreadcrumbList', $content);
        $this->assertStringContainsString('https://schema.org', $content);
    }

    public function test_breadcrumbs_are_displayed_on_category_page(): void
    {
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

        $response = $this->get(route('category.show', $category->slug));

        $response->assertOk();
        $response->assertSee('Home');
        $response->assertSee('Technology');
    }

    public function test_breadcrumbs_are_displayed_on_tag_page(): void
    {
        $tag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $response = $this->get(route('tag.show', $tag->slug));

        $response->assertOk();
        $response->assertSee('Home');
        $response->assertSee('Laravel');
    }

    public function test_breadcrumbs_are_displayed_on_series_page(): void
    {
        $series = Series::factory()->create(['name' => 'Laravel Tutorial', 'slug' => 'laravel-tutorial']);

        $response = $this->get(route('series.show', $series->slug));

        $response->assertOk();
        $response->assertSee('Home');
        $response->assertSee('Series');
        $response->assertSee('Laravel Tutorial');
    }

    public function test_breadcrumbs_are_displayed_on_search_page(): void
    {
        $response = $this->get(route('search', ['q' => 'laravel']));

        $response->assertOk();
        $response->assertSee('Home');
        $response->assertSee('Search Results');
    }

    public function test_current_page_breadcrumb_has_no_link(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'slug' => 'test-post',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();

        // The current page title should be in a span, not an anchor
        $content = $response->getContent();
        $this->assertStringContainsString('<span', $content);
        $this->assertStringContainsString('Test Post', $content);
    }
}

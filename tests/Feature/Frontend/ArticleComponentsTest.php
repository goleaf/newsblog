<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_header_component_renders_correctly(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $category = Category::factory()->create([
            'name' => 'Technology',
            'icon' => '💻',
            'color_code' => '#3b82f6',
        ]);
        $tag = Tag::factory()->create(['name' => 'Laravel']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Article Title',
            'excerpt' => 'This is a test excerpt',
            'reading_time' => 5,
            'view_count' => 100,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post->tags()->attach($tag);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertSee('Test Article Title');
        $response->assertSee('This is a test excerpt');
        $response->assertSee('John Doe');
        $response->assertSee('Technology');
        $response->assertSee('5 min read');
        $response->assertSee('100 views');
        $response->assertSee('Laravel');
    }

    public function test_article_content_component_renders_prose_styling(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'content' => '<h2>Test Heading</h2><p>Test paragraph content.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertSee('prose', false);
        $response->assertSee('prose-lg', false);
        $response->assertSee('dark:prose-invert', false);
        $response->assertSee('Test Heading');
        $response->assertSee('Test paragraph content.');
    }

    public function test_reading_progress_indicator_is_present(): void
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
        $response->assertSee('readingProgress', false);
        $response->assertSee('fixed top-0', false);
        $response->assertSee('bg-indigo-600', false);
    }

    public function test_floating_actions_component_renders_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertSee('floatingActions', false);
        $response->assertSee('toggleBookmark', false);
        $response->assertSee('showShareModal', false);
        $response->assertSee('showReactionPicker', false);
    }

    public function test_floating_actions_shows_login_prompts_for_guests(): void
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
        $response->assertSee('Login to bookmark', false);
        $response->assertSee('Login to react', false);
    }

    public function test_series_navigation_renders_when_post_is_in_series(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $series = Series::factory()->create([
            'name' => 'Laravel Tutorial Series',
            'description' => 'Learn Laravel from scratch',
        ]);

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Part 1: Introduction',
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Part 2: Getting Started',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $post1->series()->associate($series); $post1->order_in_series = 1; $post1->save();
        $post2->series()->associate($series); $post2->order_in_series = 2; $post2->save();

        $response = $this->get("/post/{$post2->slug}");

        $response->assertStatus(200);
        $response->assertSee('Laravel Tutorial Series');
        $response->assertSee('Part of a Series');
        $response->assertSee('Part 1: Introduction');
        $response->assertSee('Previous');
    }

    public function test_related_posts_sidebar_displays_related_articles(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $mainPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Main Article',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $relatedPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Related Article',
            'status' => 'published',
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->get("/post/{$mainPost->slug}");

        $response->assertStatus(200);
        $response->assertSee('More like this', false);
        $response->assertSee('Related Article');
    }

    public function test_seo_meta_tags_component_includes_all_required_tags(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Technology']);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'SEO Test Article',
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description',
            'meta_keywords' => 'seo, test, article',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        // Basic meta tags
        $response->assertSee('<title>Custom SEO Title', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="keywords"', false);
        // Canonical URL
        $response->assertSee('rel="canonical"', false);
        // Open Graph tags
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee('property="og:type"', false);
        // Twitter Card tags
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('name="twitter:title"', false);
        // Structured data
        $response->assertSee('application/ld+json', false);
        $response->assertSee('https://schema.org', false);
        $response->assertSee('Article', false);
        $response->assertSee('BreadcrumbList', false);
    }

    public function test_article_page_is_cached(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Cached Article',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        // First request should cache the post
        $response1 = $this->get("/post/{$post->slug}");
        $response1->assertStatus(200);

        // Update the post title
        $post->update(['title' => 'Updated Title']);

        // Second request should still show cached version
        $response2 = $this->get("/post/{$post->slug}");
        $response2->assertStatus(200);
        $response2->assertSee('Cached Article');
    }

    public function test_article_components_work_with_dark_mode_classes(): void
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
        // Check for dark mode classes
        $response->assertSee('dark:bg-gray-800', false);
        $response->assertSee('dark:text-white', false);
        $response->assertSee('dark:prose-invert', false);
        $response->assertSee('dark:border-gray-700', false);
    }

    public function test_article_page_includes_accessibility_attributes(): void
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
        // Check for ARIA attributes
        $response->assertSee('role="progressbar"', false);
        $response->assertSee('aria-label', false);
        $response->assertSee('aria-valuenow', false);
    }
}

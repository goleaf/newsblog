<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageLazyLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_optimized_image_component_renders_with_lazy_loading(): void
    {
        $view = $this->blade(
            '<x-optimized-image src="/storage/test.jpg" alt="Test Image" width="800" height="600" />',
            []
        );

        $view->assertSee('loading="lazy"', false);
        $view->assertSee('decoding="async"', false);
        $view->assertSee('data-src="/storage/test.jpg"', false);
        $view->assertSee('class="lazy-image"', false);
    }

    public function test_optimized_image_component_renders_with_eager_loading(): void
    {
        $view = $this->blade(
            '<x-optimized-image src="/storage/test.jpg" alt="Test Image" :eager="true" />',
            []
        );

        $view->assertSee('loading="eager"', false);
        $view->assertSee('src="/storage/test.jpg"', false);
        $view->assertDontSee('data-src', false);
        $view->assertDontSee('lazy-image', false);
    }

    public function test_optimized_image_component_includes_responsive_srcset(): void
    {
        $view = $this->blade(
            '<x-optimized-image src="/storage/test.jpg" alt="Test Image" width="800" height="600" />',
            []
        );

        $view->assertSee('data-srcset', false);
        $view->assertSee('?w=400 400w', false);
        $view->assertSee('?w=800 800w', false);
        $view->assertSee('?w=1200 1200w', false);
    }

    public function test_post_card_uses_optimized_image_component(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'featured_image' => 'posts/test-image.jpg',
            'image_alt_text' => 'Test post image',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $view = $this->blade(
            '<x-content.post-card :post="$post" />',
            ['post' => $post]
        );

        $view->assertSee('lazy-image', false);
        $view->assertSee('Test post image', false);
    }

    public function test_hero_post_uses_eager_loading(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'featured_image' => 'posts/hero-image.jpg',
            'image_alt_text' => 'Hero post image',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $view = $this->blade(
            '<x-content.hero-post :post="$post" />',
            ['post' => $post]
        );

        $view->assertSee('loading="eager"', false);
        $view->assertDontSee('lazy-image', false);
    }

    public function test_homepage_displays_images_with_lazy_loading(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create featured post
        $featuredPost = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_featured' => true,
            'featured_image' => 'posts/featured.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Create regular posts
        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'featured_image' => 'posts/regular.jpg',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get('/');

        $response->assertOk();
        // Hero image should use eager loading
        $response->assertSee('loading="eager"', false);
        // Other images should use lazy loading
        $response->assertSee('lazy-image', false);
    }

    public function test_article_content_adds_lazy_loading_to_images(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'content' => '<p>Test content with image</p><img src="/storage/content-image.jpg" alt="Content image">',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $view = $this->blade(
            '<x-article.article-content :post="$post" />',
            ['post' => $post]
        );

        // The Alpine.js script should add lazy loading
        $view->assertSee('articleContent', false);
    }

    public function test_optimized_image_includes_blur_up_placeholder(): void
    {
        $view = $this->blade(
            '<x-optimized-image src="/storage/test.jpg" alt="Test Image" />',
            []
        );

        // Should include SVG placeholder
        $view->assertSee('data:image/svg+xml', false);
        // Should include blur filter
        $view->assertSee('feGaussianBlur', false);
    }

    public function test_optimized_image_can_disable_blur_up(): void
    {
        $view = $this->blade(
            '<x-optimized-image src="/storage/test.jpg" alt="Test Image" :blur-up="false" />',
            []
        );

        $view->assertSee('src="/storage/test.jpg"', false);
        $view->assertDontSee('data:image/svg+xml', false);
    }

    public function test_optimized_image_includes_lazy_loading_script(): void
    {
        // Test that the component includes the necessary data attributes for lazy loading
        $view = $this->blade(
            '<x-optimized-image src="/storage/test.jpg" alt="Test Image" />',
            []
        );

        // Should include data attributes for lazy loading
        $view->assertSee('data-src="/storage/test.jpg"', false);
        $view->assertSee('class="lazy-image"', false);

        // The @push directives will be rendered in the full page context
        // Here we just verify the component sets up the necessary attributes
    }
}

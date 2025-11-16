<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ImageLazyLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create([
            'avatar' => 'avatars/test-avatar.jpg',
        ]);

        $this->category = Category::factory()->create([
            'icon' => '📱',
        ]);

        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'featured_image' => '/storage/posts/test-image.jpg',
            'image_alt_text' => 'Test image description',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function test_optimized_image_component_renders_with_lazy_loading()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
        ])->render();

        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    public function test_optimized_image_component_renders_with_eager_loading()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => true,
        ])->render();

        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    public function test_optimized_image_component_includes_alt_text()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image description',
        ])->render();

        $this->assertStringContainsString('alt="Test image description"', $html);
    }

    public function test_optimized_image_component_generates_srcset_for_storage_images()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
        ])->render();

        // Should have data-srcset for blur-up technique
        $this->assertStringContainsString('data-srcset=', $html);
        $this->assertStringContainsString('400w', $html);
        $this->assertStringContainsString('800w', $html);
        $this->assertStringContainsString('1200w', $html);
    }

    public function test_optimized_image_component_includes_sizes_attribute()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
        ])->render();

        // Should have data-sizes for blur-up technique
        $this->assertStringContainsString('data-sizes=', $html);
    }

    public function test_optimized_image_component_uses_blur_up_placeholder()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
            'blurUp' => true,
        ])->render();

        // Should have SVG placeholder
        $this->assertStringContainsString('data:image/svg+xml', $html);
        $this->assertStringContainsString('data-src=', $html);
        $this->assertStringContainsString('lazy-image', $html);
    }

    public function test_optimized_image_component_adds_lazy_image_class()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
            'blurUp' => true,
        ])->render();

        // Should include lazy-image class for JavaScript targeting
        $this->assertStringContainsString('lazy-image', $html);
    }

    public function test_optimized_image_component_uses_data_attributes_for_lazy_loading()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
            'blurUp' => true,
        ])->render();

        // Should use data attributes for lazy loading
        $this->assertStringContainsString('data-src=', $html);
        $this->assertStringContainsString('data-srcset=', $html);
        $this->assertStringContainsString('data-sizes=', $html);
    }

    public function test_optimized_image_component_respects_custom_sizes()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'sizes' => '(max-width: 768px) 100vw, 50vw',
            'eager' => false,
        ])->render();

        $this->assertStringContainsString('(max-width: 768px) 100vw, 50vw', $html);
    }

    public function test_optimized_image_component_includes_width_and_height()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'width' => 800,
            'height' => 600,
        ])->render();

        $this->assertStringContainsString('width="800"', $html);
        $this->assertStringContainsString('height="600"', $html);
    }

    public function test_post_card_component_uses_optimized_image()
    {
        $html = View::make('components.content.post-card', [
            'post' => $this->post,
        ])->render();

        // Should use optimized-image component
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    public function test_post_card_includes_image_alt_text()
    {
        $html = View::make('components.content.post-card', [
            'post' => $this->post,
        ])->render();

        $this->assertStringContainsString('alt="Test image description"', $html);
    }

    public function test_post_card_handles_missing_images_gracefully()
    {
        $postWithoutImage = Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'featured_image' => null,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $html = View::make('components.content.post-card', [
            'post' => $postWithoutImage,
        ])->render();

        // Should not throw errors
        $this->assertStringNotContainsString('Undefined', $html);
        $this->assertStringNotContainsString('Error', $html);
    }

    public function test_optimized_image_generates_responsive_srcset()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
        ])->render();

        // Should generate multiple sizes
        $this->assertStringContainsString('400w', $html);
        $this->assertStringContainsString('800w', $html);
        $this->assertStringContainsString('1200w', $html);
        $this->assertStringContainsString('1600w', $html);
    }

    public function test_optimized_image_uses_intersection_observer_for_lazy_loading()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
            'blurUp' => true,
        ])->render();

        // Should use data attributes for lazy loading with IntersectionObserver
        $this->assertStringContainsString('data-src=', $html);
        $this->assertStringContainsString('lazy-image', $html);
    }

    public function test_optimized_image_prevents_layout_shift_with_dimensions()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'width' => 800,
            'height' => 600,
        ])->render();

        // Should have width and height to prevent layout shift
        $this->assertStringContainsString('width="800"', $html);
        $this->assertStringContainsString('height="600"', $html);
    }

    public function test_hero_post_uses_eager_loading()
    {
        $html = View::make('components.content.hero-post', [
            'post' => $this->post,
        ])->render();

        // Hero post should use eager loading (above the fold)
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertStringContainsString('decoding="async"', $html);
    }

    public function test_optimized_image_with_blur_up_uses_placeholder()
    {
        $html = View::make('components.optimized-image', [
            'src' => '/storage/posts/test-image.jpg',
            'alt' => 'Test image',
            'eager' => false,
            'blurUp' => true,
        ])->render();

        // Should use SVG placeholder for blur-up effect
        $this->assertStringContainsString('data:image/svg+xml', $html);
        $this->assertStringContainsString('data-src=', $html);
    }
}

<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_card_renders_with_all_metadata(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $category = Category::factory()->create(['name' => 'Technology']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post Title',
            'excerpt' => 'This is a test excerpt',
            'featured_image' => 'test-image.jpg',
            'reading_time' => 5,
            'view_count' => 100,
        ]);

        $view = $this->blade('<x-content.post-card :post="$post" />', ['post' => $post]);

        $view->assertSee('Test Post Title');
        $view->assertSee('This is a test excerpt');
        $view->assertSee('Technology');
        $view->assertSee('John Doe');
        $view->assertSee('5 min read');
        $view->assertSee('100');
    }

    public function test_post_card_renders_featured_badge(): void
    {
        $post = Post::factory()->create([
            'is_featured' => true,
            'featured_image' => 'test-image.jpg',
        ]);

        $view = $this->blade('<x-content.post-card :post="$post" />', ['post' => $post]);

        $view->assertSee('Featured');
    }

    public function test_post_card_renders_trending_badge(): void
    {
        $post = Post::factory()->create([
            'is_trending' => true,
            'featured_image' => 'test-image.jpg',
        ]);

        $view = $this->blade('<x-content.post-card :post="$post" />', ['post' => $post]);

        $view->assertSee('Trending');
    }

    public function test_post_card_renders_without_image(): void
    {
        $post = Post::factory()->create([
            'title' => 'No Image Post',
            'featured_image' => 'test-image.jpg',
        ]);

        $view = $this->blade('<x-content.post-card :post="$post" :show-image="false" />', ['post' => $post]);

        $view->assertSee('No Image Post');
        $view->assertDontSee('test-image.jpg');
    }

    public function test_post_card_renders_without_excerpt(): void
    {
        $post = Post::factory()->create([
            'title' => 'No Excerpt Post',
            'excerpt' => 'This excerpt should not appear',
        ]);

        $view = $this->blade('<x-content.post-card :post="$post" :show-excerpt="false" />', ['post' => $post]);

        $view->assertSee('No Excerpt Post');
        $view->assertDontSee('This excerpt should not appear');
    }

    public function test_post_card_renders_with_different_sizes(): void
    {
        $post = Post::factory()->create(['title' => 'Size Test Post']);

        $smallView = $this->blade('<x-content.post-card :post="$post" size="small" />', ['post' => $post]);
        $smallView->assertSee('Size Test Post');

        $largeView = $this->blade('<x-content.post-card :post="$post" size="large" />', ['post' => $post]);
        $largeView->assertSee('Size Test Post');
    }

    public function test_post_grid_renders_posts_in_grid(): void
    {
        $posts = Post::factory()->count(3)->create();

        $view = $this->blade('<x-content.post-grid :posts="$posts" />', ['posts' => $posts]);

        $view->assertSee('grid', false);
        foreach ($posts as $post) {
            $view->assertSee($post->title);
        }
    }

    public function test_post_grid_renders_with_different_column_counts(): void
    {
        $posts = Post::factory()->count(2)->create();

        $twoColView = $this->blade('<x-content.post-grid :posts="$posts" :columns="2" />', ['posts' => $posts]);
        $twoColView->assertSee('md:grid-cols-2', false);

        $fourColView = $this->blade('<x-content.post-grid :posts="$posts" :columns="4" />', ['posts' => $posts]);
        $fourColView->assertSee('xl:grid-cols-4', false);
    }

    public function test_post_grid_shows_empty_state_when_no_posts(): void
    {
        $posts = collect([]);

        $view = $this->blade('<x-content.post-grid :posts="$posts" />', ['posts' => $posts]);

        $view->assertSee('No posts found');
    }

    public function test_post_list_renders_posts_in_list_layout(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        $posts = Post::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $view = $this->blade('<x-content.post-list :posts="$posts" />', ['posts' => $posts]);

        foreach ($posts as $post) {
            $view->assertSee($post->title);
            $view->assertSee($post->excerpt);
        }
        $view->assertSee('Jane Smith');
    }

    public function test_post_list_renders_with_thumbnail(): void
    {
        $post = Post::factory()->create([
            'featured_image' => 'thumbnail.jpg',
        ]);
        $posts = collect([$post]);

        $view = $this->blade('<x-content.post-list :posts="$posts" />', ['posts' => $posts]);

        $view->assertSee('thumbnail.jpg', false);
    }

    public function test_post_list_renders_without_image(): void
    {
        $post = Post::factory()->create([
            'title' => 'List Post Without Image',
            'featured_image' => null,
        ]);
        $posts = collect([$post]);

        $view = $this->blade('<x-content.post-list :posts="$posts" :show-image="false" />', ['posts' => $posts]);

        $view->assertSee('List Post Without Image');
    }

    public function test_post_list_shows_empty_state_when_no_posts(): void
    {
        $posts = collect([]);

        $view = $this->blade('<x-content.post-list :posts="$posts" />', ['posts' => $posts]);

        $view->assertSee('No posts found');
    }

    public function test_post_badge_renders_featured_type(): void
    {
        $view = $this->blade('<x-content.post-badge type="featured" />');

        $view->assertSee('Featured');
        $view->assertSee('bg-yellow-500', false);
    }

    public function test_post_badge_renders_trending_type(): void
    {
        $view = $this->blade('<x-content.post-badge type="trending" />');

        $view->assertSee('Trending');
        $view->assertSee('bg-red-500', false);
    }

    public function test_post_badge_renders_trending_with_rank(): void
    {
        $view = $this->blade('<x-content.post-badge type="trending" :rank="3" />');

        $view->assertSee('#3 Trending');
    }

    public function test_post_badge_renders_ai_generated_type(): void
    {
        $view = $this->blade('<x-content.post-badge type="ai-generated" />');

        $view->assertSee('AI Generated');
        $view->assertSee('bg-purple-500', false);
    }

    public function test_post_card_displays_engagement_metrics(): void
    {
        $post = Post::factory()->create([
            'view_count' => 250,
        ]);

        // Add comments count
        $post->comments_count = 5;

        $view = $this->blade('<x-content.post-card :post="$post" />', ['post' => $post]);

        $view->assertSee('250');
        $view->assertSee('5');
    }

    public function test_post_list_displays_all_metadata(): void
    {
        $user = User::factory()->create(['name' => 'Test Author']);
        $category = Category::factory()->create(['name' => 'Test Category']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Complete Metadata Post',
            'excerpt' => 'Full metadata excerpt',
            'reading_time' => 8,
            'view_count' => 500,
        ]);
        $posts = collect([$post]);

        $view = $this->blade('<x-content.post-list :posts="$posts" />', ['posts' => $posts]);

        $view->assertSee('Complete Metadata Post');
        $view->assertSee('Full metadata excerpt');
        $view->assertSee('Test Category');
        $view->assertSee('Test Author');
        $view->assertSee('8 min');
        $view->assertSee('500');
    }
}

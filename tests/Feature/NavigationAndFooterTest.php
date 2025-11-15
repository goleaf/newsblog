<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationAndFooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_renders_with_category_navigation(): void
    {
        // Create categories with posts
        $category = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
            'parent_id' => null,
        ]);

        Post::factory()->count(3)->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Technology');
        $response->assertSee(config('app.name'));
    }

    public function test_category_mega_menu_shows_subcategories(): void
    {
        // Create parent category
        $parent = Category::factory()->create([
            'name' => 'Programming',
            'status' => 'active',
            'parent_id' => null,
            'description' => 'All about programming',
        ]);

        // Create child categories
        $child = Category::factory()->create([
            'name' => 'PHP',
            'status' => 'active',
            'parent_id' => $parent->id,
        ]);

        Post::factory()->count(2)->create([
            'category_id' => $parent->id,
            'status' => 'published',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Programming');
        $response->assertSee('PHP');
        $response->assertSee('All about programming');
    }

    public function test_category_navigation_shows_recent_posts(): void
    {
        $category = Category::factory()->create([
            'name' => 'Web Development',
            'status' => 'active',
            'parent_id' => null,
        ]);

        $post = Post::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Latest Web Dev Trends',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Web Development');
        $response->assertSee('Latest Web Dev Trends');
    }

    public function test_footer_renders_with_all_sections(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for footer sections
        $response->assertSee('Quick Links');
        $response->assertSee('Resources');
        $response->assertSee('Legal');
        $response->assertSee('Follow Us');
        // Check copyright (HTML encoded)
        $response->assertSee(date('Y'));
        $response->assertSee(config('app.name'));
        $response->assertSee('All rights reserved');
    }

    public function test_footer_shows_legal_pages_when_available(): void
    {
        // Create legal pages
        Page::factory()->create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'status' => 'published',
        ]);

        Page::factory()->create([
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'status' => 'published',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('Terms of Service');
    }

    public function test_footer_social_links_render(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for social media section
        $response->assertSee('Follow Us');
        // SVG icons should be present (checking for viewBox attribute)
        $response->assertSee('viewBox');
    }

    public function test_mobile_navigation_renders(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for mobile menu button
        $response->assertSee('Toggle mobile menu');
    }

    public function test_category_navigation_horizontal_scroll_buttons(): void
    {
        // Create multiple categories to trigger scroll
        Category::factory()->count(10)->create([
            'status' => 'active',
            'parent_id' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for scroll buttons
        $response->assertSee('Scroll categories left');
        $response->assertSee('Scroll categories right');
    }

    public function test_navigation_shows_authenticated_user_menu(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        // User should see their name or profile link
        $response->assertSee($user->name);
    }

    public function test_footer_back_to_top_button_renders(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Back to Top');
    }

    public function test_category_menu_has_proper_aria_attributes(): void
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
            'parent_id' => null,
            'description' => 'Tech articles',
        ]);

        Post::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for ARIA attributes
        $response->assertSee('aria-haspopup');
        $response->assertSee('aria-expanded');
        $response->assertSee('aria-controls');
        $response->assertSee('aria-label');
    }

    public function test_category_menu_has_navigation_landmark(): void
    {
        Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
            'parent_id' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for nav element with aria-label
        $response->assertSee('<nav', false);
        $response->assertSee('Category navigation');
    }

    public function test_category_menu_scroll_buttons_have_aria_labels(): void
    {
        // Create multiple categories to trigger scroll buttons
        Category::factory()->count(10)->create([
            'status' => 'active',
            'parent_id' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Scroll categories left');
        $response->assertSee('Scroll categories right');
    }

    public function test_category_menu_post_counts_have_aria_labels(): void
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
            'parent_id' => null,
        ]);

        Post::factory()->count(5)->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check that post count has aria-label
        $response->assertSee('5 posts');
    }

    public function test_category_menu_links_have_focus_styles(): void
    {
        $category = Category::factory()->create([
            'name' => 'Technology',
            'status' => 'active',
            'parent_id' => null,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        // Check for focus ring classes
        $response->assertSee('focus:ring');
        $response->assertSee('focus:outline-none');
    }
}

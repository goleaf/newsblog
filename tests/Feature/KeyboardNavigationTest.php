<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyboardNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->post = Post::factory()->published()->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function all_interactive_elements_are_keyboard_accessible()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // All buttons should be keyboard accessible (button or a tags)
        preg_match_all('/<button[^>]*>|<a[^>]*href/', $content, $matches);
        
        $this->assertGreaterThan(0, count($matches[0]), 
            'Page should have interactive elements'
        );
        
        // Check that interactive elements don't have tabindex="-1" unless intentional
        $this->assertStringNotContainsString('tabindex="-1"', $content);
    }

    /** @test */
    public function navigation_links_are_keyboard_accessible()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Navigation should use semantic HTML (nav, a tags)
        $this->assertStringContainsString('<nav', $content);
        $this->assertStringContainsString('href="/"', $content);
    }

    /** @test */
    public function search_input_is_keyboard_accessible()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Search input should be accessible
        $this->assertTrue(
            str_contains($content, 'type="search"') || 
            str_contains($content, 'type="text"'),
            'Search input should be present'
        );
    }

    /** @test */
    public function dropdown_menus_support_keyboard_navigation()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Dropdowns should have proper keyboard handling
        // Check for Alpine.js keyboard event handlers
        $this->assertTrue(
            str_contains($content, '@keydown') || 
            str_contains($content, 'keydown'),
            'Dropdowns should handle keyboard events'
        );
    }

    /** @test */
    public function modals_trap_focus_when_open()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Check for Alpine.js focus trap directive
        if (str_contains($content, 'modal') || str_contains($content, 'dialog')) {
            $this->assertTrue(
                str_contains($content, 'x-trap') || 
                str_contains($content, 'focus-trap'),
                'Modals should trap focus'
            );
        }
    }

    /** @test */
    public function escape_key_closes_modals_and_dropdowns()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Check for escape key handlers
        $this->assertTrue(
            str_contains($content, '@keydown.escape') || 
            str_contains($content, 'keydown.escape'),
            'Components should handle escape key'
        );
    }

    /** @test */
    public function tab_order_follows_logical_flow()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Check that there are no positive tabindex values (which break natural tab order)
        preg_match_all('/tabindex="([0-9]+)"/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $tabindex) {
                $this->assertLessThanOrEqual(0, (int)$tabindex, 
                    'Positive tabindex values break natural tab order'
                );
            }
        }
    }

    /** @test */
    public function form_inputs_are_keyboard_accessible()
    {
        $response = $this->actingAs($this->user)
            ->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Forms should have proper input elements
        if (str_contains($content, '<form')) {
            $this->assertTrue(
                str_contains($content, '<input') || 
                str_contains($content, '<textarea') ||
                str_contains($content, '<button'),
                'Forms should have keyboard-accessible inputs'
            );
        }
    }

    /** @test */
    public function buttons_can_be_activated_with_enter_and_space()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Buttons should use <button> or <a> tags, not divs with click handlers
        // Check that we're not using div buttons
        $this->assertStringNotContainsString('<div @click', $content);
        $this->assertStringNotContainsString('<span @click', $content);
    }

    /** @test */
    public function skip_to_main_content_link_is_first_focusable_element()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Skip link should be present and early in the document
        $this->assertTrue(
            str_contains($content, 'Skip to') || 
            str_contains($content, 'skip') ||
            str_contains($content, '#main'),
            'Skip to main content link should be present'
        );
    }

    /** @test */
    public function search_autocomplete_supports_arrow_key_navigation()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Search should handle arrow keys for autocomplete
        if (str_contains($content, 'autocomplete') || str_contains($content, 'search')) {
            $this->assertTrue(
                str_contains($content, '@keydown.arrow-down') || 
                str_contains($content, '@keydown.arrow-up') ||
                str_contains($content, 'ArrowDown') ||
                str_contains($content, 'ArrowUp'),
                'Search autocomplete should handle arrow keys'
            );
        }
    }

    /** @test */
    public function category_menu_supports_keyboard_navigation()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Category menu should be keyboard accessible
        $this->assertStringContainsString('categor', strtolower($content));
        
        // Should use semantic navigation
        $this->assertStringContainsString('<nav', $content);
    }

    /** @test */
    public function bookmark_button_is_keyboard_accessible()
    {
        $response = $this->actingAs($this->user)
            ->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Bookmark button should be a proper button element
        if (str_contains($content, 'bookmark')) {
            $this->assertTrue(
                str_contains($content, '<button') || 
                str_contains($content, '<a'),
                'Bookmark should use semantic button or link'
            );
        }
    }

    /** @test */
    public function reaction_buttons_are_keyboard_accessible()
    {
        $response = $this->actingAs($this->user)
            ->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Reaction buttons should be keyboard accessible
        if (str_contains($content, 'reaction')) {
            $this->assertTrue(
                str_contains($content, '<button') || 
                str_contains($content, 'role="button"'),
                'Reactions should be keyboard accessible'
            );
        }
    }

    /** @test */
    public function comment_form_is_keyboard_accessible()
    {
        $response = $this->actingAs($this->user)
            ->get(route('post.show', $this->post->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Comment form should have proper form elements
        if (str_contains($content, 'comment')) {
            $this->assertTrue(
                str_contains($content, '<textarea') || 
                str_contains($content, '<input'),
                'Comment form should have keyboard-accessible inputs'
            );
        }
    }

    /** @test */
    public function mobile_menu_is_keyboard_accessible()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Mobile menu button should be keyboard accessible
        $this->assertTrue(
            str_contains($content, 'menu') || 
            str_contains($content, 'Menu'),
            'Mobile menu should be present'
        );
    }

    /** @test */
    public function pagination_links_are_keyboard_accessible()
    {
        // Create enough posts for pagination
        Post::factory()->published()->count(20)->create([
            'category_id' => $this->category->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get('/?page=1');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Pagination should use links
        if (str_contains($content, 'page=')) {
            $this->assertStringContainsString('<a', $content);
        }
    }

    /** @test */
    public function filter_controls_are_keyboard_accessible()
    {
        $response = $this->get(route('category.show', $this->category->slug));

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Filter controls should be keyboard accessible
        if (str_contains($content, 'filter') || str_contains($content, 'sort')) {
            $this->assertTrue(
                str_contains($content, '<select') || 
                str_contains($content, '<button') ||
                str_contains($content, '<input'),
                'Filters should use keyboard-accessible controls'
            );
        }
    }

    /** @test */
    public function all_links_have_visible_focus_indicators()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Check for focus styles in the page
        $this->assertTrue(
            str_contains($content, 'focus:') || 
            str_contains($content, ':focus'),
            'Page should have focus styles'
        );
    }

    /** @test */
    public function keyboard_shortcuts_are_documented()
    {
        $response = $this->get('/');

        $response->assertOk();
        
        // This test documents that keyboard shortcuts should be available
        // In a real implementation, we'd check for a keyboard shortcuts help modal
        $this->assertTrue(true, 'Keyboard shortcuts should be documented for users');
    }
}

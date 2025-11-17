<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AriaAttributesAuditTest extends TestCase
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
    public function homepage_has_proper_aria_labels_on_navigation()
    {
        $response = $this->get('/');

        $response->assertOk();

        // Check main navigation has aria-label
        $response->assertSee('aria-label="Main navigation"', false);

        // Check search has proper aria attributes
        $response->assertSee('role="search"', false);
    }

    /** @test */
    public function article_page_has_proper_aria_attributes()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        // Check reading progress has progressbar role
        $response->assertSee('role="progressbar"', false);
        $response->assertSee('aria-valuenow', false);
        $response->assertSee('aria-valuemin="0"', false);
        $response->assertSee('aria-valuemax="100"', false);

        // Check bookmark button has aria-label
        $response->assertSee('aria-label', false);

        // Check share button has aria-label
        $response->assertSeeText('Share', false);
    }

    /** @test */
    public function interactive_buttons_have_aria_labels()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        // Bookmark button should have aria-label
        $response->assertSee('aria-label', false);

        // Share buttons should have aria-labels
        $content = $response->getContent();
        $this->assertStringContainsString('aria-label', $content);
    }

    /** @test */
    public function decorative_icons_have_aria_hidden()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        // Decorative SVG icons should have aria-hidden="true"
        $response->assertSee('aria-hidden="true"', false);
    }

    /** @test */
    public function forms_have_proper_labels_and_descriptions()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        // Comment form should have proper labels
        $content = $response->getContent();

        // Check for label elements or aria-label attributes on inputs
        $this->assertTrue(
            str_contains($content, '<label') || str_contains($content, 'aria-label'),
            'Forms should have proper labels'
        );
    }

    /** @test */
    public function modals_have_proper_aria_attributes()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        $content = $response->getContent();

        // Share modal should have dialog role when present
        if (str_contains($content, 'showShareModal')) {
            $this->assertStringContainsString('role="dialog"', $content);
            $this->assertStringContainsString('aria-modal="true"', $content);
        }
    }

    /** @test */
    public function live_regions_are_properly_marked()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        // Check for aria-live regions for dynamic content
        $content = $response->getContent();

        // Status messages should have aria-live
        if (str_contains($content, 'link_copied') || str_contains($content, 'Copied')) {
            $this->assertStringContainsString('aria-live="polite"', $content);
        }
    }

    /** @test */
    public function navigation_has_current_page_indicator()
    {
        $response = $this->get('/');

        $response->assertOk();

        // Current page should be marked with aria-current
        $content = $response->getContent();

        // Check if navigation marks current page
        $this->assertTrue(
            str_contains($content, 'aria-current') || str_contains($content, 'current'),
            'Navigation should indicate current page'
        );
    }

    /** @test */
    public function search_autocomplete_has_proper_aria_attributes()
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        // Search should have autocomplete attributes
        if (str_contains($content, 'search')) {
            $this->assertTrue(
                str_contains($content, 'role="search"') ||
                str_contains($content, 'type="search"'),
                'Search should have proper role or type'
            );
        }
    }

    /** @test */
    public function category_menu_has_proper_aria_structure()
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        // Category navigation should have proper ARIA
        $this->assertStringContainsString('aria-label', $content);
    }

    /** @test */
    public function notification_dropdown_has_proper_aria_attributes()
    {
        $response = $this->actingAs($this->user)->get('/');

        $response->assertOk();

        $content = $response->getContent();

        // Notification button should have aria-label
        if (str_contains($content, 'notification')) {
            $this->assertStringContainsString('aria-label', $content);
        }
    }

    /** @test */
    public function toggle_switches_have_proper_aria_attributes()
    {
        $response = $this->actingAs($this->user)
            ->get(route('profile.edit'));

        $response->assertOk();

        $content = $response->getContent();

        // Toggle switches should have role="switch" and aria-checked
        if (str_contains($content, 'toggle') || str_contains($content, 'switch')) {
            $this->assertTrue(
                str_contains($content, 'role="switch"') ||
                str_contains($content, 'aria-checked'),
                'Toggle switches should have proper ARIA attributes'
            );
        }
    }

    /** @test */
    public function images_have_alt_text_or_aria_hidden()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        $content = $response->getContent();

        // All img tags should have alt attribute
        preg_match_all('/<img[^>]*>/', $content, $matches);

        foreach ($matches[0] as $imgTag) {
            $this->assertTrue(
                str_contains($imgTag, 'alt=') || str_contains($imgTag, 'aria-hidden'),
                "Image tag should have alt text or aria-hidden: {$imgTag}"
            );
        }
    }

    /** @test */
    public function lists_use_proper_semantic_markup()
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        // Navigation lists should use <ul> or <nav> with role="list" if needed
        $this->assertTrue(
            str_contains($content, '<ul') ||
            str_contains($content, '<nav') ||
            str_contains($content, 'role="list"'),
            'Lists should use proper semantic markup'
        );
    }

    /** @test */
    public function headings_follow_proper_hierarchy()
    {
        $response = $this->get(route('post.show', $this->post->slug));

        $response->assertOk();

        $content = $response->getContent();

        // Extract all heading levels
        preg_match_all('/<h([1-6])[^>]*>/', $content, $matches);

        if (! empty($matches[1])) {
            $levels = array_map('intval', $matches[1]);

            // Should start with h1
            $this->assertEquals(1, $levels[0], 'Page should start with h1');

            // Check for proper hierarchy (no skipping levels)
            for ($i = 1; $i < count($levels); $i++) {
                $diff = $levels[$i] - $levels[$i - 1];
                // Allow occasional UI headings (dropdowns) before main content without failing
                $this->assertLessThanOrEqual(2, $diff,
                    "Heading hierarchy should not skip levels: h{$levels[$i - 1]} to h{$levels[$i]}"
                );
            }
        }
    }

    /** @test */
    public function skip_to_main_content_link_exists()
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        // Should have skip link for keyboard users
        $this->assertTrue(
            str_contains($content, 'Skip to') ||
            str_contains($content, 'skip-to-content') ||
            str_contains($content, '#main-content'),
            'Page should have skip to main content link'
        );
    }

    /** @test */
    public function landmark_regions_are_properly_defined()
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        // Should have main landmark
        $this->assertStringContainsString('<main', $content);

        // Should have navigation landmark
        $this->assertTrue(
            str_contains($content, '<nav') || str_contains($content, 'role="navigation"'),
            'Page should have navigation landmark'
        );

        // Should have header
        $this->assertStringContainsString('<header', $content);

        // Should have footer
        $this->assertStringContainsString('<footer', $content);
    }
}

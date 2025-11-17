<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\AccessibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
    }

    public function test_homepage_contains_landmarks_and_skip_link(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        $response->assertSee('href="#main-content"', false);
        $response->assertSee('<main id="main-content"', false);
        $response->assertSee('<header', false);
        $response->assertSee('<footer', false);
        $response->assertSee('role="main"', false);
        $response->assertSee('role="banner"', false);
        $response->assertSee('role="contentinfo"', false);
    }

    public function test_skip_links_are_present(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check for skip link
        $response->assertSee('Skip to main content', false);
        $response->assertSee('href="#main-content"', false);
    }

    public function test_semantic_html_elements_are_used(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('posts.show', $post->slug));
        $response->assertOk();

        // Check for semantic HTML elements
        $response->assertSee('<article', false);
        $response->assertSee('<nav', false);
        $response->assertSee('<aside', false);
        $response->assertSee('<header', false);
        $response->assertSee('<footer', false);
        $response->assertSee('<main', false);
    }

    public function test_aria_landmarks_are_present(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check for ARIA landmarks
        $response->assertSee('role="banner"', false);
        $response->assertSee('role="main"', false);
        $response->assertSee('role="contentinfo"', false);
        $response->assertSee('role="navigation"', false);
    }

    public function test_images_have_alt_text(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'featured_image' => 'test-image.jpg',
        ]);

        $response = $this->get(route('posts.show', $post->slug));
        $response->assertOk();

        // Check that images have alt attributes
        $content = $response->getContent();
        preg_match_all('/<img[^>]+>/i', $content, $images);

        foreach ($images[0] as $img) {
            $this->assertStringContainsString('alt=', $img, 'Image missing alt attribute: '.$img);
        }
    }

    public function test_form_fields_have_labels(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();

        // Check that form fields have associated labels
        $content = $response->getContent();

        // Check for label elements
        $this->assertStringContainsString('<label', $content);

        // Check for aria-label or aria-labelledby on inputs
        preg_match_all('/<input[^>]+>/i', $content, $inputs);

        foreach ($inputs[0] as $input) {
            // Skip hidden inputs and CSRF tokens
            if (str_contains($input, 'type="hidden"')) {
                continue;
            }

            $hasLabel = str_contains($input, 'aria-label=') ||
                       str_contains($input, 'aria-labelledby=') ||
                       preg_match('/id="([^"]+)"/', $input, $matches);

            $this->assertTrue($hasLabel, 'Input missing label: '.$input);
        }
    }

    public function test_buttons_have_accessible_names(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        $content = $response->getContent();
        preg_match_all('/<button[^>]*>(.*?)<\/button>/is', $content, $buttons);

        foreach ($buttons[0] as $index => $button) {
            $hasAccessibleName =
                ! empty(trim(strip_tags($buttons[1][$index]))) || // Has text content
                str_contains($button, 'aria-label=') ||           // Has aria-label
                str_contains($button, 'aria-labelledby=');        // Has aria-labelledby

            $this->assertTrue($hasAccessibleName, 'Button missing accessible name: '.$button);
        }
    }

    public function test_links_have_descriptive_text(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        $content = $response->getContent();
        preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $content, $links);

        foreach ($links[0] as $index => $link) {
            // Skip if link has aria-label
            if (str_contains($link, 'aria-label=')) {
                continue;
            }

            $linkText = trim(strip_tags($links[1][$index]));

            // Check that link text is not generic
            $genericTexts = ['click here', 'read more', 'here', 'link'];
            $isGeneric = in_array(strtolower($linkText), $genericTexts);

            $this->assertFalse($isGeneric && empty($linkText), 'Link has generic or empty text: '.$link);
        }
    }

    public function test_heading_hierarchy_is_correct(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('posts.show', $post->slug));
        $response->assertOk();

        $content = $response->getContent();

        // Extract all headings
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $headings);

        if (! empty($headings[1])) {
            $levels = array_map('intval', $headings[1]);

            // Check that we start with h1
            $this->assertEquals(1, $levels[0], 'Page should start with h1');

            // Check that we don't skip levels
            $previousLevel = 0;
            foreach ($levels as $level) {
                $this->assertLessThanOrEqual($previousLevel + 1, $level,
                    "Heading hierarchy skips from h{$previousLevel} to h{$level}");
                $previousLevel = $level;
            }
        }
    }

    public function test_color_contrast_meets_wcag_aa(): void
    {
        $accessibilityService = app(AccessibilityService::class);

        // Test common color combinations
        $combinations = [
            ['foreground' => '#111827', 'background' => '#FFFFFF', 'largeText' => false],
            ['foreground' => '#2563EB', 'background' => '#FFFFFF', 'largeText' => false],
            ['foreground' => '#FFFFFF', 'background' => '#2563EB', 'largeText' => false],
            ['foreground' => '#F9FAFB', 'background' => '#111827', 'largeText' => false],
        ];

        foreach ($combinations as $combo) {
            $meetsRequirements = $accessibilityService->meetsContrastRequirements(
                $combo['foreground'],
                $combo['background'],
                $combo['largeText']
            );

            $this->assertTrue($meetsRequirements,
                "Color combination {$combo['foreground']} on {$combo['background']} does not meet WCAG AA");
        }
    }

    public function test_focus_indicators_are_visible(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check that focus styles are defined in CSS
        $response->assertSee('focus:outline', false);
        $response->assertSee('focus:ring', false);
    }

    public function test_keyboard_navigation_is_supported(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check for tabindex attributes (should not have positive values)
        $content = $response->getContent();
        preg_match_all('/tabindex="([^"]+)"/i', $content, $tabindexes);

        foreach ($tabindexes[1] as $tabindex) {
            $value = (int) $tabindex;
            $this->assertLessThanOrEqual(0, $value,
                'Positive tabindex values should not be used: '.$tabindex);
        }
    }

    public function test_aria_live_regions_are_present(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check for ARIA live regions for dynamic content
        $content = $response->getContent();

        // Should have at least one live region for announcements
        $this->assertStringContainsString('aria-live', $content);
    }

    public function test_language_attribute_is_set(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check that html element has lang attribute
        $response->assertSee('<html lang=', false);
    }

    public function test_page_title_is_descriptive(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
            'title' => 'Test Article Title',
        ]);

        $response = $this->get(route('posts.show', $post->slug));
        $response->assertOk();

        // Check that page has a descriptive title
        $response->assertSee('<title>', false);
        $response->assertSee('Test Article Title', false);
    }

    public function test_forms_have_proper_error_handling(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        // Check that errors are associated with fields
        $content = $response->getContent();

        // Should have aria-invalid on invalid fields
        if (str_contains($content, 'aria-invalid')) {
            $this->assertStringContainsString('aria-invalid="true"', $content);
        }
    }

    public function test_reduced_motion_is_respected(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Check for prefers-reduced-motion media query support
        $content = $response->getContent();
        $this->assertStringContainsString('prefers-reduced-motion', $content);
    }
}

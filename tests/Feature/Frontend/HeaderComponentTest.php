<?php

namespace Tests\Feature\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_renders_with_default_props(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('TechNewsHub');
        $response->assertSee('Home');
        $response->assertSee('Series');
    }

    public function test_header_shows_logo_and_site_name(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(config('app.name'));
        $response->assertSeeInOrder(['TechNewsHub Home', config('app.name')]);
    }

    public function test_header_navigation_links_are_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('href="'.route('home').'"', false);
        $response->assertSee('href="'.route('series.index').'"', false);
    }

    public function test_header_shows_search_button(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Open search');
        $response->assertSee('@click="$dispatch(\'open-search\')"', false);
    }

    public function test_header_includes_dark_mode_toggle(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-data="themeToggle"', false);
    }

    public function test_header_shows_user_menu_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        // User menu component is included in header
        $response->assertSee($user->name);
    }

    public function test_header_shows_mobile_menu_button(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Toggle mobile menu');
        $response->assertSee('@click="mobileMenuOpen = !mobileMenuOpen"', false);
    }

    public function test_header_has_proper_aria_labels(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('role="banner"', false);
        $response->assertSee('aria-label="Main navigation"', false);
        $response->assertSee('aria-label="TechNewsHub Home"', false);
        $response->assertSee('aria-label="Open search"', false);
        $response->assertSee('aria-label="Toggle mobile menu"', false);
    }

    public function test_header_marks_current_page_with_aria_current(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('aria-current="page"', false);
    }

    public function test_header_includes_alpine_scroll_behavior(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('scrolled: false', false);
        $response->assertSee('hidden: false', false);
        $response->assertSee('lastScroll: 0', false);
        $response->assertSee('mobileMenuOpen: false', false);
    }

    public function test_header_has_sticky_positioning_by_default(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('sticky top-0 z-50', false);
    }

    public function test_header_includes_mobile_menu_component(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // Mobile menu is rendered as part of the header
        $response->assertSee('mobileMenuOpen', false);
    }

    public function test_header_navigation_highlights_active_route(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // Should show aria-current="page" for home link when on home page
        $response->assertSee('aria-current="page"', false);
    }

    public function test_header_responsive_classes_are_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('hidden sm:inline', false); // Logo text hidden on mobile
        $response->assertSee('hidden lg:flex', false); // Desktop nav hidden on mobile
        $response->assertSee('lg:hidden', false); // Mobile menu button hidden on desktop
        $response->assertSee('hidden lg:block', false); // User menu responsive
    }

    public function test_header_includes_transition_classes(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('transition-all duration-300 ease-in-out', false);
        $response->assertSee('transition-colors', false);
    }

    public function test_header_dark_mode_classes_are_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('dark:bg-gray-900', false);
        $response->assertSee('dark:text-white', false);
        $response->assertSee('dark:text-gray-300', false);
        $response->assertSee('dark:hover:text-blue-400', false);
    }

    public function test_header_includes_x_cloak_for_alpine(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-cloak', false);
    }

    public function test_header_max_width_container_is_present(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('max-w-7xl mx-auto', false);
    }
}

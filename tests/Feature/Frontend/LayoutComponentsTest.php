<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutComponentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_menu_toggle_button_exists(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Toggle mobile menu', false);
        $response->assertSee('@click="mobileMenuOpen = !mobileMenuOpen"', false);
    }

    public function test_mobile_menu_has_alpine_data_attribute(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-show="mobileMenuOpen"', false);
        $response->assertSee('x-cloak', false);
    }

    public function test_mobile_menu_has_proper_aria_attributes(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('aria-label="Mobile navigation menu"', false);
    }

    public function test_mobile_menu_has_overlay_with_click_handler(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('@click="mobileMenuOpen = false"', false);
        $response->assertSee('@click.away="mobileMenuOpen = false"', false);
    }

    public function test_mobile_menu_has_slide_in_animations(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-transition:enter="transition ease-in-out duration-300 transform"', false);
        $response->assertSee('x-transition:enter-start="-translate-x-full"', false);
        $response->assertSee('x-transition:enter-end="translate-x-0"', false);
    }

    public function test_mobile_menu_has_focus_trap(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-trap="mobileMenuOpen"', false);
    }

    public function test_mobile_menu_header_component_exists(): void
    {
        $this->assertTrue(
            file_exists(resource_path('views/components/navigation/mobile-menu-header.blade.php')),
            'Mobile menu header component does not exist'
        );
    }

    public function test_mobile_menu_navigation_component_exists(): void
    {
        $this->assertTrue(
            file_exists(resource_path('views/components/navigation/mobile-menu-nav.blade.php')),
            'Mobile menu navigation component does not exist'
        );
    }

    public function test_mobile_menu_user_component_exists(): void
    {
        $this->assertTrue(
            file_exists(resource_path('views/components/navigation/mobile-menu-user.blade.php')),
            'Mobile menu user component does not exist'
        );
    }

    public function test_mobile_menu_closes_on_link_click(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // Mobile menu links should close menu on click
        $response->assertSee('@click="mobileMenuOpen = false"', false);
    }

    public function test_dark_mode_toggle_component_exists(): void
    {
        $this->assertTrue(
            file_exists(resource_path('views/components/ui/dark-mode-toggle.blade.php')),
            'Dark mode toggle component does not exist'
        );
    }

    public function test_dark_mode_toggle_uses_theme_store(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-data="themeToggle"', false);
    }

    public function test_dark_mode_theme_store_has_local_storage_persistence(): void
    {
        $storeContent = file_get_contents(resource_path('js/stores/theme.js'));

        $this->assertStringContainsString('localStorage.getItem(\'theme\')', $storeContent);
        $this->assertStringContainsString('localStorage.setItem(\'theme\'', $storeContent);
    }

    public function test_dark_mode_initializes_from_local_storage(): void
    {
        $storeContent = file_get_contents(resource_path('js/stores/theme.js'));

        $this->assertStringContainsString('localStorage.getItem(\'theme\') || \'system\'', $storeContent);
    }

    public function test_dark_mode_has_toggle_function(): void
    {
        $storeContent = file_get_contents(resource_path('js/stores/theme.js'));

        $this->assertStringContainsString('toggle()', $storeContent);
        $this->assertStringContainsString('localStorage.setItem(\'theme\'', $storeContent);
    }

    public function test_dark_mode_has_apply_theme_function(): void
    {
        $storeContent = file_get_contents(resource_path('js/stores/theme.js'));

        $this->assertStringContainsString('applyTheme()', $storeContent);
        $this->assertStringContainsString('document.documentElement.classList.add(\'dark\')', $storeContent);
        $this->assertStringContainsString('document.documentElement.classList.remove(\'dark\')', $storeContent);
    }

    public function test_dark_mode_respects_system_preference(): void
    {
        $storeContent = file_get_contents(resource_path('js/stores/theme.js'));

        $this->assertStringContainsString('window.matchMedia(\'(prefers-color-scheme: dark)\')', $storeContent);
        $this->assertStringContainsString('addEventListener(\'change\'', $storeContent);
    }

    public function test_dark_mode_layout_has_initialization_script(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('localStorage.getItem(\'theme\')', false);
        $response->assertSee('document.documentElement.classList.add(\'dark\')', false);
    }

    public function test_dark_mode_has_three_modes(): void
    {
        $storeContent = file_get_contents(resource_path('js/stores/theme.js'));

        $this->assertStringContainsString('\'light\'', $storeContent);
        $this->assertStringContainsString('\'dark\'', $storeContent);
        $this->assertStringContainsString('\'system\'', $storeContent);
    }

    public function test_sticky_navigation_has_alpine_data(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('x-data="headerState"', false);
        $response->assertSee('x-init="initScrollBehavior()"', false);
    }

    public function test_sticky_navigation_has_scroll_detection(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('scrolled: false', false);
        $response->assertSee('checkScrollPosition()', false);
        $response->assertSee('addEventListener(\'scroll\'', false);
    }

    public function test_sticky_navigation_has_scroll_threshold(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('scrollThreshold = 50', $headerContent);
        $this->assertStringContainsString('this.scrolled = currentScroll > scrollThreshold', $headerContent);
    }

    public function test_sticky_navigation_has_reduced_height_when_scrolled(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(':class="scrolled ? \'h-14\' : \'h-16\'"', false);
    }

    public function test_sticky_navigation_has_shadow_when_scrolled(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('shadow-lg', $headerContent);
        $this->assertStringContainsString('this.scrolled && !transparent', $headerContent);
    }

    public function test_sticky_navigation_has_backdrop_blur(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('backdrop-blur-sm', $headerContent);
    }

    public function test_sticky_navigation_has_auto_hide_on_scroll_down(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('hidden: false', $headerContent);
        $this->assertStringContainsString('currentScroll > this.lastScroll && currentScroll > 100', $headerContent);
        $this->assertStringContainsString('this.hidden = true', $headerContent);
    }

    public function test_sticky_navigation_uses_request_animation_frame(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('requestAnimationFrame', $headerContent);
        $this->assertStringContainsString('passive: true', $headerContent);
    }

    public function test_sticky_navigation_has_smooth_transitions(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('transition-all duration-300 ease-in-out', false);
    }

    public function test_sticky_navigation_logo_scales_when_scrolled(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(':class="scrolled ? \'scale-95\' : \'scale-100\'"', false);
        $response->assertSee('transition-transform duration-300 ease-in-out', false);
    }

    public function test_sticky_navigation_has_sticky_positioning(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('sticky top-0 z-50', false);
    }

    public function test_mobile_menu_has_responsive_classes(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('lg:hidden', false); // Mobile menu only visible on mobile
    }

    public function test_mobile_menu_has_close_button(): void
    {
        $menuHeaderContent = file_get_contents(resource_path('views/components/navigation/mobile-menu-header.blade.php'));

        $this->assertStringContainsString('@click="mobileMenuOpen = false"', $menuHeaderContent);
        $this->assertStringContainsString('Close menu', $menuHeaderContent);
    }

    public function test_dark_mode_toggle_has_click_handler(): void
    {
        $toggleContent = file_get_contents(resource_path('views/components/ui/dark-mode-toggle.blade.php'));

        $this->assertStringContainsString('@click="toggle()"', $toggleContent);
    }

    public function test_dark_mode_toggle_has_initialization(): void
    {
        $toggleContent = file_get_contents(resource_path('views/components/ui/dark-mode-toggle.blade.php'));

        $this->assertStringContainsString('x-init="init()"', $toggleContent);
    }

    public function test_sticky_navigation_initializes_scroll_behavior(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('initScrollBehavior()', $headerContent);
        $this->assertStringContainsString('this.checkScrollPosition()', $headerContent);
    }

    public function test_sticky_navigation_has_dynamic_classes_function(): void
    {
        $headerContent = file_get_contents(resource_path('views/components/layout/header.blade.php'));

        $this->assertStringContainsString('getHeaderClasses', $headerContent);
        $this->assertStringContainsString(':class="getHeaderClasses', $headerContent);
    }
}

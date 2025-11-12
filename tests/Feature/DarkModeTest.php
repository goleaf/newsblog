<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_includes_dark_mode_toggle(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('darkMode');
        $response->assertSee('localStorage');
    }

    public function test_layout_includes_dark_mode_initialization_script(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Prevent flash of unstyled content', false);
        $response->assertSee('document.documentElement.classList.add(\'dark\')', false);
    }

    public function test_layout_has_alpine_dark_mode_data(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('x-data', false);
        $response->assertSee(':class="{ \'dark\': darkMode }"', false);
    }

    public function test_dark_mode_toggle_component_exists(): void
    {
        $this->assertTrue(
            file_exists(resource_path('views/components/dark-mode-toggle.blade.php')),
            'Dark mode toggle component does not exist'
        );
    }

    public function test_tailwind_config_has_dark_mode_enabled(): void
    {
        $configContent = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString(
            'darkMode: \'class\'',
            $configContent,
            'Tailwind config does not have dark mode enabled with class strategy'
        );
    }

    public function test_body_has_dark_mode_classes(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('dark:bg-gray-900', false);
        $response->assertSee('dark:text-gray-100', false);
    }

    public function test_navigation_has_dark_mode_classes(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('dark:bg-gray-800', false);
    }

    public function test_guest_layout_includes_dark_mode_support(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('darkMode');
        $response->assertSee('localStorage');
        $response->assertSee('dark:bg-gray-900', false);
    }

    public function test_dark_mode_respects_system_preference(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('window.matchMedia(\'(prefers-color-scheme: dark)\')', false);
    }

    public function test_dark_mode_toggle_has_sun_and_moon_icons(): void
    {
        $toggleContent = file_get_contents(resource_path('views/components/dark-mode-toggle.blade.php'));

        $this->assertStringContainsString('x-show="darkMode"', $toggleContent);
        $this->assertStringContainsString('x-show="!darkMode"', $toggleContent);
        $this->assertStringContainsString('Sun icon', $toggleContent);
        $this->assertStringContainsString('Moon icon', $toggleContent);
    }
}

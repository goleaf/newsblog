<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CssOptimizationTest extends TestCase
{
    /**
     * Test that critical CSS component exists and renders correctly.
     */
    public function test_critical_css_component_exists(): void
    {
        $componentPath = resource_path('views/components/optimized-css.blade.php');

        $this->assertFileExists($componentPath, 'Optimized CSS component should exist');
    }

    /**
     * Test that critical CSS component renders in development mode.
     */
    public function test_critical_css_renders_in_development(): void
    {
        config(['app.env' => 'local']);

        $view = $this->blade('<x-optimized-css page="home" />');

        // In development, should load CSS via Vite (check for build directory)
        $view->assertSee('/build/', false);
        $view->assertSee('.css', false);
    }

    /**
     * Test that PostCSS configuration exists.
     */
    public function test_postcss_config_exists(): void
    {
        $configPath = base_path('postcss.config.js');

        $this->assertFileExists($configPath, 'PostCSS configuration should exist');

        $content = File::get($configPath);

        // Verify PurgeCSS is configured
        $this->assertStringContainsString('purgecss', $content);
        $this->assertStringContainsString('cssnano', $content);
        $this->assertStringContainsString('autoprefixer', $content);
    }

    /**
     * Test that critical CSS files exist.
     */
    public function test_critical_css_files_exist(): void
    {
        $criticalFiles = [
            'resources/css/critical.css',
            'resources/css/critical-home.css',
            'resources/css/critical-article.css',
        ];

        foreach ($criticalFiles as $file) {
            $path = base_path($file);
            $this->assertFileExists($path, "Critical CSS file {$file} should exist");
        }
    }

    /**
     * Test that Vite config includes critical CSS files.
     */
    public function test_vite_config_includes_critical_css(): void
    {
        $configPath = base_path('vite.config.js');

        $this->assertFileExists($configPath, 'Vite configuration should exist');

        $content = File::get($configPath);

        // Verify critical CSS files are included
        $this->assertStringContainsString('critical.css', $content);
        $this->assertStringContainsString('critical-home.css', $content);
        $this->assertStringContainsString('critical-article.css', $content);
    }

    /**
     * Test that CSS optimization scripts exist.
     */
    public function test_css_optimization_scripts_exist(): void
    {
        $scripts = [
            'scripts/optimize-css.js',
            'scripts/extract-critical-css.js',
        ];

        foreach ($scripts as $script) {
            $path = base_path($script);
            $this->assertFileExists($path, "Script {$script} should exist");
        }
    }

    /**
     * Test that package.json includes optimization scripts.
     */
    public function test_package_json_includes_optimization_scripts(): void
    {
        $packagePath = base_path('package.json');

        $this->assertFileExists($packagePath, 'package.json should exist');

        $content = File::get($packagePath);

        // Verify optimization scripts are defined
        $this->assertStringContainsString('build:optimized', $content);
        $this->assertStringContainsString('build:production', $content);
        $this->assertStringContainsString('extract-critical', $content);
        $this->assertStringContainsString('analyze-css', $content);
    }

    /**
     * Test that critical CSS is small enough to inline.
     */
    public function test_critical_css_size_is_within_target(): void
    {
        $criticalFiles = [
            'resources/css/critical.css',
            'resources/css/critical-home.css',
            'resources/css/critical-article.css',
        ];

        $maxSize = 14 * 1024; // 14 KB target for inline CSS

        foreach ($criticalFiles as $file) {
            $path = base_path($file);

            if (File::exists($path)) {
                $size = File::size($path);

                $this->assertLessThanOrEqual(
                    $maxSize,
                    $size,
                    "Critical CSS file {$file} should be under 14 KB (current: ".round($size / 1024, 2).' KB)'
                );
            }
        }
    }

    /**
     * Test that homepage uses optimized CSS loading.
     */
    public function test_homepage_uses_optimized_css_loading(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        // Should include CSS files in the head
        $response->assertSee('<link', false);
        $response->assertSee('.css', false);
    }

    /**
     * Test that PurgeCSS safelist includes necessary classes.
     */
    public function test_purgecss_safelist_includes_necessary_classes(): void
    {
        $configPath = base_path('postcss.config.js');
        $content = File::get($configPath);

        // Verify important classes are safelisted
        $necessaryPatterns = [
            'prose',
            'dark',
            'x-cloak',
            'animate-',
            'transition-',
        ];

        foreach ($necessaryPatterns as $pattern) {
            $this->assertStringContainsString(
                $pattern,
                $content,
                "PurgeCSS safelist should include pattern: {$pattern}"
            );
        }
    }

    /**
     * Test that CSS loading includes preload hints.
     */
    public function test_css_loading_includes_preload_hints(): void
    {
        $componentPath = resource_path('views/components/optimized-css.blade.php');
        $content = File::get($componentPath);

        // Verify preload and preconnect hints
        $this->assertStringContainsString('rel="preload"', $content);
        $this->assertStringContainsString('rel="preconnect"', $content);
        $this->assertStringContainsString('as="style"', $content);
    }

    /**
     * Test that non-critical CSS is deferred.
     */
    public function test_non_critical_css_is_deferred(): void
    {
        $componentPath = resource_path('views/components/optimized-css.blade.php');
        $content = File::get($componentPath);

        // Verify deferred loading strategy
        $this->assertStringContainsString('onload=', $content);
        $this->assertStringContainsString('noscript', $content);
    }

    /**
     * Test that Tailwind config has proper content paths for PurgeCSS.
     */
    public function test_tailwind_config_has_proper_content_paths(): void
    {
        $configPath = base_path('tailwind.config.js');

        $this->assertFileExists($configPath, 'Tailwind configuration should exist');

        $content = File::get($configPath);

        // Verify content paths are configured
        $this->assertStringContainsString('resources/views/**/*.blade.php', $content);
        $this->assertStringContainsString('content:', $content);
    }
}

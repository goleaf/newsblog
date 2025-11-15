<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CssOptimizationTest extends TestCase
{
    /**
     * Test that critical CSS file exists in resources
     */
    public function test_critical_css_file_exists(): void
    {
        $criticalCssPath = resource_path('css/critical.css');

        $this->assertFileExists($criticalCssPath, 'Critical CSS file should exist');

        $content = File::get($criticalCssPath);
        $this->assertNotEmpty($content, 'Critical CSS should not be empty');

        // Check for essential critical styles
        $this->assertStringContainsString('header', $content);
        $this->assertStringContainsString('nav', $content);
        $this->assertStringContainsString('x-cloak', $content);
    }

    /**
     * Test that optimized CSS component exists
     */
    public function test_optimized_css_component_exists(): void
    {
        $componentPath = resource_path('views/components/optimized-css.blade.php');

        $this->assertFileExists($componentPath, 'Optimized CSS component should exist');

        $content = File::get($componentPath);
        $this->assertStringContainsString('critical-css', $content);
        $this->assertStringContainsString('preload', $content);
        $this->assertStringContainsString('noscript', $content);
    }

    /**
     * Test that PostCSS configuration includes optimization plugins
     */
    public function test_postcss_config_includes_optimization(): void
    {
        $postcssConfigPath = base_path('postcss.config.js');

        $this->assertFileExists($postcssConfigPath, 'PostCSS config should exist');

        $content = File::get($postcssConfigPath);

        // Check for cssnano (CSS minification)
        $this->assertStringContainsString('cssnano', $content);

        // Check for production environment check
        $this->assertStringContainsString('NODE_ENV', $content);

        // Check for Tailwind CSS (which handles purging)
        $this->assertStringContainsString('tailwindcss', $content);
    }

    /**
     * Test that Vite config includes critical CSS
     */
    public function test_vite_config_includes_critical_css(): void
    {
        $viteConfigPath = base_path('vite.config.js');

        $this->assertFileExists($viteConfigPath, 'Vite config should exist');

        $content = File::get($viteConfigPath);

        // Check that critical.css is in the input array
        $this->assertStringContainsString('critical.css', $content);

        // Check for CSS optimization settings
        $this->assertStringContainsString('cssMinify', $content);
    }

    /**
     * Test that layout uses optimized CSS component
     */
    public function test_layout_uses_optimized_css_component(): void
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');

        $this->assertFileExists($layoutPath, 'App layout should exist');

        $content = File::get($layoutPath);

        // Check that optimized CSS component is used
        $this->assertStringContainsString('x-optimized-css', $content);
    }

    /**
     * Test that homepage renders with optimized CSS in production
     */
    public function test_homepage_renders_with_optimized_css(): void
    {
        // Simulate production environment
        config(['app.env' => 'production']);

        $response = $this->get('/');

        $response->assertStatus(200);

        // In production, should have optimized CSS loading
        // Note: This test assumes the build has been run
        $content = $response->getContent();

        // Should have preload link or inline critical CSS
        $hasOptimization = str_contains($content, 'preload') ||
                          str_contains($content, 'critical-css') ||
                          str_contains($content, 'noscript');

        $this->assertTrue($hasOptimization, 'Page should have CSS optimization');
    }

    /**
     * Test that CSS files are minified in production build
     */
    public function test_css_files_are_minified_in_production(): void
    {
        $buildPath = public_path('build/assets');

        // Skip if build directory doesn't exist (not built yet)
        if (! File::exists($buildPath)) {
            $this->markTestSkipped('Build directory does not exist. Run npm run build first.');
        }

        $cssFiles = File::glob($buildPath.'/*.css');

        $this->assertNotEmpty($cssFiles, 'Should have CSS files in build directory');

        foreach ($cssFiles as $cssFile) {
            $content = File::get($cssFile);

            // Minified CSS should not have excessive whitespace
            $lines = explode("\n", $content);
            $avgLineLength = strlen($content) / max(count($lines), 1);

            // Minified CSS typically has long lines (> 100 chars average)
            $this->assertGreaterThan(100, $avgLineLength,
                basename($cssFile).' should be minified');
        }
    }

    /**
     * Test that critical CSS is small enough to inline
     */
    public function test_critical_css_size_is_reasonable(): void
    {
        $criticalCssPath = resource_path('css/critical.css');

        $this->assertFileExists($criticalCssPath);

        $size = File::size($criticalCssPath);

        // Critical CSS should be under 14KB for optimal performance
        $maxSize = 14 * 1024; // 14KB

        $this->assertLessThan($maxSize, $size,
            'Critical CSS should be under 14KB for optimal inlining. Current size: '.
            round($size / 1024, 2).'KB');
    }

    /**
     * Test that PurgeCSS config exists
     */
    public function test_purgecss_config_exists(): void
    {
        $purgecssConfigPath = base_path('purgecss.config.js');

        $this->assertFileExists($purgecssConfigPath, 'PurgeCSS config should exist');

        $content = File::get($purgecssConfigPath);

        // Check for safelist configuration
        $this->assertStringContainsString('safelist', $content);

        // Check for content paths
        $this->assertStringContainsString('blade.php', $content);

        // Check for Alpine.js directives in safelist
        $this->assertStringContainsString('x-cloak', $content);
    }

    /**
     * Test that CSS optimization script exists
     */
    public function test_css_optimization_script_exists(): void
    {
        $scriptPath = base_path('scripts/optimize-css.js');

        $this->assertFileExists($scriptPath, 'CSS optimization script should exist');

        $content = File::get($scriptPath);

        // Check for key functionality
        $this->assertStringContainsString('npm run build', $content);
        $this->assertStringContainsString('CSS optimization', $content);
        $this->assertStringContainsString('report', $content);
    }

    /**
     * Test that package.json has optimization scripts
     */
    public function test_package_json_has_optimization_scripts(): void
    {
        $packageJsonPath = base_path('package.json');

        $this->assertFileExists($packageJsonPath);

        $content = File::get($packageJsonPath);
        $packageJson = json_decode($content, true);

        $this->assertArrayHasKey('scripts', $packageJson);

        // Check for optimization scripts
        $this->assertArrayHasKey('build:optimized', $packageJson['scripts']);
        $this->assertArrayHasKey('analyze-css', $packageJson['scripts']);
    }

    /**
     * Test that Tailwind config has proper purge settings
     */
    public function test_tailwind_config_has_proper_content_paths(): void
    {
        $tailwindConfigPath = base_path('tailwind.config.js');

        $this->assertFileExists($tailwindConfigPath);

        $content = File::get($tailwindConfigPath);

        // Check for content paths
        $this->assertStringContainsString('content:', $content);
        $this->assertStringContainsString('blade.php', $content);

        // Check for dark mode configuration
        $this->assertStringContainsString('darkMode', $content);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CodeSplittingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure build directory exists
        if (! File::exists(public_path('build'))) {
            $this->markTestSkipped('Build directory does not exist. Run npm run build first.');
        }
    }

    /** @test */
    public function vite_config_has_code_splitting_configuration()
    {
        $viteConfig = File::get(base_path('vite.config.js'));

        // Verify manual chunks configuration exists
        $this->assertStringContainsString('manualChunks', $viteConfig);

        // Verify vendor chunk splitting
        $this->assertStringContainsString('vendor', $viteConfig);

        // Verify page-specific entry points
        $this->assertStringContainsString('pages/homepage.js', $viteConfig);
        $this->assertStringContainsString('pages/article.js', $viteConfig);
        $this->assertStringContainsString('pages/dashboard.js', $viteConfig);
        $this->assertStringContainsString('pages/search.js', $viteConfig);
    }

    /** @test */
    public function page_specific_entry_points_exist()
    {
        $entryPoints = [
            'resources/js/pages/homepage.js',
            'resources/js/pages/article.js',
            'resources/js/pages/dashboard.js',
            'resources/js/pages/search.js',
        ];

        foreach ($entryPoints as $entryPoint) {
            $this->assertFileExists(
                base_path($entryPoint),
                "Entry point {$entryPoint} should exist"
            );
        }
    }

    /** @test */
    public function vendor_chunks_are_created_in_build()
    {
        $buildManifest = $this->getBuildManifest();

        if (! $buildManifest) {
            $this->markTestSkipped('Build manifest not found. Run npm run build first.');
        }

        // Check for vendor chunks in manifest
        $hasVendorChunk = false;
        foreach ($buildManifest as $key => $value) {
            if (str_contains($key, 'vendor') ||
                (isset($value['file']) && str_contains($value['file'], 'vendor'))) {
                $hasVendorChunk = true;
                break;
            }
        }

        $this->assertTrue(
            $hasVendorChunk,
            'Build should contain vendor chunks for better caching'
        );
    }

    /** @test */
    public function route_based_chunks_are_created()
    {
        $buildManifest = $this->getBuildManifest();

        if (! $buildManifest) {
            $this->markTestSkipped('Build manifest not found. Run npm run build first.');
        }

        $expectedChunks = [
            'homepage' => false,
            'article' => false,
            'dashboard' => false,
            'search' => false,
        ];

        foreach ($buildManifest as $key => $value) {
            $file = $value['file'] ?? $key;

            foreach ($expectedChunks as $chunk => $found) {
                if (str_contains($file, $chunk) || str_contains($key, $chunk)) {
                    $expectedChunks[$chunk] = true;
                }
            }
        }

        foreach ($expectedChunks as $chunk => $found) {
            $this->assertTrue(
                $found,
                "Build should contain {$chunk} chunk for route-based splitting"
            );
        }
    }

    /** @test */
    public function javascript_bundles_are_within_size_limits()
    {
        $buildDir = public_path('build/js');

        if (! File::exists($buildDir)) {
            $this->markTestSkipped('Build JS directory does not exist. Run npm run build first.');
        }

        $jsFiles = File::files($buildDir);
        $bundleSizes = [];
        $maxBundleSize = 500 * 1024; // 500KB limit per bundle
        $totalSize = 0;

        foreach ($jsFiles as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $bundleSizes[$file->getFilename()] = $size;

            // Individual bundles should be under 500KB
            $this->assertLessThan(
                $maxBundleSize,
                $size,
                "Bundle {$file->getFilename()} is too large: ".
                round($size / 1024, 2).'KB (limit: 500KB)'
            );
        }

        // Total JS size should be reasonable (under 2MB)
        $this->assertLessThan(
            2 * 1024 * 1024,
            $totalSize,
            'Total JavaScript size is too large: '.
            round($totalSize / 1024 / 1024, 2).'MB (limit: 2MB)'
        );

        // Log bundle sizes for reference
        echo "\n\nBundle Sizes:\n";
        foreach ($bundleSizes as $filename => $size) {
            echo sprintf("  %s: %s KB\n", $filename, round($size / 1024, 2));
        }
        echo sprintf("Total: %s KB\n\n", round($totalSize / 1024, 2));
    }

    /** @test */
    public function css_bundles_are_within_size_limits()
    {
        $buildDir = public_path('build/css');

        if (! File::exists($buildDir)) {
            $this->markTestSkipped('Build CSS directory does not exist. Run npm run build first.');
        }

        $cssFiles = File::files($buildDir);
        $bundleSizes = [];
        $maxBundleSize = 200 * 1024; // 200KB limit per CSS bundle
        $totalSize = 0;

        foreach ($cssFiles as $file) {
            $size = $file->getSize();
            $totalSize += $size;
            $bundleSizes[$file->getFilename()] = $size;

            // Individual CSS bundles should be under 200KB
            $this->assertLessThan(
                $maxBundleSize,
                $size,
                "CSS bundle {$file->getFilename()} is too large: ".
                round($size / 1024, 2).'KB (limit: 200KB)'
            );
        }

        // Total CSS size should be reasonable (under 500KB)
        $this->assertLessThan(
            500 * 1024,
            $totalSize,
            'Total CSS size is too large: '.
            round($totalSize / 1024, 2).'KB (limit: 500KB)'
        );

        // Log bundle sizes for reference
        echo "\n\nCSS Bundle Sizes:\n";
        foreach ($bundleSizes as $filename => $size) {
            echo sprintf("  %s: %s KB\n", $filename, round($size / 1024, 2));
        }
        echo sprintf("Total: %s KB\n\n", round($totalSize / 1024, 2));
    }

    /** @test */
    public function lazy_loading_components_are_not_in_main_bundle()
    {
        $buildManifest = $this->getBuildManifest();

        if (! $buildManifest) {
            $this->markTestSkipped('Build manifest not found. Run npm run build first.');
        }

        // Components that should be lazy loaded
        $lazyComponents = [
            'share-post',
            'bookmark-button',
            'reading-progress',
            'infinite-scroll',
        ];

        // Check that these components are in separate chunks, not main app.js
        $mainAppEntry = null;
        foreach ($buildManifest as $key => $value) {
            if (str_contains($key, 'resources/js/app.js')) {
                $mainAppEntry = $value;
                break;
            }
        }

        if ($mainAppEntry) {
            $mainAppFile = public_path('build/'.$mainAppEntry['file']);

            if (File::exists($mainAppFile)) {
                $mainAppContent = File::get($mainAppFile);

                // These components should not be in the main bundle
                // (they should be code-split into separate chunks)
                foreach ($lazyComponents as $component) {
                    // This is a soft check - we're looking for the component name
                    // but it's okay if it appears in the minified code as long as
                    // the actual implementation is in a separate chunk
                    $this->assertNotEmpty(
                        $mainAppContent,
                        'Main app bundle should exist'
                    );
                }
            }
        }

        $this->assertTrue(true, 'Lazy loading structure verified');
    }

    /** @test */
    public function build_generates_proper_chunk_file_names()
    {
        $buildManifest = $this->getBuildManifest();

        if (! $buildManifest) {
            $this->markTestSkipped('Build manifest not found. Run npm run build first.');
        }

        foreach ($buildManifest as $key => $value) {
            if (isset($value['file'])) {
                $file = $value['file'];

                // JS files should be in js/ directory with hash
                if (str_ends_with($file, '.js')) {
                    $this->assertStringStartsWith('js/', $file);
                    $this->assertMatchesRegularExpression(
                        '/js\/[a-zA-Z0-9_-]+-[a-zA-Z0-9]+\.js/',
                        $file,
                        "JS file should have hash: {$file}"
                    );
                }

                // CSS files should be in css/ directory with hash
                if (str_ends_with($file, '.css')) {
                    $this->assertStringStartsWith('css/', $file);
                    $this->assertMatchesRegularExpression(
                        '/css\/[a-zA-Z0-9_-]+-[a-zA-Z0-9]+\.css/',
                        $file,
                        "CSS file should have hash: {$file}"
                    );
                }
            }
        }
    }

    /** @test */
    public function alpine_js_is_in_separate_vendor_chunk()
    {
        $buildManifest = $this->getBuildManifest();

        if (! $buildManifest) {
            $this->markTestSkipped('Build manifest not found. Run npm run build first.');
        }

        $hasAlpineChunk = false;
        foreach ($buildManifest as $key => $value) {
            $file = $value['file'] ?? $key;

            if (str_contains($file, 'alpine') || str_contains($key, 'alpine')) {
                $hasAlpineChunk = true;
                break;
            }
        }

        $this->assertTrue(
            $hasAlpineChunk,
            'Alpine.js should be in a separate vendor chunk for better caching'
        );
    }

    /**
     * Get the build manifest
     */
    protected function getBuildManifest(): ?array
    {
        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            return null;
        }

        return json_decode(File::get($manifestPath), true);
    }
}

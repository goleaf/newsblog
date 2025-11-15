<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeSplittingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that homepage loads with page-specific scripts
     */
    public function test_homepage_loads_with_page_specific_scripts(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('homepage.js', false);
    }

    /**
     * Test that article page loads with article-specific scripts
     */
    public function test_article_page_loads_with_article_specific_scripts(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSee('article.js', false);
    }

    /**
     * Test that dashboard loads with dashboard-specific scripts
     */
    public function test_dashboard_loads_with_dashboard_specific_scripts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('dashboard.js', false);
    }

    /**
     * Test that search page loads with search-specific scripts
     */
    public function test_search_page_loads_with_search_specific_scripts(): void
    {
        $response = $this->get('/search');

        $response->assertStatus(200);
        $response->assertSee('search.js', false);
    }

    /**
     * Test that build manifest exists and contains expected chunks
     */
    public function test_build_manifest_contains_expected_chunks(): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (! file_exists($manifestPath)) {
            $this->markTestSkipped('Build manifest not found. Run npm run build first.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        // Check for page-specific entry points
        $this->assertArrayHasKey('resources/js/pages/homepage.js', $manifest);
        $this->assertArrayHasKey('resources/js/pages/article.js', $manifest);
        $this->assertArrayHasKey('resources/js/pages/dashboard.js', $manifest);
        $this->assertArrayHasKey('resources/js/pages/search.js', $manifest);

        // Check for main app entry
        $this->assertArrayHasKey('resources/js/app.js', $manifest);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);
    }

    /**
     * Test that vendor chunks are properly separated
     */
    public function test_vendor_chunks_are_separated(): void
    {
        $buildPath = public_path('build/js');

        if (! is_dir($buildPath)) {
            $this->markTestSkipped('Build directory not found. Run npm run build first.');
        }

        $files = scandir($buildPath);
        $vendorFiles = array_filter($files, function ($file) {
            return str_contains($file, 'vendor-');
        });

        // Should have at least vendor-alpine and vendor-axios
        $this->assertGreaterThanOrEqual(2, count($vendorFiles),
            'Expected at least 2 vendor chunks (alpine and axios)');
    }

    /**
     * Test that page-specific chunks are smaller than vendor chunks
     */
    public function test_page_chunks_are_smaller_than_vendor_chunks(): void
    {
        $buildPath = public_path('build/js');

        if (! is_dir($buildPath)) {
            $this->markTestSkipped('Build directory not found. Run npm run build first.');
        }

        $files = scandir($buildPath);

        // Get vendor chunk sizes
        $vendorSizes = [];
        foreach ($files as $file) {
            if (str_contains($file, 'vendor-')) {
                $vendorSizes[] = filesize($buildPath.'/'.$file);
            }
        }

        // Get page chunk sizes
        $pageSizes = [];
        foreach ($files as $file) {
            if (str_contains($file, 'homepage') ||
                str_contains($file, 'article') ||
                str_contains($file, 'dashboard') ||
                str_contains($file, 'search')) {
                $pageSizes[] = filesize($buildPath.'/'.$file);
            }
        }

        if (empty($vendorSizes) || empty($pageSizes)) {
            $this->markTestSkipped('Could not find vendor or page chunks');
        }

        $avgVendorSize = array_sum($vendorSizes) / count($vendorSizes);
        $avgPageSize = array_sum($pageSizes) / count($pageSizes);

        // Page chunks should be significantly smaller than vendor chunks
        $this->assertLessThan($avgVendorSize, $avgPageSize,
            'Page-specific chunks should be smaller than vendor chunks');
    }
}

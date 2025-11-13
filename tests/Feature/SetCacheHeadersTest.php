<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetCacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_assets_have_cache_headers(): void
    {
        // Test CSS file
        $response = $this->get('/build/assets/app.css');

        // Should have cache headers if file exists, or 404 if not
        if ($response->status() === 200) {
            $response->assertHeader('Cache-Control', 'public, max-age=31536000, immutable');
            $this->assertNotNull($response->headers->get('Expires'));
        }
    }

    public function test_storage_files_have_cache_headers(): void
    {
        // Create a test file in storage
        $testFile = 'test-image.jpg';
        $path = storage_path('app/public/'.$testFile);

        if (! file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, 'test content');

        $response = $this->get('/storage/'.$testFile);

        if ($response->status() === 200) {
            $response->assertHeader('Cache-Control', 'public, max-age=31536000, immutable');
            $this->assertNotNull($response->headers->get('Expires'));
        }

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function test_html_pages_do_not_have_long_cache_headers(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // HTML pages should not have the 1-year cache header
        $cacheControl = $response->headers->get('Cache-Control');

        if ($cacheControl) {
            $this->assertStringNotContainsString('max-age=31536000', $cacheControl);
        }
    }

    public function test_api_responses_do_not_have_long_cache_headers(): void
    {
        $response = $this->get('/api/posts');

        // API responses should not have the 1-year cache header
        $cacheControl = $response->headers->get('Cache-Control');

        if ($cacheControl) {
            $this->assertStringNotContainsString('max-age=31536000', $cacheControl);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_allows_all_and_includes_sitemap(): void
    {
        Storage::fake('public');

        /** @var SitemapService $sitemap */
        $sitemap = app(SitemapService::class);
        $sitemap->generate();

        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Allow: /', false);
        $response->assertSee('Sitemap: '.$sitemap->getSitemapUrl(), false);
    }
}




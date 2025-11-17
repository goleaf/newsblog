<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __construct(
        private SitemapService $sitemapService
    ) {}

    public function index(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Disallow admin areas',
            'Disallow: /nova/',
            'Disallow: /admin/',
            'Disallow: /dashboard/',
            'Disallow: /api/',
            '',
            '# Disallow user-specific pages',
            'Disallow: /profile/edit',
            'Disallow: /bookmarks/',
            'Disallow: /reading-lists/create',
            'Disallow: /reading-lists/*/edit',
            '',
            '# Disallow search and filter pages with parameters',
            'Disallow: /search?*',
            'Disallow: /*?sort=*',
            'Disallow: /*?filter=*',
            '',
            '# Sitemap location',
            'Sitemap: '.$this->sitemapService->getSitemapUrl(),
        ];

        return response(implode(PHP_EOL, $lines).PHP_EOL, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}

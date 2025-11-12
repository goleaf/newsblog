<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class SitemapController extends Controller
{
    public function __construct(
        private SitemapService $sitemapService
    ) {}

    /**
     * Serve the main sitemap
     */
    public function index(): Response
    {
        // Generate sitemap if it doesn't exist
        if (! $this->sitemapService->exists()) {
            $this->sitemapService->generate();
        }

        // Check if sitemap index exists (multiple files)
        if (Storage::disk('public')->exists('sitemap-index.xml')) {
            $content = Storage::disk('public')->get('sitemap-index.xml');
        } else {
            $content = Storage::disk('public')->get('sitemap.xml');
        }

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}

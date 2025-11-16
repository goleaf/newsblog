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
            'Sitemap: '.$this->sitemapService->getSitemapUrl(),
        ];

        return response(implode(PHP_EOL, $lines).PHP_EOL, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}



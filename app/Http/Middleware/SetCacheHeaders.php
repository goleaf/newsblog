<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     *
     * Set cache headers for static assets (1 year) to improve performance.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only apply cache headers to static assets
        if ($this->isStaticAsset($request)) {
            // Cache for 1 year (31536000 seconds)
            $response->header('Cache-Control', 'public, max-age=31536000, immutable');
            $response->header('Expires', gmdate('D, d M Y H:i:s', time() + 31536000).' GMT');
        }

        return $response;
    }

    /**
     * Determine if the request is for a static asset.
     */
    private function isStaticAsset(Request $request): bool
    {
        $path = $request->path();

        // Check if the path matches static asset patterns
        $staticPatterns = [
            'build/',      // Vite build assets
            'storage/',    // Storage files (images, etc.)
            'vendor/',     // Vendor assets
        ];

        foreach ($staticPatterns as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }

        // Check file extensions
        $extension = $request->getPathInfo();
        $staticExtensions = ['.css', '.js', '.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.woff', '.woff2', '.ttf', '.eot', '.ico'];

        foreach ($staticExtensions as $ext) {
            if (str_ends_with($extension, $ext)) {
                return true;
            }
        }

        return false;
    }
}

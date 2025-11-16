<?php

namespace App\Http\Middleware;

use App\Services\CacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PageCache
{
    public function __construct(
        protected CacheService $cacheService
    ) {}

    /**
     * Cache full-page HTML responses for selected routes and TTLs.
     *
     * - Home: 10 minutes
     * - Category pages: 15 minutes
     * - Post pages: 1 hour
     *
     * Skips non-GET requests and non-HTML responses.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Exclude authenticated users from page cache
        if ($request->user()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName === null) {
            return $next($request);
        }

        $ttl = $this->resolveTtlForRoute($routeName);
        if ($ttl === null) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($routeName, $request);

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return response($cached['content'], $cached['status'], $cached['headers']);
        }

        /** @var Response $response */
        $response = $next($request);

        // Only cache successful HTML responses
        $contentType = $response->headers->get('Content-Type', '');
        if ($response->isSuccessful() && str_contains($contentType, 'text/html')) {
            Cache::put($cacheKey, [
                'status' => $response->getStatusCode(),
                'headers' => [
                    'Content-Type' => $contentType,
                ],
                'content' => $response->getContent(),
            ], $ttl);
        }

        return $response;
    }

    private function resolveTtlForRoute(string $routeName): ?int
    {
        if ($routeName === 'home') {
            return CacheService::TTL_SHORT; // 10 minutes
        }

        if (str_starts_with($routeName, 'category.show')) {
            return 900; // 15 minutes
        }

        if (str_starts_with($routeName, 'post.show')) {
            return CacheService::TTL_LONG; // 1 hour
        }

        return null;
    }

    private function buildCacheKey(string $routeName, Request $request): string
    {
        // Use path and relevant query parameters for uniqueness
        $query = $request->query();
        ksort($query);
        $queryHash = md5(json_encode($query));

        return "pagecache.{$routeName}.{$request->path()}.{$queryHash}";
    }
}



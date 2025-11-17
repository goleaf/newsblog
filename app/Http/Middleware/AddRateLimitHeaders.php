<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddRateLimitHeaders
{
    public function __construct(protected RateLimiter $limiter) {}

    /**
     * Handle an incoming request.
     *
     * Add rate limit headers to API responses for transparency.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only add headers to API routes
        if (! $request->is('api/*')) {
            return $response;
        }

        // Determine the rate limiter key
        $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();
        $limiterName = 'api';

        // Get rate limit info
        $maxAttempts = $request->user() ? 120 : 60;
        $decayMinutes = 1;

        // Calculate remaining attempts
        $attempts = $this->limiter->attempts($limiterName.':'.$key);
        $remaining = max(0, $maxAttempts - $attempts);

        // Add headers
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        // Add reset time if we have attempts
        if ($attempts > 0) {
            $resetTime = $this->limiter->availableIn($limiterName.':'.$key) + time();
            $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        }

        return $response;
    }
}

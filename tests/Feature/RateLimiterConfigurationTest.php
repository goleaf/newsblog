<?php

namespace Tests\Feature;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimiterConfigurationTest extends TestCase
{
    public function test_login_rate_limiter_configuration(): void
    {
        $limiter = RateLimiter::limiter('login');

        self::assertNotNull($limiter);

        $limit = $limiter(Request::create('/login', 'POST', [
            'email' => 'user@example.com',
        ]));

        self::assertInstanceOf(Limit::class, $limit);
        self::assertSame(5, $limit->maxAttempts);

        Log::spy();

        $response = ($limit->responseCallback)(
            Request::create('/login', 'POST', [
                'email' => 'user@example.com',
            ]),
            ['Retry-After' => 60]
        );

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('Too many login attempts. Please try again later.', $response->getData(true)['message']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Rate limit exceeded for login'
                    && $context['email'] === 'user@example.com';
            });
    }

    public function test_comment_rate_limiter_configuration(): void
    {
        $limiter = RateLimiter::limiter('comments');

        self::assertNotNull($limiter);

        $limit = $limiter(Request::create('/comments', 'POST'));

        self::assertInstanceOf(Limit::class, $limit);
        self::assertSame(3, $limit->maxAttempts);

        Log::spy();

        $response = ($limit->responseCallback)(Request::create('/comments', 'POST'), ['Retry-After' => 60]);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('Too many comment submissions. Please slow down.', $response->getData(true)['message']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $message): bool {
                return $message === 'Rate limit exceeded for comments';
            });
    }

    public function test_search_rate_limiter_configuration(): void
    {
        $limiter = RateLimiter::limiter('search');

        self::assertNotNull($limiter);

        $limit = $limiter(Request::create('/search', 'GET'));

        self::assertInstanceOf(Limit::class, $limit);
        self::assertSame(60, $limit->maxAttempts);
    }
}

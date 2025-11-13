<?php

namespace Tests\Feature\Feature\Middleware;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_security_headers_to_response(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_adds_content_security_policy_header(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_adds_permissions_policy_header(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy');
        $this->assertStringContainsString('geolocation=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_middleware_processes_request(): void
    {
        $middleware = new SecurityHeaders;
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Test content');
        });

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }
}

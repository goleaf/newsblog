<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test X-Frame-Options header is set correctly
     */
    public function test_x_frame_options_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * Test X-Content-Type-Options header is set correctly
     */
    public function test_x_content_type_options_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Test X-XSS-Protection header is set correctly
     */
    public function test_x_xss_protection_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Test Referrer-Policy header is set correctly
     */
    public function test_referrer_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * Test Content-Security-Policy header is set correctly
     */
    public function test_content_security_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    /**
     * Test Permissions-Policy header is set correctly
     */
    public function test_permissions_policy_header_is_set(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Permissions-Policy');

        $policy = $response->headers->get('Permissions-Policy');

        $this->assertStringContainsString('geolocation=()', $policy);
        $this->assertStringContainsString('microphone=()', $policy);
        $this->assertStringContainsString('camera=()', $policy);
    }

    /**
     * Test security headers are applied to all routes
     */
    public function test_security_headers_applied_to_all_routes(): void
    {
        $routes = [
            '/',
            '/search',
            '/login',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            $response->assertHeader('X-Frame-Options');
            $response->assertHeader('X-Content-Type-Options');
            $response->assertHeader('X-XSS-Protection');
            $response->assertHeader('Referrer-Policy');
            $response->assertHeader('Content-Security-Policy');
        }
    }

    /**
     * Test security headers are applied to API routes
     */
    public function test_security_headers_applied_to_api_routes(): void
    {
        $response = $this->getJson('/api/v1/posts');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    /**
     * Test Strict-Transport-Security header is not set in non-production
     */
    public function test_hsts_not_set_in_non_production(): void
    {
        config(['app.env' => 'local']);

        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    /**
     * Test all security headers work together
     */
    public function test_all_security_headers_present(): void
    {
        $response = $this->get('/');

        $requiredHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options',
            'X-XSS-Protection',
            'Referrer-Policy',
            'Content-Security-Policy',
            'Permissions-Policy',
        ];

        foreach ($requiredHeaders as $header) {
            $this->assertTrue(
                $response->headers->has($header),
                "Missing required security header: {$header}"
            );
        }
    }
}

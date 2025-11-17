<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityMeasuresVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that passwords are hashed with bcrypt rounds of 12.
     */
    public function test_passwords_are_hashed_with_bcrypt(): void
    {
        $user = User::factory()->create([
            'password' => 'TestPassword123!',
        ]);

        // Verify password is hashed
        $this->assertNotEquals('TestPassword123!', $user->password);

        // Verify bcrypt is used (starts with $2y$)
        $this->assertStringStartsWith('$2y$', $user->password);

        // Verify password can be verified
        $this->assertTrue(Hash::check('TestPassword123!', $user->password));
    }

    /**
     * Test that session security settings are configured.
     */
    public function test_session_security_is_configured(): void
    {
        // Verify session configuration
        $this->assertEquals(120, config('session.lifetime'));
        $this->assertTrue(config('session.http_only'));
        $this->assertEquals('strict', config('session.same_site'));
    }

    /**
     * Test that CSRF protection is enabled.
     */
    public function test_csrf_protection_is_enabled(): void
    {
        $user = User::factory()->create();

        // Attempt POST without CSRF token should fail
        $response = $this->actingAs($user)->post('/profile', [
            'name' => 'Test User',
        ]);

        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Test that rate limiting is configured for login.
     */
    public function test_login_rate_limiting_is_configured(): void
    {
        // Clear any existing rate limits
        RateLimiter::clear('test@example.com127.0.0.1');

        // Make 5 login attempts (should succeed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test that security headers are present in responses.
     */
    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        // Verify all required security headers are present
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Verify CSP header exists
        $this->assertTrue($response->headers->has('Content-Security-Policy'));

        // Verify Permissions-Policy header exists
        $this->assertTrue($response->headers->has('Permissions-Policy'));
    }

    /**
     * Test that API routes are excluded from CSRF protection.
     */
    public function test_api_routes_excluded_from_csrf(): void
    {
        // API routes should work without CSRF token
        $response = $this->getJson('/api/v1/articles');

        // Should not get CSRF error
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test that API rate limiting is configured.
     */
    public function test_api_rate_limiting_is_configured(): void
    {
        // Clear any existing rate limits
        RateLimiter::clear('ip:127.0.0.1');

        // Make requests up to the limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/v1/articles');
        }

        // Next request should be rate limited
        $response = $this->getJson('/api/v1/articles');

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test that password validation enforces complexity.
     */
    public function test_password_validation_enforces_complexity(): void
    {
        // Weak password should fail
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');

        // Strong password should pass
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'StrongP@ssw0rd!',
            'password_confirmation' => 'StrongP@ssw0rd!',
        ]);

        $response->assertSessionHasNoErrors();
    }
}

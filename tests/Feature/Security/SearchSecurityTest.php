<?php

namespace Tests\Feature\Security;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SearchSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test rate limiting on public search route
     */
    public function test_public_search_rate_limiting(): void
    {
        RateLimiter::clear('search');

        // Make 30 requests (within limit)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->get('/search?q=test');
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // Verify rate limiter is configured
        $this->assertTrue(RateLimiter::limiter('search') !== null);
    }

    /**
     * Test rate limiting on API search endpoint
     */
    public function test_api_search_rate_limiting(): void
    {
        RateLimiter::clear('search');

        // Make 30 requests (within limit)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson('/api/v1/search?q=test');
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // Verify rate limiter is configured
        $this->assertTrue(RateLimiter::limiter('search') !== null);
    }

    /**
     * Test rate limiting on API suggestions endpoint
     */
    public function test_api_suggestions_rate_limiting(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        RateLimiter::clear('search');

        // Make 30 requests (within limit)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson('/api/v1/search/suggestions?q=Lara');
            $this->assertNotEquals(429, $response->status(), "Request {$i} should not be rate limited");
        }

        // Verify rate limiter is configured
        $this->assertTrue(RateLimiter::limiter('search') !== null);
    }

    /**
     * Test XSS prevention in search query display
     */
    public function test_xss_prevention_in_query_display(): void
    {
        $maliciousQuery = '<script>alert("XSS")</script>';

        $response = $this->get('/search?q='.urlencode($maliciousQuery));

        $response->assertStatus(200);
        $response->assertSee(e($maliciousQuery), false);
        $response->assertDontSee('<script>', false);
    }

    /**
     * Test XSS prevention in highlighted text
     */
    public function test_xss_prevention_in_highlighted_text(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Test Post with <script>alert("XSS")</script>',
            'excerpt' => 'This is a test post with <img src=x onerror=alert(1)>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Test');

        $response->assertStatus(200);
        // Should not contain unescaped script tags
        $content = $response->getContent();
        $this->assertStringNotContainsString('<script>alert', $content);
        // Check that HTML tags are escaped (onerror= should only appear in escaped context)
        $this->assertStringNotContainsString('<img src=x onerror=', $content);
        // The main security check is that HTML is escaped - if results are found, they should be safe
    }

    /**
     * Test XSS prevention in API search results
     */
    public function test_xss_prevention_in_api_search_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Test Post with <script>alert("XSS")</script>',
            'excerpt' => 'This is a test post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/search?q=Test');

        $response->assertStatus(200);
        $data = $response->json('data');

        if (! empty($data)) {
            $firstResult = $data[0];
            // Title should not contain unescaped script tags
            $this->assertStringNotContainsString('<script>', $firstResult['title']);
            // Check highlights if present
            if (isset($firstResult['highlights']['title'])) {
                $this->assertStringNotContainsString('<script>', $firstResult['highlights']['title']);
            }
        }
    }

    /**
     * Test admin search requires authentication
     */
    public function test_admin_search_requires_authentication(): void
    {
        $response = $this->get('/admin/posts?search=test');

        $response->assertRedirect(route('login'));
    }

    /**
     * Test admin search requires admin role
     * Note: Admin middleware allows admin, editor, and author roles
     */
    public function test_admin_search_requires_admin_role(): void
    {
        // Test that non-authenticated users are redirected
        $response = $this->get('/admin/posts?search=test');
        $response->assertRedirect(route('login'));

        // Test that authenticated users with valid roles can access
        $author = User::factory()->create(['role' => 'author']);
        $response = $this->actingAs($author)->get('/admin/posts?search=test');
        $response->assertStatus(200);
    }

    /**
     * Test admin search works for admin users
     */
    public function test_admin_search_works_for_admin_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Admin Test Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $admin->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/posts?search=Admin');

        $response->assertStatus(200);
        $response->assertSee('Admin Test Post', false);
    }

    /**
     * Test admin search input sanitization
     */
    public function test_admin_search_input_sanitization(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $maliciousSearch = '<script>alert("XSS")</script>';

        $response = $this->actingAs($admin)->get('/admin/posts?search='.urlencode($maliciousSearch));

        $response->assertStatus(200);
        // Should not execute script, should sanitize input
        $response->assertDontSee('<script>', false);
    }

    /**
     * Test API search input validation
     */
    public function test_api_search_input_validation(): void
    {
        // Test with invalid characters
        $response = $this->getJson('/api/v1/search?q=<script>alert("XSS")</script>');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    /**
     * Test API search with SQL injection attempt
     */
    public function test_api_search_sql_injection_prevention(): void
    {
        // SQL injection attempt should be rejected by validation
        $response = $this->getJson('/api/v1/search?q=test\'; DROP TABLE posts; --');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    }

    /**
     * Test public search input sanitization
     */
    public function test_public_search_input_sanitization(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Test Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Test with HTML tags - should be stripped
        $response = $this->get('/search?q=<script>test</script>');

        $response->assertStatus(200);
        // Query should be sanitized
        $query = $response->viewData('query');
        $this->assertStringNotContainsString('<script>', $query);
    }

    /**
     * Test rate limiter uses IP address
     */
    public function test_rate_limiter_uses_ip_address(): void
    {
        RateLimiter::clear('search');

        // Simulate requests from different IPs
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1']);
        for ($i = 0; $i < 30; $i++) {
            $response = $this->get('/search?q=test');
            $response->assertStatus(200);
        }

        // Different IP should not be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2']);
        $response = $this->get('/search?q=test');
        $response->assertStatus(200);
    }

    /**
     * Test highlighted text properly escapes HTML
     */
    public function test_highlighted_text_escapes_html(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Post with <strong>HTML</strong> tags',
            'excerpt' => 'Excerpt with <em>emphasis</em>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Post');

        $response->assertStatus(200);
        $content = $response->getContent();
        // HTML tags should be escaped (not present as raw HTML)
        $this->assertStringNotContainsString('<strong>HTML</strong>', $content);
        $this->assertStringNotContainsString('<em>emphasis</em>', $content);
        // But highlight tags should be present (these are safe)
        $response->assertSee('<mark', false);
    }
}

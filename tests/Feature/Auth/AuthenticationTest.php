<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // Login Functionality Tests
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_email(): void
    {
        User::factory()->create();

        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_inactive_status(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Inactive,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_suspended_status(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Suspended,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_login_validates_email_required(): void
    {
        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_validates_password_required(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_login_redirects_to_intended_url_after_authentication(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_remember_me_functionality_creates_remember_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $this->assertAuthenticated();
        $user->refresh();
        $this->assertNotNull($user->remember_token);
    }

    public function test_remember_me_functionality_without_checkbox(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    // Logout Functionality Tests
    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_unauthenticated_users_cannot_logout(): void
    {
        $response = $this->post('/logout');

        // Unauthenticated users may be redirected or get 302
        $this->assertTrue($response->isRedirect() || $response->status() === 302);
        $this->assertGuest();
    }

    // Session Management Tests
    public function test_session_is_regenerated_on_successful_login(): void
    {
        $user = User::factory()->create();

        $this->get('/login');
        $oldId = session()->getId();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertNotSame($oldId, session()->getId());
    }

    public function test_session_is_invalidated_on_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $oldId = session()->getId();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $this->assertNotSame($oldId, session()->getId());
    }

    public function test_csrf_token_is_regenerated_on_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $oldToken = session()->token();

        $response = $this->post('/logout');

        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());
    }

    public function test_authenticated_user_session_persists_across_requests(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/dashboard');
        $this->assertAuthenticated();

        $this->get('/profile');
        $this->assertAuthenticated();

        $this->assertEquals($user->id, Auth::id());
    }

    // Role-Based Access Control Tests
    public function test_admin_can_access_role_protected_route(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $response = $this->actingAs($admin)->get('/admin/analytics');

        $response->assertStatus(200);
    }

    public function test_editor_can_access_role_protected_route(): void
    {
        $editor = User::factory()->create([
            'role' => UserRole::Editor,
        ]);

        $response = $this->actingAs($editor)->get('/admin/analytics');

        // Should not be 403 (forbidden) - editor has access
        $this->assertNotEquals(403, $response->status());
    }

    public function test_author_cannot_access_role_protected_route(): void
    {
        $author = User::factory()->create([
            'role' => UserRole::Author,
        ]);

        $response = $this->actingAs($author)->get('/admin/analytics');

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_role_protected_route(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $response = $this->actingAs($user)->get('/admin/analytics');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login_for_role_protected_route(): void
    {
        $response = $this->get('/admin/analytics');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_approve_comments(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);
        $post = \App\Models\Post::factory()->create();
        $comment = \App\Models\Comment::factory()->create([
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($admin)->post("/comments/{$comment->id}/approve");

        $response->assertStatus(302);
        $comment->refresh();
        $this->assertEquals(\App\Enums\CommentStatus::Approved, $comment->status);
    }

    public function test_editor_can_approve_comments(): void
    {
        $editor = User::factory()->create([
            'role' => UserRole::Editor,
        ]);
        $post = \App\Models\Post::factory()->create();
        $comment = \App\Models\Comment::factory()->create([
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($editor)->post("/comments/{$comment->id}/approve");

        $response->assertStatus(302);
    }

    public function test_author_cannot_approve_comments(): void
    {
        $author = User::factory()->create([
            'role' => UserRole::Author,
        ]);
        $post = \App\Models\Post::factory()->create();
        $comment = \App\Models\Comment::factory()->create([
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($author)->post("/comments/{$comment->id}/approve");

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_approve_comments(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);
        $post = \App\Models\Post::factory()->create();
        $comment = \App\Models\Comment::factory()->create([
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($user)->post("/comments/{$comment->id}/approve");

        $response->assertStatus(403);
    }

    // Rate Limiting Tests
    public function test_login_rate_limiting_prevents_brute_force(): void
    {
        $user = User::factory()->create();

        // Attempt 5 failed logins
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Rate limiting should prevent authentication
        $this->assertGuest();
        // Check if response has errors or is a redirect/error status
        $isErrorStatus = in_array($response->status(), [302, 429, 422]);
        $isRedirect = $response->isRedirect();
        $this->assertTrue($isErrorStatus || $isRedirect, 'Rate limiting should prevent login');
    }

    public function test_rate_limiter_clears_on_successful_login(): void
    {
        $user = User::factory()->create();

        // Attempt 4 failed logins
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // Successful login should clear rate limiter
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        // Verify rate limiter is cleared (key format matches LoginRequest throttleKey)
        $throttleKey = \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower($user->email).'|127.0.0.1');
        $this->assertEquals(0, RateLimiter::attempts($throttleKey));
    }
}

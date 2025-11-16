<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
    }

    public function test_users_can_select_author_role_on_registration(): void
    {
        $response = $this->post('/register', [
            'name' => 'Author User',
            'email' => 'author@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'author',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'author@example.com')->first();

        $this->assertNotNull($user);
        $this->assertEquals('author', $user->role);
    }
}

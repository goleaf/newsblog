<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class NovaAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_nova(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertTrue(Gate::forUser($admin)->check('viewNova'));
    }

    public function test_editor_can_access_nova(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $this->assertTrue(Gate::forUser($editor)->check('viewNova'));
    }

    public function test_author_can_access_nova(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $this->assertTrue(Gate::forUser($author)->check('viewNova'));
    }

    public function test_regular_user_cannot_access_nova(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->assertFalse(Gate::forUser($user)->check('viewNova'));
    }

    public function test_nova_auth_callback_allows_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $request = \Illuminate\Http\Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $admin);

        $this->assertTrue(Gate::forUser($admin)->check('viewNova'));
    }

    public function test_nova_auth_callback_allows_editor(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);

        $request = \Illuminate\Http\Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $editor);

        $this->assertTrue(Gate::forUser($editor)->check('viewNova'));
    }

    public function test_nova_auth_callback_allows_author(): void
    {
        $author = User::factory()->create(['role' => 'author']);

        $request = \Illuminate\Http\Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $author);

        $this->assertTrue(Gate::forUser($author)->check('viewNova'));
    }

    public function test_nova_auth_callback_denies_regular_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $request = \Illuminate\Http\Request::create('/admin', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->assertFalse(Gate::forUser($user)->check('viewNova'));
    }
}

<?php

namespace Tests\Feature\Nova;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
    }

    public function test_admin_can_view_users_index(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_editor_cannot_view_users_index(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->editor)
            ->getJson('/nova-api/users');

        $response->assertForbidden();
    }

    public function test_author_cannot_view_users_index(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->author)
            ->getJson('/nova-api/users');

        $response->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'role' => 'author',
                'status' => 'active',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'author',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'role' => 'author',
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson("/nova-api/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'role' => 'editor',
                'status' => $user->status,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'role' => 'editor',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/nova-api/users?resources[]={$user->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_editor_cannot_create_user(): void
    {
        $response = $this->actingAs($this->editor)
            ->postJson('/nova-api/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'role' => 'author',
            ]);

        $response->assertForbidden();
    }

    public function test_editor_cannot_update_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->editor)
            ->putJson("/nova-api/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'role' => $user->role,
            ]);

        $response->assertForbidden();
    }

    public function test_editor_cannot_delete_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->editor)
            ->deleteJson("/nova-api/users?resources[]={$user->id}");

        $response->assertForbidden();
    }

    public function test_user_creation_requires_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/users', [
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'author',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_creation_requires_email(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/users', [
                'name' => 'Test User',
                'password' => 'password123',
                'role' => 'author',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_creation_requires_unique_email(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/users', [
                'name' => 'Test User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'role' => 'author',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_creation_requires_password(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => 'author',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_is_hashed_on_creation(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/nova-api/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'plainpassword',
                'role' => 'author',
            ]);

        $response->assertCreated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue(Hash::check('plainpassword', $user->password));
    }

    public function test_admin_can_change_user_role(): void
    {
        $user = User::factory()->create(['role' => 'author']);

        $response = $this->actingAs($this->admin)
            ->putJson("/nova-api/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'editor',
                'status' => $user->status,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'editor',
        ]);
    }

    public function test_admin_can_change_user_status(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->putJson("/nova-api/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => 'suspended',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'suspended',
        ]);
    }
}

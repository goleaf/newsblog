<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;
    }

    public function test_admin_can_view_any_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_editor_can_view_any_users(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->assertTrue($this->policy->viewAny($editor));
    }

    public function test_author_cannot_view_any_users(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);

        $this->assertFalse($this->policy->viewAny($author));
    }

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    public function test_admin_can_view_any_user_profile(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->view($admin, $otherUser));
    }

    public function test_user_cannot_view_others_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::Reader]);
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->view($user, $otherUser));
    }

    public function test_only_admin_can_create_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($editor));
    }

    public function test_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->update($user, $user));
    }

    public function test_admin_can_update_any_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->update($admin, $otherUser));
    }

    public function test_user_cannot_update_others_profile(): void
    {
        $user = User::factory()->create(['role' => UserRole::Reader]);
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->update($user, $otherUser));
    }

    public function test_user_can_delete_own_account(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->delete($user, $user));
    }

    public function test_admin_can_delete_other_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $otherUser));
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    public function test_user_cannot_delete_others(): void
    {
        $user = User::factory()->create(['role' => UserRole::Reader]);
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->delete($user, $otherUser));
    }

    public function test_only_admin_can_restore_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $deletedUser = User::factory()->create();

        $this->assertTrue($this->policy->restore($admin, $deletedUser));
        $this->assertFalse($this->policy->restore($editor, $deletedUser));
    }

    public function test_admin_can_force_delete_other_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->forceDelete($admin, $otherUser));
    }

    public function test_admin_cannot_force_delete_themselves(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertFalse($this->policy->forceDelete($admin, $admin));
    }
}

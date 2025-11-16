<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_uses_enum_cast_for_role(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);

        $this->assertInstanceOf(UserRole::class, $user->role);
        $this->assertEquals(UserRole::Admin, $user->role);
    }

    public function test_user_uses_enum_cast_for_status(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::Active->value,
        ]);

        $this->assertInstanceOf(UserStatus::class, $user->status);
        $this->assertEquals(UserStatus::Active, $user->status);
    }

    public function test_user_default_role_is_user(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(UserRole::User, $user->role);
    }

    public function test_user_default_status_is_active(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(UserStatus::Active, $user->status);
    }

    public function test_is_admin_returns_true_for_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isEditor());
        $this->assertFalse($user->isAuthor());
    }

    public function test_is_editor_returns_true_for_editor_role(): void
    {
        $user = User::factory()->editor()->create();

        $this->assertTrue($user->isEditor());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isAuthor());
    }

    public function test_is_author_returns_true_for_author_role(): void
    {
        $user = User::factory()->author()->create();

        $this->assertTrue($user->isAuthor());
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isEditor());
    }

    public function test_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->user()->create();

        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isEditor());
        $this->assertFalse($user->isAuthor());
    }

    public function test_user_factory_states_for_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $author = User::factory()->author()->create();
        $regularUser = User::factory()->user()->create();

        $this->assertEquals(UserRole::Admin, $admin->role);
        $this->assertEquals(UserRole::Editor, $editor->role);
        $this->assertEquals(UserRole::Author, $author->role);
        $this->assertEquals(UserRole::User, $regularUser->role);
    }

    public function test_user_factory_states_for_statuses(): void
    {
        $active = User::factory()->active()->create();
        $suspended = User::factory()->suspended()->create();
        $inactive = User::factory()->inactive()->create();

        $this->assertEquals(UserStatus::Active, $active->status);
        $this->assertEquals(UserStatus::Suspended, $suspended->status);
        $this->assertEquals(UserStatus::Inactive, $inactive->status);
    }

    public function test_scope_active_filters_active_users(): void
    {
        User::factory()->active()->count(3)->create();
        User::factory()->suspended()->count(2)->create();
        User::factory()->inactive()->count(1)->create();

        $activeUsers = User::active()->get();

        $this->assertCount(3, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertEquals(UserStatus::Active, $user->status);
        });
    }

    public function test_scope_admins_filters_admin_users(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->editor()->count(3)->create();
        User::factory()->author()->count(4)->create();
        User::factory()->user()->count(5)->create();

        $admins = User::admins()->get();

        $this->assertCount(2, $admins);
        $admins->each(function ($user) {
            $this->assertEquals(UserRole::Admin, $user->role);
            $this->assertTrue($user->isAdmin());
        });
    }

    public function test_scope_editors_filters_editor_users(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->editor()->count(3)->create();
        User::factory()->author()->count(4)->create();

        $editors = User::editors()->get();

        $this->assertCount(3, $editors);
        $editors->each(function ($user) {
            $this->assertEquals(UserRole::Editor, $user->role);
            $this->assertTrue($user->isEditor());
        });
    }

    public function test_scope_authors_filters_author_users(): void
    {
        User::factory()->admin()->count(2)->create();
        User::factory()->editor()->count(3)->create();
        User::factory()->author()->count(4)->create();

        $authors = User::authors()->get();

        $this->assertCount(4, $authors);
        $authors->each(function ($user) {
            $this->assertEquals(UserRole::Author, $user->role);
            $this->assertTrue($user->isAuthor());
        });
    }

    public function test_role_enum_has_all_expected_values(): void
    {
        $expectedRoles = ['admin', 'editor', 'author', 'user'];
        $actualRoles = array_map(fn ($case) => $case->value, UserRole::cases());

        $this->assertEqualsCanonicalizing($expectedRoles, $actualRoles);
    }

    public function test_status_enum_has_all_expected_values(): void
    {
        $expectedStatuses = ['active', 'suspended', 'inactive'];
        $actualStatuses = array_map(fn ($case) => $case->value, UserStatus::cases());

        $this->assertEqualsCanonicalizing($expectedStatuses, $actualStatuses);
    }

    public function test_role_enum_options_method(): void
    {
        $options = UserRole::options();

        $this->assertArrayHasKey('Admin', $options);
        $this->assertArrayHasKey('Editor', $options);
        $this->assertArrayHasKey('Author', $options);
        $this->assertArrayHasKey('User', $options);
        $this->assertEquals('admin', $options['Admin']);
        $this->assertEquals('editor', $options['Editor']);
        $this->assertEquals('author', $options['Author']);
        $this->assertEquals('user', $options['User']);
    }

    public function test_status_enum_options_method(): void
    {
        $options = UserStatus::options();

        $this->assertArrayHasKey('Active', $options);
        $this->assertArrayHasKey('Suspended', $options);
        $this->assertArrayHasKey('Inactive', $options);
        $this->assertEquals('active', $options['Active']);
        $this->assertEquals('suspended', $options['Suspended']);
        $this->assertEquals('inactive', $options['Inactive']);
    }

    public function test_role_enum_label_method(): void
    {
        $this->assertEquals('Admin', UserRole::Admin->label());
        $this->assertEquals('Editor', UserRole::Editor->label());
        $this->assertEquals('Author', UserRole::Author->label());
        $this->assertEquals('User', UserRole::User->label());
    }

    public function test_status_enum_label_method(): void
    {
        $this->assertEquals('Active', UserStatus::Active->label());
        $this->assertEquals('Suspended', UserStatus::Suspended->label());
        $this->assertEquals('Inactive', UserStatus::Inactive->label());
    }

    public function test_user_can_have_role_and_status_changed(): void
    {
        $user = User::factory()->user()->active()->create();

        $this->assertEquals(UserRole::User, $user->role);
        $this->assertEquals(UserStatus::Active, $user->status);

        $user->role = UserRole::Admin;
        $user->status = UserStatus::Suspended;
        $user->save();

        $user->refresh();

        $this->assertEquals(UserRole::Admin, $user->role);
        $this->assertEquals(UserStatus::Suspended, $user->status);
        $this->assertTrue($user->isAdmin());
    }
}

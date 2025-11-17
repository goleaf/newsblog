<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_role_exists_and_can_be_assigned(): void
    {
        $user = User::factory()->reader()->create();

        $this->assertEquals(UserRole::Reader, $user->role);
        $this->assertTrue($user->isReader());
        $this->assertFalse($user->isAuthor());
        $this->assertFalse($user->isModerator());
        $this->assertFalse($user->isAdmin());
    }

    public function test_moderator_role_exists_and_can_be_assigned(): void
    {
        $user = User::factory()->moderator()->create();

        $this->assertEquals(UserRole::Moderator, $user->role);
        $this->assertTrue($user->isModerator());
        $this->assertFalse($user->isReader());
        $this->assertFalse($user->isAuthor());
        $this->assertFalse($user->isAdmin());
    }

    public function test_reader_cannot_create_articles(): void
    {
        $user = User::factory()->reader()->create();

        $this->assertFalse($user->canCreateArticles());
        $this->assertFalse($user->canPublishArticles());
        $this->assertFalse($user->canModerate());
        $this->assertFalse($user->canDeleteAnyContent());
        $this->assertFalse($user->canManageUsers());
    }

    public function test_author_can_create_but_not_publish_articles(): void
    {
        $user = User::factory()->author()->create();

        $this->assertTrue($user->canCreateArticles());
        $this->assertFalse($user->canPublishArticles());
        $this->assertFalse($user->canModerate());
        $this->assertFalse($user->canDeleteAnyContent());
        $this->assertFalse($user->canManageUsers());
    }

    public function test_moderator_can_moderate_but_not_create_articles(): void
    {
        $user = User::factory()->moderator()->create();

        $this->assertFalse($user->canCreateArticles());
        $this->assertFalse($user->canPublishArticles());
        $this->assertTrue($user->canModerate());
        $this->assertFalse($user->canDeleteAnyContent());
        $this->assertFalse($user->canManageUsers());
    }

    public function test_editor_can_create_and_publish_articles(): void
    {
        $user = User::factory()->editor()->create();

        $this->assertTrue($user->canCreateArticles());
        $this->assertTrue($user->canPublishArticles());
        $this->assertFalse($user->canModerate());
        $this->assertFalse($user->canDeleteAnyContent());
        $this->assertFalse($user->canManageUsers());
    }

    public function test_admin_has_all_permissions(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->canCreateArticles());
        $this->assertTrue($user->canPublishArticles());
        $this->assertTrue($user->canModerate());
        $this->assertTrue($user->canDeleteAnyContent());
        $this->assertTrue($user->canManageUsers());
    }

    public function test_user_role_has_no_special_permissions(): void
    {
        $user = User::factory()->user()->create();

        $this->assertFalse($user->canCreateArticles());
        $this->assertFalse($user->canPublishArticles());
        $this->assertFalse($user->canModerate());
        $this->assertFalse($user->canDeleteAnyContent());
        $this->assertFalse($user->canManageUsers());
    }

    public function test_role_enum_methods_work_correctly(): void
    {
        $this->assertTrue(UserRole::Admin->canCreateArticles());
        $this->assertTrue(UserRole::Admin->canPublishArticles());
        $this->assertTrue(UserRole::Admin->canModerate());
        $this->assertTrue(UserRole::Admin->isAdmin());

        $this->assertTrue(UserRole::Editor->canCreateArticles());
        $this->assertTrue(UserRole::Editor->canPublishArticles());
        $this->assertFalse(UserRole::Editor->canModerate());
        $this->assertFalse(UserRole::Editor->isAdmin());

        $this->assertTrue(UserRole::Author->canCreateArticles());
        $this->assertFalse(UserRole::Author->canPublishArticles());
        $this->assertFalse(UserRole::Author->canModerate());

        $this->assertTrue(UserRole::Moderator->canModerate());
        $this->assertFalse(UserRole::Moderator->canCreateArticles());
        $this->assertFalse(UserRole::Moderator->canPublishArticles());

        $this->assertFalse(UserRole::Reader->canCreateArticles());
        $this->assertFalse(UserRole::Reader->canPublishArticles());
        $this->assertFalse(UserRole::Reader->canModerate());

        $this->assertFalse(UserRole::User->canCreateArticles());
        $this->assertFalse(UserRole::User->canPublishArticles());
        $this->assertFalse(UserRole::User->canModerate());
    }

    public function test_scope_moderators_returns_only_moderators(): void
    {
        User::factory()->reader()->create();
        User::factory()->author()->create();
        $moderator = User::factory()->moderator()->create();
        User::factory()->admin()->create();

        $moderators = User::moderators()->get();

        $this->assertCount(1, $moderators);
        $this->assertTrue($moderators->first()->is($moderator));
    }

    public function test_scope_readers_returns_only_readers(): void
    {
        $reader = User::factory()->reader()->create();
        User::factory()->author()->create();
        User::factory()->moderator()->create();
        User::factory()->admin()->create();

        $readers = User::readers()->get();

        $this->assertCount(1, $readers);
        $this->assertTrue($readers->first()->is($reader));
    }

    public function test_role_labels_are_correct(): void
    {
        $this->assertEquals('Reader', UserRole::Reader->label());
        $this->assertEquals('Author', UserRole::Author->label());
        $this->assertEquals('Moderator', UserRole::Moderator->label());
        $this->assertEquals('Admin', UserRole::Admin->label());
        $this->assertEquals('Editor', UserRole::Editor->label());
        $this->assertEquals('User', UserRole::User->label());
    }

    public function test_role_options_include_all_roles(): void
    {
        $options = UserRole::options();

        $this->assertArrayHasKey('Reader', $options);
        $this->assertArrayHasKey('Author', $options);
        $this->assertArrayHasKey('Moderator', $options);
        $this->assertArrayHasKey('Admin', $options);
        $this->assertArrayHasKey('Editor', $options);
        $this->assertArrayHasKey('User', $options);

        $this->assertEquals('reader', $options['Reader']);
        $this->assertEquals('author', $options['Author']);
        $this->assertEquals('moderator', $options['Moderator']);
        $this->assertEquals('admin', $options['Admin']);
        $this->assertEquals('editor', $options['Editor']);
        $this->assertEquals('user', $options['User']);
    }
}

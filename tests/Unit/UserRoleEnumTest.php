<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use Tests\TestCase;

class UserRoleEnumTest extends TestCase
{
    public function test_user_role_enum_has_expected_values(): void
    {
        $this->assertSame('reader', UserRole::Reader->value);
        $this->assertSame('author', UserRole::Author->value);
        $this->assertSame('moderator', UserRole::Moderator->value);
        $this->assertSame('admin', UserRole::Admin->value);
    }

    public function test_can_create_articles_permission(): void
    {
        $this->assertTrue(UserRole::Author->canCreateArticles());
        $this->assertTrue(UserRole::Admin->canCreateArticles());
        $this->assertFalse(UserRole::Reader->canCreateArticles());
        $this->assertFalse(UserRole::Moderator->canCreateArticles());
    }

    public function test_can_publish_articles_permission(): void
    {
        $this->assertTrue(UserRole::Admin->canPublishArticles());
        $this->assertFalse(UserRole::Author->canPublishArticles());
        $this->assertFalse(UserRole::Reader->canPublishArticles());
        $this->assertFalse(UserRole::Moderator->canPublishArticles());
    }

    public function test_can_moderate_permission(): void
    {
        $this->assertTrue(UserRole::Moderator->canModerate());
        $this->assertTrue(UserRole::Admin->canModerate());
        $this->assertFalse(UserRole::Author->canModerate());
        $this->assertFalse(UserRole::Reader->canModerate());
    }

    public function test_is_admin_permission(): void
    {
        $this->assertTrue(UserRole::Admin->isAdmin());
        $this->assertFalse(UserRole::Moderator->isAdmin());
        $this->assertFalse(UserRole::Author->isAdmin());
        $this->assertFalse(UserRole::Reader->isAdmin());
    }

    public function test_can_delete_any_content_permission(): void
    {
        $this->assertTrue(UserRole::Admin->canDeleteAnyContent());
        $this->assertFalse(UserRole::Moderator->canDeleteAnyContent());
        $this->assertFalse(UserRole::Author->canDeleteAnyContent());
        $this->assertFalse(UserRole::Reader->canDeleteAnyContent());
    }

    public function test_can_manage_users_permission(): void
    {
        $this->assertTrue(UserRole::Admin->canManageUsers());
        $this->assertFalse(UserRole::Moderator->canManageUsers());
        $this->assertFalse(UserRole::Author->canManageUsers());
        $this->assertFalse(UserRole::Reader->canManageUsers());
    }
}

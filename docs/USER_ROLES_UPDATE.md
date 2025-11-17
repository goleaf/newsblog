# User Roles System Update

## Overview
The user roles system has been enhanced with two new roles (`Reader` and `Moderator`) and permission-checking methods have been added to both the `UserRole` enum and the `User` model.

## New Roles

### Reader
- **Purpose**: Basic read-only access to the platform
- **Permissions**: Can view content, follow users, bookmark articles, and comment
- **Cannot**: Create articles, publish content, or moderate

### Moderator
- **Purpose**: Content moderation without article creation privileges
- **Permissions**: Can moderate comments, flag content, and manage user reports
- **Cannot**: Create or publish articles, manage users, or delete any content

## Complete Role Hierarchy

1. **Reader** - Basic read-only user
2. **User** - Standard registered user (can comment, bookmark, follow)
3. **Author** - Can create articles (requires editor/admin approval to publish)
4. **Moderator** - Can moderate content but not create articles
5. **Editor** - Can create and publish articles
6. **Admin** - Full system access

## Permission Methods

### UserRole Enum Methods

```php
// Check if role can create articles
$role->canCreateArticles(); // true for Author, Editor, Admin

// Check if role can publish articles
$role->canPublishArticles(); // true for Editor, Admin

// Check if role can moderate content
$role->canModerate(); // true for Moderator, Admin

// Check if role is admin
$role->isAdmin(); // true only for Admin

// Check if role can delete any content
$role->canDeleteAnyContent(); // true only for Admin

// Check if role can manage users
$role->canManageUsers(); // true only for Admin
```

### User Model Methods

```php
// Role checking methods
$user->isReader();
$user->isAuthor();
$user->isModerator();
$user->isEditor();
$user->isAdmin();

// Permission checking methods (delegates to enum)
$user->canCreateArticles();
$user->canPublishArticles();
$user->canModerate();
$user->canDeleteAnyContent();
$user->canManageUsers();

// Query scopes
User::readers()->get();
User::moderators()->get();
User::authors()->get();
User::editors()->get();
User::admins()->get();
```

## Database Changes

### Migration
A new migration `2025_11_17_020949_add_reader_and_moderator_roles_to_users_table.php` has been created to:
- Update MySQL enum column to include 'reader' and 'moderator' values
- No changes needed for SQLite/PostgreSQL (roles stored as strings)

### Factory States
New factory states added to `UserFactory`:
```php
User::factory()->reader()->create();
User::factory()->moderator()->create();
```

## Testing

A comprehensive test suite `UserRolePermissionsTest` has been created to verify:
- Role assignment and checking
- Permission methods work correctly
- Query scopes return correct users
- Role labels and options are properly configured

## Usage Examples

### Creating Users with Specific Roles

```php
// Create a reader
$reader = User::factory()->reader()->create();

// Create a moderator
$moderator = User::factory()->moderator()->create();
```

### Checking Permissions

```php
// Check if user can create articles
if ($user->canCreateArticles()) {
    // Show article creation form
}

// Check if user can moderate
if ($user->canModerate()) {
    // Show moderation dashboard
}
```

### Route Protection

Existing middleware patterns continue to work:
```php
// Moderators and admins only
Route::middleware(['auth', 'role:moderator,admin'])->group(function () {
    // Moderation routes
});

// Editors and admins only
Route::middleware(['auth', 'role:editor,admin'])->group(function () {
    // Publishing routes
});
```

## Files Modified

1. `app/Enums/UserRole.php` - Added new roles and permission methods
2. `app/Models/User.php` - Added role checking and permission methods
3. `database/factories/UserFactory.php` - Added factory states for new roles
4. `database/migrations/2025_11_17_020949_add_reader_and_moderator_roles_to_users_table.php` - Database schema update
5. `tests/Feature/UserRolePermissionsTest.php` - Comprehensive test coverage

## Migration Instructions

1. Run the migration:
   ```bash
   php artisan migrate
   ```

2. Run tests to verify:
   ```bash
   php artisan test --filter=UserRolePermissionsTest
   ```

3. Update any custom authorization logic to use the new permission methods instead of direct role checks

## Breaking Changes

None. All existing role checks continue to work. The new methods provide additional functionality without breaking existing code.

## Future Considerations

- Consider adding a `canEditOwnContent()` method for authors
- May want to add `canViewAnalytics()` for specific roles
- Could add role-based feature flags for gradual rollout of new features

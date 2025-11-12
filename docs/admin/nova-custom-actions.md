# Laravel Nova Custom Actions

## Overview

Custom actions in Nova allow you to perform bulk operations on resources. This guide covers all available custom actions in TechNewsHub and how to use them effectively.

## Table of Contents

- [What are Actions?](#what-are-actions)
- [Post Actions](#post-actions)
- [Comment Actions](#comment-actions)
- [Using Actions](#using-actions)
- [Action Permissions](#action-permissions)
- [Best Practices](#best-practices)

## What are Actions?

Actions are operations you can perform on one or more resources at once. They appear in the "Actions" dropdown when you select items in a resource list.

### Action Types

- **Destructive Actions**: Require confirmation (e.g., Delete)
- **Standalone Actions**: Run immediately (e.g., Export)
- **Queued Actions**: Run in background for large datasets

### Action States

- **Available**: Action can be performed
- **Disabled**: Action not available for selection
- **Hidden**: Action not visible to current user

## Post Actions

### Publish Posts

Publishes selected draft posts immediately.

**Purpose**: Quickly publish multiple draft posts at once

**Requirements**:
- Posts must have status "draft"
- User must be Admin or Editor
- Posts must have required fields (title, content, category)

**What it does**:
1. Changes post status to "published"
2. Sets published_at to current timestamp
3. Triggers post published notifications
4. Updates search index
5. Clears relevant caches

**How to use**:
1. Navigate to Posts
2. Filter by Status: Draft
3. Select posts to publish
4. Click Actions → Publish Posts
5. Confirm action
6. View success message

**Example**:
```
✓ 5 posts published successfully!
```

**Notes**:
- Cannot be undone (but posts can be unpublished manually)
- Sends email notifications if configured
- Updates activity log
- Scheduled posts are not affected

### Feature Posts

Toggles the "featured" flag on selected posts.

**Purpose**: Mark or unmark posts as featured for homepage display

**Requirements**:
- User must be Admin or Editor
- Posts can be in any status

**What it does**:
1. Toggles is_featured field
2. Updates post timestamps
3. Clears cache
4. Logs activity

**How to use**:
1. Navigate to Posts
2. Select posts to feature/unfeature
3. Click Actions → Feature Posts
4. Confirm action
5. View success message

**Example**:
```
✓ 3 posts marked as featured!
```

**Notes**:
- Works as a toggle (featured → unfeatured, unfeatured → featured)
- Can be used on published or draft posts
- Featured posts appear in special sections
- Limit featured posts to 5-10 for best UX

### Export Posts

Exports selected posts to CSV format.

**Purpose**: Download post data for backup, analysis, or migration

**Requirements**:
- User must be Admin or Editor
- At least one post selected

**What it does**:
1. Generates CSV file with post data
2. Includes all fields and relationships
3. Downloads file to browser
4. Logs export activity

**How to use**:
1. Navigate to Posts
2. Select posts to export (or select all)
3. Click Actions → Export Posts
4. File downloads automatically

**Exported Fields**:
- ID
- Title
- Slug
- Excerpt
- Content
- Author Name
- Author Email
- Category Name
- Tags (comma-separated)
- Status
- Is Featured
- Is Trending
- View Count
- Published At
- Created At
- Updated At
- Meta Title
- Meta Description
- Meta Keywords

**File Format**:
```csv
ID,Title,Slug,Excerpt,Content,Author,Category,Tags,Status,Views,Published At
1,"Laravel Tips","laravel-tips","Quick tips...","Full content...","John Doe","Programming","laravel,php","published",150,"2025-01-15 10:30:00"
```

**Notes**:
- Large exports may take time
- File encoding: UTF-8
- Date format: Y-m-d H:i:s
- HTML content is preserved
- Can be imported into Excel, Google Sheets, etc.

## Comment Actions

### Approve Comments

Approves selected pending comments.

**Purpose**: Bulk approve legitimate comments

**Requirements**:
- Comments must have status "pending"
- User must be Admin or Editor

**What it does**:
1. Changes comment status to "approved"
2. Makes comments visible on posts
3. Sends notification to comment authors (if configured)
4. Updates comment count
5. Logs activity

**How to use**:
1. Navigate to Comments
2. Filter by Status: Pending
3. Review comments
4. Select comments to approve
5. Click Actions → Approve Comments
6. View success message

**Example**:
```
✓ 12 comments approved successfully!
```

**Notes**:
- Approved comments appear immediately on posts
- Cannot be undone (but can be marked as spam later)
- Consider reviewing content before approving
- Notifications sent if MAIL_MAILER configured

### Mark as Spam

Marks selected comments as spam.

**Purpose**: Bulk reject spam or inappropriate comments

**Requirements**:
- User must be Admin or Editor
- Comments can be in any status

**What it does**:
1. Changes comment status to "spam"
2. Hides comments from posts
3. Trains spam detection system
4. Updates spam statistics
5. Logs activity

**How to use**:
1. Navigate to Comments
2. Select spam comments
3. Click Actions → Mark as Spam
4. Confirm action
5. View success message

**Example**:
```
✓ 8 comments marked as spam!
```

**Notes**:
- Helps improve spam detection
- Can be reversed by changing status to "approved"
- Consider IP blocking for repeat offenders
- Review spam folder periodically for false positives

### Delete Comments

Permanently deletes selected comments.

**Purpose**: Remove comments completely from database

**Requirements**:
- User must be Admin
- Confirmation required

**What it does**:
1. Permanently deletes comments
2. Removes all associated data
3. Updates comment counts
4. Logs deletion activity

**How to use**:
1. Navigate to Comments
2. Select comments to delete
3. Click Actions → Delete Comments
4. Confirm deletion (cannot be undone!)
5. View success message

**Example**:
```
✓ 5 comments deleted permanently!
```

**Notes**:
- **Cannot be undone** - use with caution
- Consider marking as spam instead
- Deletes replies if parent comment deleted
- Admin only for safety

## Using Actions

### Basic Workflow

1. **Navigate** to resource (Posts, Comments, etc.)
2. **Filter** results if needed
3. **Select** items using checkboxes
4. **Click** Actions dropdown
5. **Choose** action
6. **Confirm** if prompted
7. **Review** success message

### Selection Methods

**Individual Selection**:
- Click checkbox next to each item
- Selected items highlighted

**Select All on Page**:
- Click checkbox in table header
- Selects all visible items

**Select All Matching**:
- Click "Select All Matching" link
- Selects all items matching current filters
- Use with caution for large datasets

**Deselect All**:
- Click checkbox in header again
- Or click "Deselect All" link

### Action Confirmation

Some actions require confirmation:

**Confirmation Dialog**:
```
Are you sure you want to publish 5 posts?

This will:
- Change status to "published"
- Set published_at timestamp
- Send notifications
- Update search index

[Cancel] [Confirm]
```

**Tips**:
- Read confirmation carefully
- Check selected item count
- Understand action consequences
- Use Cancel if unsure

### Action Results

**Success Message**:
```
✓ Action completed successfully!
  - 5 items processed
  - 0 errors
```

**Partial Success**:
```
⚠ Action completed with warnings!
  - 4 items processed
  - 1 item skipped (validation error)
```

**Failure Message**:
```
✗ Action failed!
  - Error: Insufficient permissions
  - 0 items processed
```

## Action Permissions

### Permission Matrix

| Action | Admin | Editor | Author | User |
|--------|-------|--------|--------|------|
| Publish Posts | ✓ | ✓ | ✗ | ✗ |
| Feature Posts | ✓ | ✓ | ✗ | ✗ |
| Export Posts | ✓ | ✓ | ✗ | ✗ |
| Approve Comments | ✓ | ✓ | ✗ | ✗ |
| Mark as Spam | ✓ | ✓ | ✗ | ✗ |
| Delete Comments | ✓ | ✗ | ✗ | ✗ |

### Permission Checks

Actions check permissions before running:

1. **User Role**: Must have required role
2. **Resource Access**: Must have access to resource
3. **Item Ownership**: May require ownership (for Authors)
4. **Action Authorization**: Specific action permission

**Example**:
```php
// Author can only publish own posts
if ($user->role === 'author' && $post->user_id !== $user->id) {
    return false; // Action hidden/disabled
}
```

## Best Practices

### Before Using Actions

1. **Review Selection**: Double-check selected items
2. **Use Filters**: Narrow down to target items
3. **Check Count**: Verify number of selected items
4. **Read Confirmation**: Understand what will happen
5. **Have Backup**: Ensure recent backup exists

### When to Use Actions

**Good Use Cases**:
- Publishing multiple reviewed drafts
- Approving legitimate comments in bulk
- Marking obvious spam
- Exporting data for reports
- Featuring seasonal content

**Avoid Actions For**:
- Single items (use edit instead)
- Uncertain selections
- Untested operations
- Critical data without backup

### Action Safety

**Safe Actions** (Low Risk):
- Export Posts
- Feature Posts
- Approve Comments

**Moderate Risk**:
- Publish Posts (can unpublish)
- Mark as Spam (can reverse)

**High Risk** (Cannot Undo):
- Delete Comments
- Bulk delete operations

### Performance Considerations

**Small Batches** (< 50 items):
- Run immediately
- Fast response
- No issues

**Medium Batches** (50-500 items):
- May take a few seconds
- Watch for timeouts
- Consider queuing

**Large Batches** (> 500 items):
- Should be queued
- Run in background
- Check job status

### Error Handling

**Common Errors**:

1. **Validation Error**:
   - Item doesn't meet requirements
   - Fix item and retry

2. **Permission Error**:
   - Insufficient permissions
   - Contact admin

3. **Timeout Error**:
   - Too many items selected
   - Reduce batch size

4. **Database Error**:
   - Constraint violation
   - Check relationships

### Monitoring Actions

**Activity Log**:
- All actions logged
- View in Activity Logs resource
- Includes user, timestamp, changes

**Audit Trail**:
```
User: admin@technewshub.com
Action: Publish Posts
Items: 5 posts
Time: 2025-01-15 10:30:00
Result: Success
```

## Troubleshooting

### Action Not Visible

**Possible Causes**:
- Insufficient permissions
- Wrong resource type
- No items selected
- Action disabled for selection

**Solution**:
- Check user role
- Verify resource
- Select items
- Review item status

### Action Fails

**Possible Causes**:
- Validation errors
- Permission issues
- Database constraints
- Timeout

**Solution**:
- Check error message
- Review item details
- Reduce batch size
- Contact admin

### Unexpected Results

**Possible Causes**:
- Wrong items selected
- Misunderstood action
- Cache issues

**Solution**:
- Review selection carefully
- Read action description
- Clear cache
- Check activity log

## Advanced Usage

### Queued Actions

For large batches, actions can be queued:

1. Action dispatched to queue
2. Runs in background
3. Notification on completion
4. Check job status in queue

**Benefits**:
- No timeout issues
- Better performance
- Can process thousands of items

### Custom Actions

Developers can create custom actions:

```php
php artisan nova:action PublishPosts
```

See developer documentation for details.

## Conclusion

Custom actions are powerful tools for bulk operations in Nova. Use them wisely, always review selections, and understand the consequences before confirming. When in doubt, process items individually or consult with your administrator.

For more information, see:
- [Nova User Guide](nova-user-guide.md)
- [Nova Troubleshooting](nova-troubleshooting.md)
- [Laravel Nova Documentation](https://nova.laravel.com/docs/actions)

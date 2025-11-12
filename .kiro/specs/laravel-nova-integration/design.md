# Laravel Nova Integration Design Document

## Overview

This design document outlines the architecture and implementation strategy for integrating Laravel Nova v5.7.6 into the Tech News Platform. The integration will replace the existing custom admin panel with Nova's modern, feature-rich interface while maintaining all current functionality and adding enhanced administrative capabilities.

### Design Goals

1. **Seamless Integration**: Install Nova from local directory without disrupting existing functionality
2. **Complete Feature Parity**: Ensure all current admin features are available in Nova
3. **Enhanced User Experience**: Leverage Nova's modern UI for improved admin workflows
4. **Role-Based Security**: Implement granular authorization using Laravel policies
5. **Extensibility**: Design custom tools and actions for platform-specific needs
6. **Performance**: Optimize resource queries and eager loading for large datasets

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Laravel Application                      │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────┐      ┌──────────────┐                     │
│  │   Frontend   │      │  Nova Admin  │                     │
│  │   (Blade)    │      │  Interface   │                     │
│  └──────┬───────┘      └──────┬───────┘                     │
│         │                     │                              │
│  ┌──────▼──────────────────────▼──────┐                     │
│  │      Application Routes             │                     │
│  └──────┬──────────────────────┬───────┘                     │
│         │                      │                              │
│  ┌──────▼──────┐      ┌────────▼────────┐                   │
│  │ Controllers │      │ Nova Resources  │                   │
│  └──────┬──────┘      └────────┬────────┘                   │
│         │                      │                              │
│  ┌──────▼──────────────────────▼──────┐                     │
│  │         Eloquent Models             │                     │
│  └──────┬──────────────────────────────┘                     │
│         │                                                     │
│  ┌──────▼──────┐                                             │
│  │   Database  │                                             │
│  └─────────────┘                                             │
└─────────────────────────────────────────────────────────────┘
```

### Nova Component Architecture

```
app/Nova/
├── Dashboards/
│   └── Main.php                    # Main dashboard with metrics
├── Resources/
│   ├── User.php                    # User management
│   ├── Post.php                    # Post management
│   ├── Category.php                # Category management
│   ├── Tag.php                     # Tag management
│   ├── Comment.php                 # Comment moderation
│   ├── Media.php                   # Media library
│   ├── Page.php                    # Static pages
│   ├── Newsletter.php              # Newsletter subscribers
│   ├── Setting.php                 # System settings
│   └── ActivityLog.php             # Activity logs
├── Actions/
│   ├── PublishPosts.php            # Bulk publish action
│   ├── FeaturePosts.php            # Bulk feature action
│   ├── ApproveComments.php         # Bulk approve action
│   └── ExportPosts.php             # Export to CSV
├── Filters/
│   ├── PostStatus.php              # Filter by post status
│   ├── PostCategory.php            # Filter by category
│   ├── CommentStatus.php           # Filter by comment status
│   └── UserRole.php                # Filter by user role
├── Metrics/
│   ├── TotalPosts.php              # Total posts count
│   ├── TotalUsers.php              # Total users count
│   ├── PostsPerDay.php             # Posts trend
│   └── PostsByStatus.php           # Posts partition
└── Tools/
    ├── MaintenanceMode.php         # Maintenance controls
    ├── CacheManager.php            # Cache management
    └── SystemHealth.php            # System monitoring
```

## Components and Interfaces

### 1. Nova Installation and Configuration


#### Installation Strategy

**Local Package Installation**:
- Copy Nova from `.data/laravel-nova_v5.7.6` to `vendor/laravel/nova`
- Add Nova to `composer.json` as a path repository
- Register Nova service provider in `bootstrap/providers.php`

**Configuration**:
```php
// config/nova.php
return [
    'name' => 'Tech News Admin',
    'path' => '/admin',
    'domain' => null,
    'guard' => 'web',
    'middleware' => ['web', 'auth'],
    'pagination' => 'links',
    'storage_disk' => 'public',
];
```

**Authentication Integration**:
- Use existing User model with `role` field
- Implement `Nova::auth()` gate in `NovaServiceProvider`
- Restrict access to users with admin, editor, or author roles

### 2. Resource Design

#### Base Resource Structure

All Nova resources will extend `Laravel\Nova\Resource` and follow this pattern:

```php
class Post extends Resource
{
    public static $model = \App\Models\Post::class;
    public static $title = 'title';
    public static $search = ['id', 'title', 'excerpt', 'content'];
    
    public function fields(NovaRequest $request): array
    {
        // Field definitions
    }
    
    public function filters(NovaRequest $request): array
    {
        // Filter definitions
    }
    
    public function actions(NovaRequest $request): array
    {
        // Action definitions
    }
}
```

#### Post Resource

**Fields**:
- ID (readonly)
- Title (Text, required, rules: max:255)
- Slug (Text, readonly, computed from title)
- Excerpt (Textarea, rules: max:500)
- Content (Trix editor, required)
- Featured Image (Image, disk: public, path: posts)
- Image Alt Text (Text)
- Category (BelongsTo relationship)
- Tags (BelongsToMany relationship)
- Author (BelongsTo User, readonly on edit)
- Status (Select: draft, published, scheduled)
- Is Featured (Boolean)
- Is Trending (Boolean)
- Published At (DateTime)
- Scheduled At (DateTime, visible when status=scheduled)
- Reading Time (Number, readonly, auto-calculated)
- View Count (Number, readonly)
- Meta Title (Text, SEO panel)
- Meta Description (Textarea, SEO panel)
- Meta Keywords (Text, SEO panel)

**Relationships**:
- BelongsTo: User (author), Category
- BelongsToMany: Tags
- HasMany: Comments, Views, Bookmarks, Reactions, Revisions

**Filters**:
- Status (draft, published, scheduled)
- Category
- Author
- Featured
- Date Range

**Actions**:
- Publish Posts
- Feature Posts
- Export Posts

#### User Resource

**Fields**:
- ID (readonly)
- Name (Text, required)
- Email (Email, required, unique)
- Password (Password, creation only, bcrypt)
- Role (Select: admin, editor, author, user)
- Avatar (Image, disk: public, path: avatars)
- Bio (Textarea)
- Status (Select: active, inactive, suspended)
- Email Verified At (DateTime, readonly)
- Created At (DateTime, readonly)

**Relationships**:
- HasMany: Posts, Comments, Media, Bookmarks, Reactions

**Filters**:
- Role
- Status

**Metrics**:
- Posts Count
- Comments Count

#### Category Resource

**Fields**:
- ID (readonly)
- Name (Text, required)
- Slug (Text, readonly)
- Description (Textarea)
- Parent Category (BelongsTo self, nullable)
- Icon (Text, icon class)
- Color Code (Color picker)
- Status (Select: active, inactive)
- Display Order (Number)
- Meta Title (Text)
- Meta Description (Textarea)

**Relationships**:
- BelongsTo: Parent Category
- HasMany: Child Categories, Posts

**Display**:
- Tree view for hierarchical categories
- Drag-and-drop ordering

#### Comment Resource

**Fields**:
- ID (readonly)
- Post (BelongsTo, with link)
- User (BelongsTo, nullable)
- Author Name (Text, for guest comments)
- Author Email (Email, for guest comments)
- Content (Textarea, required)
- Status (Select: pending, approved, spam)
- IP Address (Text, readonly)
- User Agent (Text, readonly, hidden by default)
- Created At (DateTime, readonly)

**Relationships**:
- BelongsTo: Post, User, Parent Comment
- HasMany: Replies

**Filters**:
- Status
- Post
- Date Range

**Actions**:
- Approve Comments
- Mark as Spam
- Delete Comments

#### Media Resource

**Fields**:
- ID (readonly)
- Thumbnail (Image preview)
- File Name (Text, readonly)
- File Path (Text, readonly)
- File Type (Badge: image, document, video)
- File Size (Text, readonly, human-readable)
- MIME Type (Text, readonly)
- Dimensions (Text, readonly, for images)
- Alt Text (Text)
- Title (Text)
- Caption (Textarea)
- Uploaded By (BelongsTo User)
- Created At (DateTime, readonly)

**Relationships**:
- BelongsTo: User

**Filters**:
- File Type
- Upload Date

**Actions**:
- Download Media
- Regenerate Thumbnails

### 3. Dashboard and Metrics

#### Main Dashboard

**Metrics**:

1. **Total Posts** (Value Metric)
   - Display total published posts
   - Show increase/decrease from previous period
   - Format: number with trend indicator

2. **Total Users** (Value Metric)
   - Display total active users
   - Show new users this month
   - Format: number with trend indicator

3. **Total Views** (Value Metric)
   - Display total post views
   - Show views this month
   - Format: number with trend indicator

4. **Posts Per Day** (Trend Metric)
   - Line chart showing post creation over time
   - Configurable ranges: 30, 60, 90 days
   - Format: line chart

5. **Posts By Status** (Partition Metric)
   - Pie chart showing draft, published, scheduled
   - Format: donut chart with percentages

6. **Posts By Category** (Partition Metric)
   - Bar chart showing top 10 categories
   - Format: horizontal bar chart

**Cards**:
- Recent Posts (list of 5 most recent)
- Pending Comments (count with link)
- Scheduled Posts (upcoming publications)
- System Health (storage, queue status)

### 4. Authorization and Policies

#### Policy Structure

Each resource will have a corresponding policy implementing:

```php
class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'author']);
    }
    
    public function view(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->isEditor() || $post->user_id === $user->id;
    }
    
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor', 'author']);
    }
    
    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->isEditor() || $post->user_id === $user->id;
    }
    
    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }
}
```

#### Role-Based Access Matrix

| Resource      | Admin | Editor | Author | User |
|---------------|-------|--------|--------|------|
| Users         | CRUD  | R      | -      | -    |
| Posts         | CRUD  | CRUD   | CRU*   | -    |
| Categories    | CRUD  | CRUD   | R      | -    |
| Tags          | CRUD  | CRUD   | R      | -    |
| Comments      | CRUD  | CRUD   | R      | -    |
| Media         | CRUD  | CRUD   | CRU*   | -    |
| Pages         | CRUD  | CRUD   | -      | -    |
| Settings      | CRUD  | R      | -      | -    |
| Activity Logs | R     | R      | -      | -    |
| Newsletters   | CRUD  | R      | -      | -    |

*Authors can only update/delete their own content

### 5. Custom Actions

#### PublishPosts Action

```php
class PublishPosts extends Action
{
    public function handle(ActionFields $fields, Collection $models)
    {
        foreach ($models as $post) {
            $post->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
        }
        
        return Action::message('Posts published successfully!');
    }
}
```

**Features**:
- Bulk operation on selected posts
- Updates status to published
- Sets published_at timestamp
- Triggers post published notifications
- Available only to admin and editor roles

#### FeaturePosts Action

**Features**:
- Toggle is_featured flag
- Bulk operation
- Confirmation dialog
- Success message with count

#### ApproveComments Action

**Features**:
- Bulk approve pending comments
- Updates status to approved
- Sends notification to comment authors
- Available to admin and editor roles

#### ExportPosts Action

**Features**:
- Export selected posts to CSV
- Includes all fields and relationships
- Generates downloadable file
- Configurable field selection

### 6. Custom Filters

#### PostStatus Filter

```php
class PostStatus extends Filter
{
    public function apply(NovaRequest $request, $query, $value)
    {
        return $query->where('status', $value);
    }
    
    public function options(NovaRequest $request): array
    {
        return [
            'Draft' => 'draft',
            'Published' => 'published',
            'Scheduled' => 'scheduled',
        ];
    }
}
```

#### Date Range Filters

- Created Date Range
- Published Date Range
- Custom date picker with presets (today, this week, this month, this year)

### 7. Custom Tools

#### Maintenance Mode Tool

**Features**:
- Toggle maintenance mode on/off
- Set custom maintenance message
- Whitelist IP addresses
- Schedule maintenance windows

**Interface**:
- Toggle switch for enable/disable
- Text area for custom message
- IP whitelist input
- Status indicator

#### Cache Manager Tool

**Features**:
- Clear application cache
- Clear route cache
- Clear config cache
- Clear view cache
- Clear all caches button

**Interface**:
- Individual clear buttons
- Clear all button
- Last cleared timestamps
- Cache size indicators

#### System Health Tool

**Features**:
- Database connection status
- Queue status and failed jobs count
- Storage usage (disk space)
- Log file sizes
- Recent errors

**Interface**:
- Status badges (green/yellow/red)
- Metrics display
- Quick action buttons
- Auto-refresh every 30 seconds

## Data Models

### Nova Resource Field Mapping



#### Post Model → Nova Fields

| Database Column    | Nova Field Type | Options                          |
|--------------------|-----------------|----------------------------------|
| id                 | ID              | readonly                         |
| user_id            | BelongsTo       | User resource, searchable        |
| category_id        | BelongsTo       | Category resource, searchable    |
| title              | Text            | required, sortable               |
| slug               | Text            | readonly, hideFromIndex          |
| excerpt            | Textarea        | rows: 3, maxlength: 500          |
| content            | Trix            | required, withFiles              |
| featured_image     | Image           | disk: public, prunable            |
| image_alt_text     | Text            | hideFromIndex                    |
| status             | Select          | options: draft/published/scheduled |
| is_featured        | Boolean         | default: false                   |
| is_trending        | Boolean         | default: false                   |
| view_count         | Number          | readonly, sortable               |
| published_at       | DateTime        | nullable, sortable               |
| scheduled_at       | DateTime        | nullable, dependsOn: status      |
| reading_time       | Number          | readonly, suffix: 'min'          |
| meta_title         | Text            | panel: SEO                       |
| meta_description   | Textarea        | panel: SEO, rows: 2              |
| meta_keywords      | Text            | panel: SEO                       |
| created_at         | DateTime        | readonly, sortable               |
| updated_at         | DateTime        | readonly                         |

#### User Model → Nova Fields

| Database Column    | Nova Field Type | Options                          |
|--------------------|-----------------|----------------------------------|
| id                 | ID              | readonly                         |
| name               | Text            | required, sortable               |
| email              | Email           | required, unique, sortable       |
| password           | Password        | creationRules: required|min:8    |
| role               | Select          | options: admin/editor/author/user |
| avatar             | Image           | disk: public, nullable           |
| bio                | Textarea        | rows: 4, nullable                |
| status             | Select          | options: active/inactive/suspended |
| email_verified_at  | DateTime        | readonly, nullable               |
| created_at         | DateTime        | readonly, sortable               |

#### Category Model → Nova Fields

| Database Column    | Nova Field Type | Options                          |
|--------------------|-----------------|----------------------------------|
| id                 | ID              | readonly                         |
| name               | Text            | required, sortable               |
| slug               | Text            | readonly                         |
| description        | Textarea        | rows: 3, nullable                |
| parent_id          | BelongsTo       | Category resource, nullable      |
| icon               | Text            | placeholder: 'fa-icon-name'      |
| color_code         | Color           | nullable                         |
| status             | Select          | options: active/inactive         |
| display_order      | Number          | default: 0, sortable             |
| meta_title         | Text            | panel: SEO                       |
| meta_description   | Textarea        | panel: SEO                       |

## Error Handling

### Validation Errors

**Strategy**:
- Use Nova's built-in validation
- Display inline field errors
- Show summary notification
- Prevent form submission until resolved

**Common Validations**:
```php
Text::make('Title')
    ->rules('required', 'max:255', 'unique:posts,title,{{resourceId}}')
    ->creationRules('required')
    ->updateRules('required'),
```

### Authorization Errors

**Strategy**:
- Return 403 Forbidden for unauthorized access
- Display user-friendly error message
- Log authorization failures
- Redirect to dashboard

**Implementation**:
```php
public function authorizedToUpdate(Request $request): bool
{
    return $request->user()->can('update', $this->resource);
}
```

### Database Errors

**Strategy**:
- Catch constraint violations
- Display meaningful error messages
- Log errors for debugging
- Provide recovery suggestions

**Example Scenarios**:
- Foreign key constraint (deleting category with posts)
- Unique constraint (duplicate slug)
- Connection errors (database down)

### File Upload Errors

**Strategy**:
- Validate file size and type before upload
- Display progress indicator
- Handle upload failures gracefully
- Clean up partial uploads

**Validations**:
```php
Image::make('Featured Image')
    ->rules('image', 'max:5120') // 5MB max
    ->acceptedTypes('image/jpeg', 'image/png', 'image/webp')
```

## Testing Strategy

### Unit Tests

**Nova Resource Tests**:
- Test field definitions
- Test authorization methods
- Test custom methods
- Test relationships

**Example**:
```php
public function test_post_resource_has_correct_fields()
{
    $resource = new PostResource(Post::factory()->create());
    $fields = $resource->fields(NovaRequest::create('/'));
    
    $this->assertCount(20, $fields);
    $this->assertInstanceOf(Text::class, $fields[0]);
}
```

### Feature Tests

**CRUD Operations**:
- Test creating resources
- Test updating resources
- Test deleting resources
- Test viewing resources

**Authorization Tests**:
- Test admin access
- Test editor access
- Test author access
- Test unauthorized access

**Action Tests**:
- Test bulk publish action
- Test bulk feature action
- Test export action
- Test approve comments action

**Example**:
```php
public function test_admin_can_create_post()
{
    $admin = User::factory()->admin()->create();
    
    $response = $this->actingAs($admin)
        ->post('/nova-api/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
            'status' => 'draft',
        ]);
    
    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', ['title' => 'Test Post']);
}
```

### Integration Tests

**Dashboard Tests**:
- Test metric calculations
- Test card displays
- Test dashboard loading

**Filter Tests**:
- Test status filter
- Test category filter
- Test date range filter

**Search Tests**:
- Test global search
- Test resource search
- Test relationship search

### Browser Tests (Dusk)

**User Workflows**:
- Test complete post creation workflow
- Test comment moderation workflow
- Test media upload workflow
- Test user management workflow

**Example**:
```php
public function test_admin_can_create_post_through_ui()
{
    $admin = User::factory()->admin()->create();
    
    $this->browse(function (Browser $browser) use ($admin) {
        $browser->loginAs($admin)
            ->visit('/admin/resources/posts/new')
            ->type('@title', 'Test Post')
            ->type('@content', 'Test content')
            ->select('@status', 'draft')
            ->press('Create Post')
            ->assertPathIs('/admin/resources/posts/*')
            ->assertSee('Test Post');
    });
}
```

## Performance Considerations

### Query Optimization

**Eager Loading**:
```php
public static function indexQuery(NovaRequest $request, $query)
{
    return $query->with(['user', 'category', 'tags']);
}
```

**Selective Field Loading**:
```php
public static function relatableQuery(NovaRequest $request, $query)
{
    return $query->select('id', 'name', 'email');
}
```

### Caching Strategy

**Resource Counts**:
- Cache dashboard metrics for 5 minutes
- Cache category post counts for 10 minutes
- Cache user statistics for 15 minutes

**Implementation**:
```php
public function calculate(NovaRequest $request)
{
    return Cache::remember('total_posts', 300, function () {
        return Post::published()->count();
    });
}
```

### Pagination

**Default Settings**:
- 25 items per page for most resources
- 50 items per page for simple resources (tags, categories)
- 10 items per page for media (due to thumbnails)

**Configuration**:
```php
public static $perPageOptions = [10, 25, 50, 100];
```

### Asset Optimization

**Image Handling**:
- Generate thumbnails on upload
- Use responsive images in Nova
- Lazy load images in index views
- Compress images automatically

**JavaScript/CSS**:
- Use Nova's built-in asset compilation
- Minimize custom JavaScript
- Leverage Nova's Vue components

## Migration Strategy

### Phase 1: Parallel Operation

**Duration**: 2 weeks

**Activities**:
1. Install Nova alongside existing admin
2. Create all Nova resources
3. Implement authorization policies
4. Test all CRUD operations
5. Train admin users on Nova interface

**Access**:
- Old admin: `/admin/*`
- Nova: `/nova/*`
- Both accessible simultaneously

### Phase 2: Feature Parity

**Duration**: 1 week

**Activities**:
1. Implement custom actions
2. Create custom tools
3. Build dashboard metrics
4. Add filters and lenses
5. Verify all features work

**Testing**:
- Side-by-side comparison
- User acceptance testing
- Performance benchmarking

### Phase 3: Cutover

**Duration**: 1 week

**Activities**:
1. Redirect old admin routes to Nova
2. Add deprecation notices
3. Update documentation
4. Monitor for issues
5. Provide user support

**Rollback Plan**:
- Keep old admin code for 30 days
- Easy toggle to revert if needed
- Database unchanged (no migrations)

### Phase 4: Cleanup

**Duration**: 1 week

**Activities**:
1. Remove old admin controllers
2. Remove old admin views
3. Remove old admin routes
4. Remove old admin middleware
5. Update tests

**Verification**:
- All tests passing
- No broken links
- Documentation updated
- User feedback positive

## Security Considerations

### Authentication

**Requirements**:
- Use existing Laravel authentication
- Enforce email verification
- Support password reset
- Session timeout after 2 hours

**Implementation**:
```php
Nova::auth(function (Request $request) {
    return Gate::check('viewNova', [$request->user()]);
});
```

### Authorization

**Requirements**:
- Role-based access control
- Resource-level permissions
- Field-level permissions
- Action-level permissions

**Implementation**:
- Use Laravel policies for all resources
- Check permissions in Nova resource methods
- Hide unauthorized actions/fields
- Log authorization failures

### Data Protection

**Requirements**:
- Sanitize HTML content
- Validate file uploads
- Prevent SQL injection
- Protect against XSS

**Implementation**:
- Use Nova's built-in validation
- Sanitize Trix editor content
- Validate image uploads
- Use parameterized queries

### Audit Logging

**Requirements**:
- Log all create/update/delete operations
- Track user actions
- Store IP addresses
- Retain logs for 90 days

**Implementation**:
- Use existing ActivityLog model
- Hook into Nova events
- Log through observers
- Archive old logs

## Deployment Considerations

### Environment Setup

**Requirements**:
- PHP 8.2+
- Laravel 12
- MySQL 8.0+ or PostgreSQL 13+
- Redis (optional, for caching)
- Node.js 18+ (for asset compilation)

### Installation Steps

1. **Copy Nova Files**:
   ```bash
   cp -r .data/laravel-nova_v5.7.6 vendor/laravel/nova
   ```

2. **Update Composer**:
   ```json
   {
       "repositories": [
           {
               "type": "path",
               "url": "./vendor/laravel/nova"
           }
       ],
       "require": {
           "laravel/nova": "*"
       }
   }
   ```

3. **Install Dependencies**:
   ```bash
   composer update
   php artisan nova:install
   php artisan migrate
   ```

4. **Publish Assets**:
   ```bash
   php artisan nova:publish
   npm install
   npm run build
   ```

5. **Configure Nova**:
   ```bash
   php artisan vendor:publish --tag=nova-config
   ```

### Production Checklist

- [ ] Nova assets compiled and published
- [ ] All resources tested
- [ ] Authorization policies verified
- [ ] Dashboard metrics working
- [ ] Custom actions functional
- [ ] Custom tools operational
- [ ] Performance optimized
- [ ] Security hardened
- [ ] Backup strategy in place
- [ ] Monitoring configured
- [ ] Documentation updated
- [ ] Users trained

### Monitoring

**Metrics to Track**:
- Nova page load times
- Resource query performance
- Action execution times
- Error rates
- User activity

**Tools**:
- Laravel Telescope (development)
- Application Performance Monitoring (production)
- Error tracking (Sentry, Bugsnag)
- Log aggregation (Papertrail, Loggly)

## Maintenance and Support

### Regular Tasks

**Daily**:
- Monitor error logs
- Check failed jobs
- Review user feedback

**Weekly**:
- Review performance metrics
- Check for Nova updates
- Backup database

**Monthly**:
- Update Nova if new version available
- Review and optimize slow queries
- Archive old activity logs
- User training sessions

### Troubleshooting Guide

**Common Issues**:

1. **Nova not loading**:
   - Check asset compilation
   - Verify route registration
   - Check middleware configuration

2. **Authorization errors**:
   - Verify policy registration
   - Check user roles
   - Review gate definitions

3. **Slow performance**:
   - Check query counts (N+1)
   - Review eager loading
   - Optimize database indexes

4. **File upload failures**:
   - Check disk permissions
   - Verify storage configuration
   - Review file size limits

### Support Resources

- Laravel Nova Documentation: https://nova.laravel.com/docs
- Laravel Nova GitHub: https://github.com/laravel/nova-issues
- Laravel Discord: Nova channel
- Internal documentation wiki
- Admin user training materials

# Database Schema Documentation

## Entity Relationship Diagram

```mermaid
erDiagram
    users ||--o{ posts : creates
    users ||--o{ comments : writes
    users ||--o{ media_library : uploads
    users ||--o{ bookmarks : has
    users ||--o{ reactions : makes
    users ||--o{ post_revisions : creates
    users ||--o{ search_logs : performs
    users ||--o{ activity_logs : causes
    
    categories ||--o{ posts : contains
    categories ||--o{ categories : "parent of"
    
    posts ||--o{ comments : has
    posts ||--o{ post_views : receives
    posts ||--o{ bookmarks : bookmarked_by
    posts ||--o{ reactions : receives
    posts ||--o{ post_revisions : has
    posts ||--o{ search_clicks : clicked_in
    posts }o--o{ tags : tagged_with
    
    tags }o--o{ posts : tags
    
    search_logs ||--o{ search_clicks : tracks
    
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at NULL
        string password
        enum role
        string avatar NULL
        text bio NULL
        enum status
        string remember_token NULL
        timestamps
    }

    categories {
        bigint id PK
        string name
        string slug UK
        text description NULL
        bigint parent_id FK NULL
        string icon NULL
        string color_code NULL
        string meta_title NULL
        text meta_description NULL
        enum status
        int display_order
        timestamps
    }
    
    posts {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        string title
        string slug UK
        text excerpt NULL
        longtext content
        string featured_image NULL
        string image_alt_text NULL
        enum status
        boolean is_featured
        boolean is_trending
        int view_count
        timestamp published_at NULL
        timestamp scheduled_at NULL
        int reading_time NULL
        string meta_title NULL
        text meta_description NULL
        string meta_keywords NULL
        timestamps
        softDeletes
    }
    
    tags {
        bigint id PK
        string name
        string slug UK
        timestamps
    }

    comments {
        bigint id PK
        bigint post_id FK
        bigint user_id FK NULL
        bigint parent_id FK NULL
        string author_name
        string author_email
        text content
        enum status
        string ip_address NULL
        text user_agent NULL
        timestamps
        softDeletes
    }
    
    media_library {
        bigint id PK
        bigint user_id FK
        string file_name
        string file_path
        string file_type
        int file_size
        string mime_type
        string alt_text NULL
        string title NULL
        text caption NULL
        json metadata NULL
        timestamps
    }
    
    post_tag {
        bigint post_id FK PK
        bigint tag_id FK PK
    }
    
    bookmarks {
        bigint id PK
        bigint user_id FK
        bigint post_id FK
        timestamps
    }

    reactions {
        bigint id PK
        bigint post_id FK
        bigint user_id FK NULL
        string type
        string ip_address NULL
        string user_agent NULL
        timestamps
    }
    
    post_revisions {
        bigint id PK
        bigint post_id FK
        bigint user_id FK
        string title
        text content
        text excerpt NULL
        json meta_data NULL
        string revision_note NULL
        timestamps
    }
    
    post_views {
        bigint id PK
        bigint post_id FK
        string ip_address
        text user_agent NULL
        timestamp viewed_at
    }
    
    activity_logs {
        bigint id PK
        string log_name NULL
        text description
        string subject_type NULL
        bigint subject_id NULL
        string event NULL
        string causer_type NULL
        bigint causer_id NULL
        json properties NULL
        string ip_address NULL
        string user_agent NULL
        timestamps
    }

    search_logs {
        bigint id PK
        string query
        int result_count
        float execution_time NULL
        string search_type
        boolean fuzzy_enabled
        int threshold NULL
        json filters NULL
        string ip_address NULL
        string user_agent NULL
        bigint user_id FK NULL
        timestamps
    }
    
    search_clicks {
        bigint id PK
        bigint search_log_id FK
        bigint post_id FK
        int position
        timestamps
    }
    
    newsletters {
        bigint id PK
        string email UK
        enum status
        timestamp verified_at NULL
        string token NULL
        timestamps
    }
    
    pages {
        bigint id PK
        string title
        string slug UK
        longtext content
        string meta_title NULL
        text meta_description NULL
        enum status
        string template
        int display_order
        timestamps
    }

    settings {
        bigint id PK
        string key UK
        text value NULL
        string group
        timestamps
    }
    
    contact_messages {
        bigint id PK
        string name
        string email
        string subject
        text message
        enum status
        timestamps
    }
```

---

## Table Documentation

### Core Tables

#### users
**Purpose:** Stores user accounts with authentication and profile information

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| name | string | No | - | User's full name |
| email | string | No | - | Unique email address |
| email_verified_at | timestamp | Yes | NULL | Email verification timestamp |
| password | string | No | - | Hashed password |
| role | enum | No | 'author' | User role: admin, editor, author |
| avatar | string | Yes | NULL | Avatar image path |
| bio | text | Yes | NULL | User biography |
| status | enum | No | 'active' | Account status: active, inactive |
| remember_token | string | Yes | NULL | Remember me token |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (email)

**Relationships:**
- hasMany: posts, comments, media_library, bookmarks, reactions, post_revisions, search_logs, activity_logs


#### categories
**Purpose:** Hierarchical category system for organizing posts

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| name | string | No | - | Category name |
| slug | string | No | - | URL-friendly slug |
| description | text | Yes | NULL | Category description |
| parent_id | bigint | Yes | NULL | Parent category (self-referencing) |
| icon | string | Yes | NULL | Icon identifier |
| color_code | string(7) | Yes | NULL | Hex color code |
| meta_title | string | Yes | NULL | SEO meta title |
| meta_description | text | Yes | NULL | SEO meta description |
| status | enum | No | 'active' | Status: active, inactive |
| display_order | int | No | 0 | Sort order |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (slug)
- INDEX (slug)
- INDEX (status)
- INDEX (display_order)

**Constraints:**
- FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE

**Relationships:**
- hasMany: posts, categories (children)
- belongsTo: category (parent)


#### posts
**Purpose:** Main content table storing blog posts/articles

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| user_id | bigint | No | - | Author (FK to users) |
| category_id | bigint | No | - | Category (FK to categories) |
| title | string | No | - | Post title |
| slug | string | No | - | URL-friendly slug |
| excerpt | text | Yes | NULL | Short summary |
| content | longtext | No | - | Full post content |
| featured_image | string | Yes | NULL | Featured image path |
| image_alt_text | string | Yes | NULL | Image alt text for accessibility |
| status | enum | No | 'draft' | Status: draft, published, scheduled, archived |
| is_featured | boolean | No | false | Featured post flag |
| is_trending | boolean | No | false | Trending post flag |
| view_count | int | No | 0 | Total view count |
| published_at | timestamp | Yes | NULL | Publication timestamp |
| scheduled_at | timestamp | Yes | NULL | Scheduled publication time |
| reading_time | int | Yes | NULL | Estimated reading time (minutes) |
| meta_title | string | Yes | NULL | SEO meta title |
| meta_description | text | Yes | NULL | SEO meta description |
| meta_keywords | string | Yes | NULL | SEO keywords |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |
| deleted_at | timestamp | Yes | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (slug)
- INDEX (slug)
- INDEX (status)
- INDEX (published_at)
- INDEX (is_featured)
- INDEX (is_trending)
- INDEX (user_id, status)
- INDEX (category_id, status)
- INDEX (status, scheduled_at) [posts_scheduled_lookup]

**Constraints:**
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: user, category
- hasMany: comments, post_views, bookmarks, reactions, post_revisions, search_clicks
- belongsToMany: tags (through post_tag)


#### tags
**Purpose:** Tagging system for posts

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| name | string | No | - | Tag name |
| slug | string | No | - | URL-friendly slug |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (slug)
- INDEX (slug)

**Relationships:**
- belongsToMany: posts (through post_tag)

#### post_tag (Pivot Table)
**Purpose:** Many-to-many relationship between posts and tags

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| post_id | bigint | No | - | FK to posts |
| tag_id | bigint | No | - | FK to tags |

**Indexes:**
- PRIMARY KEY (post_id, tag_id)

**Constraints:**
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
- FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE


### Engagement Tables

#### comments
**Purpose:** User comments on posts with nested threading support

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| post_id | bigint | No | - | FK to posts |
| user_id | bigint | Yes | NULL | FK to users (NULL for guests) |
| parent_id | bigint | Yes | NULL | Parent comment for threading |
| author_name | string | No | - | Comment author name |
| author_email | string | No | - | Comment author email |
| content | text | No | - | Comment content |
| status | enum | No | 'pending' | Status: pending, approved, spam |
| ip_address | string(45) | Yes | NULL | Commenter IP address |
| user_agent | text | Yes | NULL | Browser user agent |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |
| deleted_at | timestamp | Yes | NULL | Soft delete timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (post_id)
- INDEX (status)
- INDEX (created_at)

**Constraints:**
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: post, user, comment (parent)
- hasMany: comments (replies)


#### bookmarks
**Purpose:** User bookmarks/saved posts

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| user_id | bigint | No | - | FK to users |
| post_id | bigint | No | - | FK to posts |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (user_id, post_id)

**Constraints:**
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: user, post

#### reactions
**Purpose:** User reactions to posts (like, love, etc.)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| post_id | bigint | No | - | FK to posts |
| user_id | bigint | Yes | NULL | FK to users (NULL for guests) |
| type | string | No | - | Reaction type: like, love, laugh, wow, sad, angry |
| ip_address | string | Yes | NULL | User IP address |
| user_agent | string | Yes | NULL | Browser user agent |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (post_id, user_id, type)
- INDEX (post_id, type)

**Constraints:**
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: post, user


#### post_views
**Purpose:** Track individual post views for analytics

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| post_id | bigint | No | - | FK to posts |
| ip_address | string(45) | No | - | Viewer IP address |
| user_agent | text | Yes | NULL | Browser user agent |
| viewed_at | timestamp | No | - | View timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (post_id)
- INDEX (viewed_at)

**Constraints:**
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: post

### Content Management Tables

#### post_revisions
**Purpose:** Version history for posts

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| post_id | bigint | No | - | FK to posts |
| user_id | bigint | No | - | FK to users (editor) |
| title | string | No | - | Post title at revision |
| content | text | No | - | Post content at revision |
| excerpt | text | Yes | NULL | Post excerpt at revision |
| meta_data | json | Yes | NULL | Additional metadata |
| revision_note | string | Yes | NULL | Editor's revision note |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (post_id)

**Constraints:**
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: post, user


#### media_library
**Purpose:** Centralized media file management

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| user_id | bigint | No | - | FK to users (uploader) |
| file_name | string | No | - | Original filename |
| file_path | string | No | - | Storage path |
| file_type | string(50) | No | - | File type category |
| file_size | int | No | - | File size in bytes |
| mime_type | string | No | - | MIME type |
| alt_text | string | Yes | NULL | Alt text for accessibility |
| title | string | Yes | NULL | Media title |
| caption | text | Yes | NULL | Media caption |
| metadata | json | Yes | NULL | Additional metadata (dimensions, etc.) |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (user_id)
- INDEX (file_type)

**Constraints:**
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: user

#### pages
**Purpose:** Static pages (About, Contact, etc.)

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| title | string | No | - | Page title |
| slug | string | No | - | URL-friendly slug |
| content | longtext | No | - | Page content |
| meta_title | string | Yes | NULL | SEO meta title |
| meta_description | text | Yes | NULL | SEO meta description |
| status | enum | No | 'draft' | Status: draft, published |
| template | string | No | 'default' | Template name |
| display_order | int | No | 0 | Sort order |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (slug)
- INDEX (slug)
- INDEX (status)


### Search & Analytics Tables

#### search_logs
**Purpose:** Track search queries and performance metrics

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| query | string(500) | No | - | Search query text |
| result_count | int | No | 0 | Number of results returned |
| execution_time | float | Yes | NULL | Query execution time (seconds) |
| search_type | string(50) | No | 'posts' | Type: posts, tags, categories, admin |
| fuzzy_enabled | boolean | No | true | Whether fuzzy search was used |
| threshold | int | Yes | NULL | Fuzzy match threshold used |
| filters | json | Yes | NULL | Applied filters (category, date, etc.) |
| ip_address | string | Yes | NULL | User IP address |
| user_agent | string(500) | Yes | NULL | Browser user agent |
| user_id | bigint | Yes | NULL | FK to users (NULL for guests) |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (query, created_at)
- INDEX (result_count)
- INDEX (created_at)

**Constraints:**
- FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

**Relationships:**
- belongsTo: user
- hasMany: search_clicks

**Usage Examples:**
```php
// Log a search query
SearchLog::create([
    'query' => 'laravel fuzzy search',
    'result_count' => 15,
    'execution_time' => 0.045,
    'search_type' => 'posts',
    'fuzzy_enabled' => true,
    'threshold' => 80,
    'filters' => ['category_id' => 5],
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'user_id' => auth()->id(),
]);
```


#### search_clicks
**Purpose:** Track which search results users click

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| search_log_id | bigint | No | - | FK to search_logs |
| post_id | bigint | No | - | FK to posts (clicked result) |
| position | int | No | - | Position in search results (1-based) |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (search_log_id, post_id)

**Constraints:**
- FOREIGN KEY (search_log_id) REFERENCES search_logs(id) ON DELETE CASCADE
- FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE

**Relationships:**
- belongsTo: search_log, post

**Usage Examples:**
```php
// Track a search result click
SearchClick::create([
    'search_log_id' => $searchLog->id,
    'post_id' => $post->id,
    'position' => 3, // Third result in the list
]);

// Analyze click-through rates
$topClickedResults = SearchClick::query()
    ->select('post_id', DB::raw('COUNT(*) as clicks'))
    ->groupBy('post_id')
    ->orderByDesc('clicks')
    ->limit(10)
    ->get();
```

#### activity_logs
**Purpose:** Polymorphic activity logging system

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| log_name | string | Yes | NULL | Log category/name |
| description | text | No | - | Activity description |
| subject_type | string | Yes | NULL | Subject model class |
| subject_id | bigint | Yes | NULL | Subject model ID |
| event | string | Yes | NULL | Event type (created, updated, deleted) |
| causer_type | string | Yes | NULL | Causer model class |
| causer_id | bigint | Yes | NULL | Causer model ID |
| properties | json | Yes | NULL | Additional properties |
| ip_address | string | Yes | NULL | User IP address |
| user_agent | string | Yes | NULL | Browser user agent |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (subject_type, subject_id)
- INDEX (causer_type, causer_id)
- INDEX (log_name)

**Relationships:**
- morphTo: subject, causer


### System Tables

#### settings
**Purpose:** Application-wide configuration settings

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| key | string | No | - | Setting key (unique) |
| value | text | Yes | NULL | Setting value |
| group | string(50) | No | 'general' | Setting group |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (key)
- INDEX (key)
- INDEX (group)

#### newsletters
**Purpose:** Newsletter subscription management

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| email | string | No | - | Subscriber email |
| status | enum | No | 'subscribed' | Status: subscribed, unsubscribed |
| verified_at | timestamp | Yes | NULL | Email verification timestamp |
| token | string | Yes | NULL | Verification token |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- UNIQUE (email)
- INDEX (email)
- INDEX (status)

#### contact_messages
**Purpose:** Contact form submissions

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | AUTO | Primary key |
| name | string | No | - | Sender name |
| email | string | No | - | Sender email |
| subject | string | No | - | Message subject |
| message | text | No | - | Message content |
| status | enum | No | 'new' | Status: new, read, replied |
| created_at | timestamp | No | NOW | Creation timestamp |
| updated_at | timestamp | No | NOW | Last update timestamp |

**Indexes:**
- PRIMARY KEY (id)
- INDEX (status)
- INDEX (created_at)


---

## Migration History

### Initial Setup (0001_01_01_*)
1. **create_users_table** - Base user authentication with sessions and password resets
2. **create_cache_table** - Cache and cache locks for performance
3. **create_jobs_table** - Queue system with job batches and failed jobs

### Core Content System (2025_11_11_1903*)
4. **add_fields_to_users_table** - Extended user profiles (role, avatar, bio, status)
5. **create_categories_table** - Hierarchical category system with SEO
6. **create_posts_table** - Main content table with full CMS features
7. **create_tags_table** - Simple tagging system
8. **create_comments_table** - Nested comments with moderation
9. **create_media_library_table** - Centralized media management
10. **create_post_tag_table** - Many-to-many posts/tags relationship

### Supporting Features (2025_11_11_1903*)
11. **create_newsletters_table** - Newsletter subscriptions
12. **create_pages_table** - Static pages
13. **create_settings_table** - Application settings
14. **create_contact_messages_table** - Contact form
15. **create_post_views_table** - View tracking

### Engagement Features (2025_11_11_2039*)
16. **create_bookmarks_table** - User bookmarks
17. **create_reactions_table** - Post reactions (like, love, etc.)
18. **create_post_revisions_table** - Version control for posts

### System Features (2025_11_11_21*)
19. **create_activity_logs_table** - Polymorphic activity logging

### Performance Optimizations (2025_11_11_23*)
20. **add_scheduled_posts_index_to_posts_table** - Composite index for scheduled post queries
21. **add_metadata_to_media_library_table** - JSON metadata for media files

### Search System (2025_11_12_00*)
22. **create_search_logs_table** - Search query tracking and analytics
23. **create_search_clicks_table** - Search result click tracking

---

## Schema Optimization Analysis

### ✅ Well-Optimized Areas

1. **Foreign Key Indexes**: All foreign keys have proper indexes
2. **Composite Indexes**: Strategic composite indexes on posts table
3. **Soft Deletes**: Implemented on posts and comments for data recovery
4. **Cascade Behaviors**: Proper cascade deletes prevent orphaned records
5. **Status Indexes**: All status columns are indexed for filtering

### ⚠️ Potential Improvements

#### Missing Indexes

1. **users.role** - Frequently filtered, should be indexed
   ```php
   $table->index('role');
   ```

2. **users.status** - Used in authentication checks
   ```php
   $table->index('status');
   ```

3. **comments.user_id** - Missing FK index
   ```php
   $table->index('user_id');
   ```

4. **reactions.user_id** - Missing FK index
   ```php
   $table->index('user_id');
   ```

5. **post_revisions.user_id** - Missing FK index
   ```php
   $table->index('user_id');
   ```

6. **search_logs.user_id** - Missing FK index
   ```php
   $table->index('user_id');
   ```


#### Recommended Composite Indexes

1. **posts (status, published_at, is_featured)** - Homepage featured posts query
   ```php
   $table->index(['status', 'published_at', 'is_featured'], 'posts_featured_lookup');
   ```

2. **posts (status, is_trending, published_at)** - Trending posts query
   ```php
   $table->index(['status', 'is_trending', 'published_at'], 'posts_trending_lookup');
   ```

3. **comments (post_id, status, created_at)** - Approved comments per post
   ```php
   $table->index(['post_id', 'status', 'created_at'], 'comments_post_approved');
   ```

4. **search_logs (search_type, created_at)** - Analytics by search type
   ```php
   $table->index(['search_type', 'created_at']);
   ```

5. **search_logs (result_count, created_at)** - No-result queries analysis
   ```php
   $table->index(['result_count', 'created_at']);
   ```

#### Full-Text Search Indexes

For better search performance, consider adding full-text indexes:

```php
// posts table
$table->fullText(['title', 'excerpt', 'content'], 'posts_fulltext');

// categories table
$table->fullText(['name', 'description'], 'categories_fulltext');

// tags table
$table->fullText('name', 'tags_fulltext');
```

### 🔍 N+1 Query Prevention

Common queries that need eager loading:

```php
// Posts with relationships
Post::with(['user', 'category', 'tags', 'comments.user'])
    ->where('status', 'published')
    ->latest('published_at')
    ->get();

// Comments with nested replies
Comment::with(['user', 'replies.user'])
    ->where('post_id', $postId)
    ->where('status', 'approved')
    ->whereNull('parent_id')
    ->get();

// Search logs with clicks
SearchLog::with(['user', 'clicks.post'])
    ->latest()
    ->get();
```

### 📊 Partitioning Recommendations

For high-traffic sites, consider partitioning:

1. **post_views** - Partition by month (viewed_at)
2. **search_logs** - Partition by month (created_at)
3. **activity_logs** - Partition by month (created_at)

### 🗑️ Data Retention Policies

Implement archival strategies:

```php
// Archive old search logs (older than 6 months)
SearchLog::where('created_at', '<', now()->subMonths(6))->delete();

// Archive old post views (older than 1 year)
PostView::where('viewed_at', '<', now()->subYear())->delete();

// Archive old activity logs (older than 3 months)
ActivityLog::where('created_at', '<', now()->subMonths(3))->delete();
```


---

## Relationship Summary

### One-to-Many Relationships

| Parent | Child | Cascade Delete |
|--------|-------|----------------|
| users | posts | ✅ Yes |
| users | comments | ✅ Yes |
| users | media_library | ✅ Yes |
| users | bookmarks | ✅ Yes |
| users | reactions | ✅ Yes |
| users | post_revisions | ✅ Yes |
| users | search_logs | ❌ No (SET NULL) |
| categories | posts | ✅ Yes |
| categories | categories | ✅ Yes (self-referencing) |
| posts | comments | ✅ Yes |
| posts | post_views | ✅ Yes |
| posts | bookmarks | ✅ Yes |
| posts | reactions | ✅ Yes |
| posts | post_revisions | ✅ Yes |
| posts | search_clicks | ✅ Yes |
| comments | comments | ✅ Yes (self-referencing) |
| search_logs | search_clicks | ✅ Yes |

### Many-to-Many Relationships

| Table 1 | Pivot Table | Table 2 | Notes |
|---------|-------------|---------|-------|
| posts | post_tag | tags | Standard pivot, no timestamps |

### Polymorphic Relationships

| Table | Morph Columns | Related Models |
|-------|---------------|----------------|
| activity_logs | subject_type, subject_id | Any model |
| activity_logs | causer_type, causer_id | Typically User |

---

## Data Dictionary

### Enum Values

#### users.role
- `admin` - Full system access
- `editor` - Can manage all content
- `author` - Can manage own content

#### users.status
- `active` - Account is active
- `inactive` - Account is disabled

#### categories.status
- `active` - Category is visible
- `inactive` - Category is hidden

#### posts.status
- `draft` - Work in progress
- `published` - Live and visible
- `scheduled` - Scheduled for future publication
- `archived` - No longer active

#### comments.status
- `pending` - Awaiting moderation
- `approved` - Visible to public
- `spam` - Marked as spam

#### reactions.type
- `like` - Standard like
- `love` - Love reaction
- `laugh` - Funny reaction
- `wow` - Surprised reaction
- `sad` - Sad reaction
- `angry` - Angry reaction

#### newsletters.status
- `subscribed` - Active subscription
- `unsubscribed` - Opted out

#### pages.status
- `draft` - Not published
- `published` - Live page

#### contact_messages.status
- `new` - Unread message
- `read` - Message has been read
- `replied` - Response sent

#### search_logs.search_type
- `posts` - Post search
- `tags` - Tag search
- `categories` - Category search
- `admin` - Admin panel search


### JSON Column Structures

#### posts.meta_keywords
```json
"laravel, php, web development, tutorial"
```

#### media_library.metadata
```json
{
  "width": 1920,
  "height": 1080,
  "format": "jpeg",
  "size_formatted": "2.5 MB",
  "exif": {
    "camera": "Canon EOS 5D",
    "iso": 400,
    "aperture": "f/2.8"
  }
}
```

#### post_revisions.meta_data
```json
{
  "changed_fields": ["title", "content"],
  "word_count": 1250,
  "previous_status": "draft"
}
```

#### search_logs.filters
```json
{
  "category_id": 5,
  "author_id": 12,
  "date_from": "2025-01-01",
  "date_to": "2025-12-31",
  "tags": [1, 3, 7]
}
```

#### activity_logs.properties
```json
{
  "attributes": {
    "title": "New Post Title",
    "status": "published"
  },
  "old": {
    "title": "Old Post Title",
    "status": "draft"
  }
}
```

---

## Common Query Patterns

### Homepage Queries

```php
// Featured posts
Post::where('status', 'published')
    ->where('is_featured', true)
    ->where('published_at', '<=', now())
    ->with(['user', 'category', 'tags'])
    ->latest('published_at')
    ->limit(5)
    ->get();

// Trending posts
Post::where('status', 'published')
    ->where('is_trending', true)
    ->where('published_at', '<=', now())
    ->with(['user', 'category'])
    ->orderByDesc('view_count')
    ->limit(10)
    ->get();
```

### Search Queries

```php
// Basic search with analytics
$searchLog = SearchLog::create([
    'query' => $query,
    'search_type' => 'posts',
    'fuzzy_enabled' => true,
    'user_id' => auth()->id(),
]);

$results = Post::where('status', 'published')
    ->where(function($q) use ($query) {
        $q->where('title', 'like', "%{$query}%")
          ->orWhere('content', 'like', "%{$query}%");
    })
    ->with(['user', 'category', 'tags'])
    ->get();

$searchLog->update(['result_count' => $results->count()]);
```

### Analytics Queries

```php
// Top search queries
SearchLog::select('query', DB::raw('COUNT(*) as count'))
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('query')
    ->orderByDesc('count')
    ->limit(10)
    ->get();

// No-result queries
SearchLog::where('result_count', 0)
    ->where('created_at', '>=', now()->subDays(7))
    ->select('query', DB::raw('COUNT(*) as count'))
    ->groupBy('query')
    ->orderByDesc('count')
    ->get();

// Most clicked posts from search
SearchClick::select('post_id', DB::raw('COUNT(*) as clicks'))
    ->whereHas('searchLog', function($q) {
        $q->where('created_at', '>=', now()->subDays(30));
    })
    ->groupBy('post_id')
    ->orderByDesc('clicks')
    ->with('post')
    ->limit(10)
    ->get();
```

### Content Management Queries

```php
// Scheduled posts ready to publish
Post::where('status', 'scheduled')
    ->where('scheduled_at', '<=', now())
    ->get();

// Posts needing review
Post::where('status', 'draft')
    ->where('updated_at', '<', now()->subDays(7))
    ->with('user')
    ->get();

// Popular posts by engagement
Post::withCount(['comments', 'reactions', 'bookmarks'])
    ->where('status', 'published')
    ->orderByDesc('view_count')
    ->orderByDesc('comments_count')
    ->limit(20)
    ->get();
```

---

## Security Considerations

### Sensitive Data

1. **IP Addresses** - Stored in multiple tables for security/analytics
   - Consider GDPR compliance and data retention
   - Implement IP anonymization for EU users

2. **User Agents** - Stored for fraud detection
   - Truncate to 500 characters to prevent abuse
   - Consider hashing for privacy

3. **Email Addresses** - Unique and indexed
   - Always validate and sanitize
   - Implement rate limiting on email-based operations

### SQL Injection Prevention

All queries use Laravel's query builder or Eloquent ORM, which provides automatic parameter binding and escaping.

### Mass Assignment Protection

Ensure all models define `$fillable` or `$guarded` properties:

```php
// Example for SearchLog model
protected $fillable = [
    'query',
    'result_count',
    'execution_time',
    'search_type',
    'fuzzy_enabled',
    'threshold',
    'filters',
    'ip_address',
    'user_agent',
    'user_id',
];
```

---

## Performance Benchmarks

### Expected Query Times (with proper indexes)

| Query Type | Expected Time | Notes |
|------------|---------------|-------|
| Single post by slug | < 10ms | With eager loading |
| Post list (paginated) | < 50ms | 20 items with relationships |
| Search query | < 100ms | Full-text search on 10k posts |
| Category posts | < 30ms | With proper indexes |
| User dashboard | < 80ms | Multiple aggregations |
| Analytics queries | < 200ms | Complex aggregations |

### Monitoring Recommendations

```php
// Log slow queries in AppServiceProvider
DB::listen(function ($query) {
    if ($query->time > 1000) { // 1 second
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
        ]);
    }
});
```

---

## Backup & Recovery

### Critical Tables (Priority 1)
- users
- posts
- categories
- tags
- post_tag

### Important Tables (Priority 2)
- comments
- media_library
- pages
- settings

### Analytics Tables (Priority 3)
- search_logs
- search_clicks
- post_views
- activity_logs

### Recommended Backup Schedule
- **Full backup**: Daily at 2 AM
- **Incremental backup**: Every 6 hours
- **Retention**: 30 days for full, 7 days for incremental

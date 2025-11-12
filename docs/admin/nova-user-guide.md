# Laravel Nova User Guide

## Table of Contents

- [Introduction](#introduction)
- [Getting Started](#getting-started)
- [Dashboard](#dashboard)
- [Resources](#resources)
- [Search and Filters](#search-and-filters)
- [Actions](#actions)
- [User Roles and Permissions](#user-roles-and-permissions)
- [Common Tasks](#common-tasks)

## Introduction

Laravel Nova is the modern administration interface for TechNewsHub. This guide will help you navigate and use Nova effectively for content management, user administration, and system monitoring.

### What is Nova?

Nova provides a beautiful, intuitive interface for managing your content and users. It includes:

- **Dashboard** with key metrics and statistics
- **Resources** for managing posts, users, categories, and more
- **Search** functionality across all content
- **Filters** to quickly find specific items
- **Actions** for bulk operations
- **Tools** for system management

### Accessing Nova

1. Navigate to `http://yourdomain.com/admin`
2. Log in with your credentials
3. You'll see the main dashboard

## Getting Started

### Navigation

The Nova interface consists of:

- **Sidebar**: Main navigation menu with resources and tools
- **Top Bar**: Search, notifications, and user menu
- **Main Content**: Dashboard, resource lists, or detail views

### Keyboard Shortcuts

- `Cmd/Ctrl + K`: Open global search
- `Cmd/Ctrl + /`: Focus search in current resource
- `Esc`: Close modals and dialogs

### User Interface Elements

- **Resource Index**: List view of items (posts, users, etc.)
- **Resource Detail**: Detailed view of a single item
- **Create/Edit Forms**: Forms for creating or editing items
- **Filters**: Sidebar filters to refine lists
- **Actions**: Dropdown menu for bulk operations

## Dashboard

The main dashboard displays key metrics and statistics about your platform.

### Available Metrics

#### Total Posts
- Shows the total number of published posts
- Displays trend compared to previous period
- Click to view all posts

#### Total Users
- Shows the total number of active users
- Displays new users this month
- Click to view all users

#### Total Views
- Shows total post views
- Displays views for current month
- Click to view analytics

#### Posts Per Day
- Line chart showing post creation over time
- Configurable time ranges (30, 60, 90 days)
- Hover to see exact numbers

#### Posts By Status
- Pie chart showing distribution of post statuses
- Categories: Draft, Published, Scheduled
- Click segments to filter posts

#### Posts By Category
- Bar chart showing top 10 categories
- Shows post count per category
- Click bars to view category posts

### Dashboard Cards

- **Recent Posts**: Latest 5 posts with quick actions
- **Pending Comments**: Count of comments awaiting moderation
- **Scheduled Posts**: Upcoming scheduled publications
- **System Health**: Storage and queue status

## Resources

Resources are the core of Nova, representing your database models.

### Available Resources

#### Posts
Manage blog posts and articles.

**Fields:**
- Title (required)
- Slug (auto-generated)
- Excerpt
- Content (rich text editor)
- Featured Image
- Category (required)
- Tags
- Status (Draft, Published, Scheduled)
- Featured flag
- Trending flag
- Published At
- Scheduled At
- SEO metadata

**Relationships:**
- Author (User)
- Category
- Tags
- Comments
- Views
- Bookmarks

**Available Actions:**
- Publish Posts
- Feature Posts
- Export Posts

#### Users
Manage user accounts and roles.

**Fields:**
- Name (required)
- Email (required, unique)
- Password (creation only)
- Role (Admin, Editor, Author, User)
- Avatar
- Bio
- Status (Active, Inactive, Suspended)
- Email Verified At

**Relationships:**
- Posts
- Comments
- Media
- Bookmarks

#### Categories
Manage content categories.

**Fields:**
- Name (required)
- Slug (auto-generated)
- Description
- Parent Category (for hierarchy)
- Icon
- Color Code
- Status
- Display Order
- SEO metadata

**Relationships:**
- Parent Category
- Child Categories
- Posts

#### Tags
Manage content tags.

**Fields:**
- Name (required)
- Slug (auto-generated)

**Relationships:**
- Posts

#### Comments
Moderate user comments.

**Fields:**
- Post (link to post)
- User (if authenticated)
- Author Name (for guests)
- Author Email (for guests)
- Content
- Status (Pending, Approved, Spam)
- IP Address
- Created At

**Relationships:**
- Post
- User
- Parent Comment
- Replies

**Available Actions:**
- Approve Comments
- Mark as Spam
- Delete Comments

#### Media
Manage uploaded files.

**Fields:**
- Thumbnail preview
- File Name
- File Path
- File Type
- File Size
- MIME Type
- Alt Text
- Title
- Caption
- Uploaded By

**Relationships:**
- User (uploader)

#### Pages
Manage static pages.

**Fields:**
- Title (required)
- Slug (auto-generated)
- Content (rich text editor)
- Template
- Display Order
- Status (Draft, Published)
- SEO metadata

#### Newsletter
Manage newsletter subscribers.

**Fields:**
- Email (required, unique)
- Status (Active, Unsubscribed)
- Verified At
- Created At

#### Settings
Manage system settings (Admin only).

**Fields:**
- Key
- Value
- Group (General, Email, Social, SEO)

#### Activity Log
View system activity (Read-only).

**Fields:**
- Log Name
- Description
- Event
- Subject (related model)
- Causer (user who performed action)
- Properties (before/after values)
- IP Address
- User Agent
- Created At

## Search and Filters

### Global Search

Press `Cmd/Ctrl + K` to open global search:

1. Type your search query
2. Results appear from all resources
3. Click a result to view details
4. Use arrow keys to navigate
5. Press Enter to open selected result

**Searchable Fields:**
- Posts: Title, Excerpt, Content
- Users: Name, Email
- Categories: Name, Description
- Tags: Name
- Comments: Content, Author Name
- Media: File Name, Title, Alt Text

### Resource Search

Each resource has its own search:

1. Navigate to a resource (e.g., Posts)
2. Use the search box at the top
3. Results filter in real-time

### Filters

Filters help you narrow down results:

#### Post Filters
- **Status**: Draft, Published, Scheduled
- **Category**: Select specific category
- **Author**: Filter by author
- **Featured**: Show only featured posts
- **Date Range**: Custom date range

#### Comment Filters
- **Status**: Pending, Approved, Spam
- **Post**: Filter by specific post
- **Date Range**: Custom date range

#### User Filters
- **Role**: Admin, Editor, Author, User
- **Status**: Active, Inactive, Suspended

#### Media Filters
- **File Type**: Image, Document, Video
- **Upload Date**: Date range

### Using Filters

1. Navigate to a resource
2. Click "Filters" in the sidebar
3. Select filter options
4. Results update automatically
5. Clear filters with "Clear Filters" button

## Actions

Actions allow you to perform operations on multiple items at once.

### Using Actions

1. Navigate to a resource
2. Select items using checkboxes
3. Click "Actions" dropdown
4. Choose an action
5. Confirm if prompted
6. View success message

### Available Actions

#### Post Actions

**Publish Posts**
- Publishes selected draft posts
- Sets published_at timestamp
- Sends notifications
- Available to: Admin, Editor

**Feature Posts**
- Toggles featured flag
- Updates is_featured field
- Available to: Admin, Editor

**Export Posts**
- Exports selected posts to CSV
- Includes all fields and relationships
- Downloads automatically
- Available to: Admin, Editor

#### Comment Actions

**Approve Comments**
- Approves pending comments
- Changes status to "approved"
- Sends notifications to authors
- Available to: Admin, Editor

**Mark as Spam**
- Marks comments as spam
- Changes status to "spam"
- Available to: Admin, Editor

**Delete Comments**
- Permanently deletes comments
- Cannot be undone
- Available to: Admin

### Action Confirmation

Some actions require confirmation:

1. Select items
2. Choose action
3. Review confirmation dialog
4. Click "Confirm" or "Cancel"
5. View result message

## User Roles and Permissions

Nova uses role-based access control to determine what users can do.

### Available Roles

#### Admin
- **Full access** to all resources and features
- Can create, read, update, and delete everything
- Can manage users and assign roles
- Can access system settings
- Can view activity logs
- Can use all custom tools

#### Editor
- Can manage all content (posts, categories, tags, pages)
- Can moderate comments
- Can manage media library
- Can view users (read-only)
- Cannot manage users or settings
- Cannot access system tools

#### Author
- Can create and edit own posts
- Can upload media
- Can view categories and tags (read-only)
- Can view own comments
- Cannot moderate other users' content
- Cannot access admin features

#### User
- No Nova access
- Frontend access only

### Permission Matrix

| Resource | Admin | Editor | Author | User |
|----------|-------|--------|--------|------|
| Posts | CRUD | CRUD | CRU* | - |
| Users | CRUD | R | - | - |
| Categories | CRUD | CRUD | R | - |
| Tags | CRUD | CRUD | R | - |
| Comments | CRUD | CRUD | R | - |
| Media | CRUD | CRUD | CRU* | - |
| Pages | CRUD | CRUD | - | - |
| Settings | CRUD | R | - | - |
| Activity Logs | R | R | - | - |
| Newsletter | CRUD | R | - | - |

*Authors can only update/delete their own content

## Common Tasks

### Creating a New Post

1. Navigate to **Posts** in sidebar
2. Click **Create Post** button
3. Fill in required fields:
   - Title
   - Content
   - Category
4. Add optional fields:
   - Excerpt
   - Featured Image
   - Tags
   - SEO metadata
5. Choose status:
   - **Draft**: Save without publishing
   - **Published**: Publish immediately
   - **Scheduled**: Set future date
6. Click **Create Post**

### Editing a Post

1. Navigate to **Posts**
2. Find the post (use search/filters)
3. Click the post title
4. Edit fields as needed
5. Click **Update Post**

### Moderating Comments

1. Navigate to **Comments**
2. Filter by **Status: Pending**
3. Review each comment
4. Select comments to moderate
5. Choose action:
   - **Approve Comments**
   - **Mark as Spam**
   - **Delete Comments**
6. Confirm action

### Managing Users

1. Navigate to **Users**
2. Click **Create User** for new user
3. Fill in details:
   - Name
   - Email
   - Password
   - Role
4. Click **Create User**

To edit existing user:
1. Find user in list
2. Click user name
3. Edit fields
4. Click **Update User**

### Uploading Media

1. Navigate to **Media**
2. Click **Create Media**
3. Click **Choose File**
4. Select image/file
5. Add metadata:
   - Alt Text (recommended)
   - Title
   - Caption
6. Click **Create Media**

### Managing Categories

1. Navigate to **Categories**
2. Click **Create Category**
3. Fill in details:
   - Name
   - Description
   - Parent Category (optional)
   - Icon
   - Color
4. Click **Create Category**

### Scheduling Posts

1. Create or edit a post
2. Set **Status** to "Scheduled"
3. Set **Scheduled At** date/time
4. Click **Create/Update Post**
5. Post will publish automatically at scheduled time

### Bulk Publishing Posts

1. Navigate to **Posts**
2. Filter by **Status: Draft**
3. Select posts to publish
4. Click **Actions** → **Publish Posts**
5. Confirm action
6. Posts are published immediately

### Exporting Data

1. Navigate to desired resource
2. Select items to export
3. Click **Actions** → **Export**
4. CSV file downloads automatically

### Viewing Activity Logs

1. Navigate to **Activity Logs**
2. Use filters to narrow results:
   - User
   - Model Type
   - Action Type
   - Date Range
3. Click log entry to view details
4. See before/after values

### Managing Settings

1. Navigate to **Settings** (Admin only)
2. Find setting to modify
3. Click setting key
4. Update value
5. Click **Update Setting**
6. Cache clears automatically

## Tips and Best Practices

### Content Management

- **Use drafts** for work in progress
- **Schedule posts** for consistent publishing
- **Add SEO metadata** for better search rankings
- **Use featured images** for visual appeal
- **Tag appropriately** for better organization
- **Write good excerpts** for previews

### Media Management

- **Add alt text** for accessibility
- **Use descriptive filenames** before uploading
- **Organize with folders** (if available)
- **Delete unused media** to save space
- **Optimize images** before uploading

### User Management

- **Assign appropriate roles** based on responsibilities
- **Review user activity** regularly
- **Suspend inactive accounts** for security
- **Use strong passwords** always
- **Enable email verification** for new users

### Comment Moderation

- **Review pending comments** daily
- **Respond to user comments** when appropriate
- **Mark spam aggressively** to train filters
- **Delete abusive comments** immediately
- **Engage with community** through replies

### Performance

- **Use filters** instead of scrolling
- **Limit results** with pagination
- **Clear browser cache** if issues occur
- **Use keyboard shortcuts** for efficiency
- **Bookmark frequently used pages**

### Security

- **Log out** when finished
- **Don't share credentials** with others
- **Use strong passwords** (12+ characters)
- **Review activity logs** for suspicious activity
- **Report security issues** immediately

## Getting Help

### In-App Help

- Hover over field labels for tooltips
- Look for help text under fields
- Check validation messages for errors

### Documentation

- [Nova Installation Guide](nova-installation.md)
- [Nova Custom Actions](nova-custom-actions.md)
- [Nova Custom Tools](nova-custom-tools.md)
- [Nova Troubleshooting](nova-troubleshooting.md)

### Support

- Check [GitHub Issues](https://github.com/yourusername/technewshub/issues)
- Read [Laravel Nova Docs](https://nova.laravel.com/docs)
- Contact system administrator

## Conclusion

Nova provides a powerful, intuitive interface for managing TechNewsHub. This guide covers the basics, but there's much more to explore. Take time to familiarize yourself with the interface, and don't hesitate to experiment – most actions can be undone!

For advanced features and customization, consult the additional documentation or contact your system administrator.

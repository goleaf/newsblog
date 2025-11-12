# Admin Panel - Getting Started Guide

## Overview

The TechNewsHub admin panel provides a comprehensive interface for managing your content, users, and site settings. This guide will help you get started with the admin panel and understand its key features.

## Accessing the Admin Panel

### Login

1. Navigate to `/admin` or `/login`
2. Enter your admin credentials
3. You'll be redirected to the admin dashboard

### Default Admin Account

After running the AdminUserSeeder:
- **Email:** `admin@technewshub.com`
- **Password:** `password`

⚠️ **Important:** Change these credentials immediately after first login!

## Dashboard Overview

The admin dashboard provides a quick overview of your site's key metrics:

### Key Metrics
- **Total Posts:** Count of all posts (published, draft, scheduled)
- **Total Views:** Aggregate view count across all posts
- **Pending Comments:** Comments awaiting moderation
- **Active Users:** Total registered users

### Recent Activity
- Latest posts
- Recent comments
- Popular content
- Search analytics

### Quick Actions
- Create new post
- Moderate comments
- View analytics
- Manage users

## User Roles & Permissions

### Role Hierarchy

#### Admin
- Full access to all features
- User management
- System settings
- Content moderation
- Analytics access

#### Editor
- Create, edit, and publish all posts
- Moderate comments
- Manage categories and tags
- Access media library
- View analytics

#### Author
- Create and edit own posts
- Submit posts for review
- Upload media
- Reply to comments on own posts

### Managing User Roles

1. Navigate to **Users** in the admin menu
2. Click on a user to edit
3. Select the appropriate role from the dropdown
4. Save changes

## Content Management

### Creating a Post

1. **Navigate to Posts → Create New**
2. **Fill in required fields:**
   - Title (required)
   - Content (required)
   - Excerpt (optional, auto-generated if empty)
   - Category (required)
   - Tags (optional)
3. **Add featured image:**
   - Click "Upload Image"
   - Select image from your computer
   - Image will be automatically optimized
4. **Configure SEO:**
   - Meta title
   - Meta description
   - Keywords
5. **Set status:**
   - Draft: Save without publishing
   - Published: Make live immediately
   - Scheduled: Set future publication date
6. **Additional options:**
   - Featured post
   - Trending post
   - Allow comments
7. **Click Save or Publish**

### Editing a Post

1. Navigate to **Posts**
2. Click **Edit** on the post you want to modify
3. Make your changes
4. Click **Update**

### Post Status Workflow

```
Draft → Published
  ↓
Scheduled → Published (automatic)
  ↓
Archived
```

- **Draft:** Work in progress, not visible to public
- **Published:** Live on the site
- **Scheduled:** Will be published automatically at specified date/time
- **Archived:** Removed from public view but not deleted

### Bulk Actions

Select multiple posts and perform actions:
- Publish
- Unpublish
- Delete
- Change category
- Add tags

## Category Management

### Creating Categories

1. Navigate to **Categories → Create New**
2. Fill in details:
   - Name (required)
   - Slug (auto-generated)
   - Description
   - Parent category (for hierarchical structure)
   - Icon (optional)
   - Color (optional)
   - Display order
3. Configure SEO metadata
4. Click **Save**

### Category Hierarchy

Categories support parent-child relationships:

```
Technology
├── Programming
│   ├── PHP
│   └── JavaScript
└── Hardware
    ├── Laptops
    └── Smartphones
```

### Best Practices

- Keep category names concise
- Use clear, descriptive names
- Limit hierarchy to 2-3 levels
- Don't create too many categories (10-20 is ideal)

## Tag Management

### Creating Tags

1. Navigate to **Tags → Create New**
2. Enter tag name
3. Slug is auto-generated
4. Add description (optional)
5. Click **Save**

### Tag Best Practices

- Use lowercase for consistency
- Be specific but not too narrow
- Reuse existing tags when possible
- Aim for 3-5 tags per post
- Review and merge similar tags periodically

## Comment Moderation

### Viewing Comments

1. Navigate to **Comments**
2. Filter by status:
   - Pending
   - Approved
   - Spam
   - Trash

### Moderating Comments

#### Approve Comment
1. Click **Approve** button
2. Comment becomes visible on the site

#### Mark as Spam
1. Click **Spam** button
2. Comment is hidden and flagged
3. Helps train spam detection

#### Delete Comment
1. Click **Delete** button
2. Comment is soft-deleted (can be restored)

#### Reply to Comment
1. Click **Reply** button
2. Enter your response
3. Click **Submit**

### Spam Detection

The system automatically detects spam using:
- Link count validation
- Submission speed checking
- Blacklisted keywords
- Honeypot fields
- Rate limiting

Configure spam settings in **Settings → Comments**.

## Media Library

### Uploading Media

1. Navigate to **Media Library**
2. Click **Upload Files**
3. Select files from your computer
4. Files are automatically processed:
   - Images optimized
   - Multiple sizes generated
   - WebP versions created
   - EXIF data stripped

### Managing Media

#### Edit Media
- Update title
- Add alt text
- Add caption
- Add description

#### Delete Media
- Click **Delete**
- Confirm deletion
- ⚠️ Check if media is used in posts first

### Image Variants

Uploaded images automatically generate:
- **Thumbnail:** 150x150px
- **Medium:** 300x300px
- **Large:** 800x800px
- **Original:** Full size

### Supported Formats

- **Images:** JPEG, PNG, GIF, WebP
- **Documents:** PDF (planned)
- **Videos:** Embeds only (YouTube, Vimeo)

## Search Analytics

### Viewing Analytics

Navigate to **Analytics → Search** to view:

#### Top Queries
- Most searched terms
- Search frequency
- Time period filters

#### No-Result Queries
- Searches with zero results
- Opportunities for new content
- Potential spelling variations

#### Click-Through Rates
- Which results get clicked
- Position analysis
- Result relevance metrics

#### Performance Metrics
- Average search time
- Slow queries
- Cache hit rates

### Using Analytics

1. **Identify popular topics** from top queries
2. **Create content** for no-result queries
3. **Optimize titles** based on click-through rates
4. **Monitor performance** for slow queries

## Settings Management

### General Settings

- Site name
- Site description
- Contact email
- Posts per page
- Date format
- Time format

### SEO Settings

- Default meta title
- Default meta description
- Default keywords
- Social media links
- Google Analytics ID

### Email Settings

- SMTP configuration
- From address
- From name
- Email templates

### Comment Settings

- Enable/disable comments
- Require moderation
- Spam detection thresholds
- Blacklisted keywords

### Search Settings

- Enable fuzzy search
- Search threshold
- Results per page
- Highlight matches
- Cache duration

## User Management

### Creating Users

1. Navigate to **Users → Create New**
2. Fill in details:
   - Name
   - Email
   - Password
   - Role
3. Click **Create**
4. User receives welcome email

### Editing Users

1. Navigate to **Users**
2. Click **Edit** on user
3. Update details:
   - Name
   - Email
   - Role
   - Status (active/inactive)
   - Avatar
   - Bio
4. Click **Update**

### User Status

- **Active:** Can log in and perform actions
- **Inactive:** Cannot log in, content remains visible
- **Banned:** Cannot log in, content hidden

## Maintenance Mode

### Enabling Maintenance Mode

```bash
php artisan down --secret="your-secret-token"
```

Access site during maintenance:
```
https://yoursite.com/your-secret-token
```

### Disabling Maintenance Mode

```bash
php artisan up
```

### Use Cases

- Performing updates
- Database maintenance
- Major content changes
- Server migrations

## Best Practices

### Content Creation

1. **Write compelling titles** (50-60 characters)
2. **Use clear excerpts** (150-160 characters)
3. **Add featured images** (1200x630px recommended)
4. **Optimize for SEO** (meta tags, keywords)
5. **Use categories wisely** (1 per post)
6. **Add relevant tags** (3-5 per post)
7. **Proofread before publishing**
8. **Schedule posts** for consistent publishing

### Content Organization

1. **Plan category structure** before creating content
2. **Use consistent naming** for categories and tags
3. **Review and merge** duplicate tags regularly
4. **Archive old content** instead of deleting
5. **Use featured posts** to highlight important content

### User Management

1. **Assign appropriate roles** based on trust level
2. **Review user activity** regularly
3. **Remove inactive users** periodically
4. **Monitor for suspicious activity**

### Performance

1. **Optimize images** before uploading
2. **Clean up unused media** regularly
3. **Archive old search logs** monthly
4. **Monitor slow queries**
5. **Clear caches** after major changes

## Keyboard Shortcuts

### Global
- `Ctrl/Cmd + S` - Save current form
- `Ctrl/Cmd + K` - Focus search
- `Esc` - Close modal

### Post Editor
- `Ctrl/Cmd + B` - Bold
- `Ctrl/Cmd + I` - Italic
- `Ctrl/Cmd + K` - Insert link
- `Ctrl/Cmd + Shift + P` - Preview

## Troubleshooting

### Common Issues

**Issue: Can't upload images**
- Check file size (max 10MB)
- Verify file format (JPEG, PNG, GIF, WebP)
- Check storage permissions

**Issue: Posts not appearing**
- Verify status is "Published"
- Check published_at date is in the past
- Clear cache

**Issue: Search not working**
- Rebuild search index: `php artisan search:rebuild`
- Check fuzzy search is enabled in settings
- Verify database indexes exist

**Issue: Slow admin panel**
- Clear application cache
- Optimize database
- Check server resources

## Getting Help

- **Documentation:** [docs/](../../)
- **Issues:** [GitHub Issues](https://github.com/yourusername/technewshub/issues)
- **Community:** [GitHub Discussions](https://github.com/yourusername/technewshub/discussions)

---

**Last Updated:** November 12, 2025  
**Version:** 0.3.0-dev

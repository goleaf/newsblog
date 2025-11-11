## ADDED Requirements

### Requirement: Content Export Suite
Administrators MUST be able to export single or multiple posts as PDF (DomPDF/Browsershot), Markdown, HTML, and plain text with embedded/linked images and metadata. Export actions live in the admin post index and bulk toolbar, respect language variants, and queue background jobs for batch operations with progress indicators in the UI.

#### Scenario: Bulk export posts to PDF
- **GIVEN** an admin selects five posts in the index
- **WHEN** they choose “Export → PDF”
- **THEN** a queued job generates a zip containing individual PDFs with cover metadata, featured images inline, and delivers a notification with a download link once complete.

### Requirement: Content Import Pipelines
Admins MUST import legacy content from WordPress XML, Medium HTML, Markdown directories, and CSV batches via dedicated wizards or artisan commands (`php artisan import:wordpress`, `import:medium`, `import:markdown`, `import:csv`). Imports shall map categories/tags/authors, provide field mapping previews, handle media ingestion, validate CSV column mapping, and support dry-run validation before commit.

#### Scenario: Preview WordPress import
- **WHEN** an admin uploads a WordPress XML export and maps categories
- **THEN** the wizard shows a preview table (title, author, publish date, status, category matches, media issues), flags conflicts (duplicate slugs), and requires confirmation before the queued import runs.

### Requirement: Widget Management
The admin UI MUST expose a drag-and-drop widget manager for defined areas (Primary Sidebar, Footer 1-4). Widgets stored in `widgets` table (area, type, title, JSON settings, order, status) include recent posts, popular posts, categories, tag cloud, newsletter signup, search, custom HTML, social links, ads, author bio. Widgets support enable/disable, live preview, configurable settings forms per widget type, and ordering.

#### Scenario: Configure sidebar widget order
- **WHEN** an editor drags “Newsletter Signup” above “Popular Posts” in Primary Sidebar and saves
- **THEN** the order persists in the database, the preview updates instantly, and front-end rendering reflects the new sequence without cache staleness.

### Requirement: Menu Builder
The system MUST provide a drag-and-drop menu builder supporting locations (Header, Footer, Mobile) with nested menu items (custom links, pages, categories, tags), open-in-new-tab option, icon selection, custom CSS classes, and unlimited depth hierarchies. Persist structure in `menus` and `menu_items` tables with ordering and parent references.

#### Scenario: Create nested mobile menu
- **WHEN** an admin creates a Mobile menu adding “Topics” with child items for three categories
- **THEN** the menu tree saves with proper parent-child relationships, the preview displays nested indentation, and publishing it updates the live mobile navigation component.

### Requirement: Editorial Content Calendar
The admin experience MUST include a calendar view (monthly/weekly/daily) showing scheduled posts by status with drag-and-drop rescheduling, filters by author/category, quick edit modal, publishing frequency stats, content gaps visualization, and export to iCal. Utilize FullCalendar.js via bundled assets, not CDN.

#### Scenario: Drag post to new date
- **WHEN** an editor drags a scheduled post from Friday to Monday in the monthly view
- **THEN** the post’s publish_at updates, notifications fire to assigned author, and the calendar refreshes to show the new slot while logging the schedule change in the activity log.

### Requirement: In-App Notification Center
Admins and authors MUST have an in-app notification bell summarizing unread notifications (new comments, replies, post published/scheduled, new user registration). Notifications live in `notifications` table (user_id, type, JSON payload, read_at). UI supports dropdown, mark read/unread, mark all read, delete, settings for opt-in types, and real-time delivery via Laravel Echo + Pusher (or polling every 30 seconds when websockets unavailable).

#### Scenario: Receive comment reply notification
- **WHEN** a comment reply is posted on an author’s article
- **THEN** the author receives a bell badge increment, dropdown entry with commenter name, excerpt, and link; clicking marks it read and navigates to the threaded comment.

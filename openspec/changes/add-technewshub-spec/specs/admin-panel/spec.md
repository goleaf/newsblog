## ADDED Requirements

### Requirement: Admin Layout & Dashboard
The admin area MUST provide a Tailwind-based layout with responsive sidebar navigation (per hierarchy in Prompt 3), top header (search, notifications, user menu, dark/light toggle), breadcrumbs, and quick actions. The dashboard must surface KPI cards (posts, views by period, comments w/ pending count, subscribers, new users), charts (posts over 30 days, category popularity), tables (top viewed posts), recent comments moderation widget, traffic sources, and shortcut buttons (new post, pending comments, categories, settings).

#### Scenario: Render dashboard widgets
- **WHEN** an admin visits `/admin`
- **THEN** the layout displays sidebar + header with active menu states, KPI cards with delta percentages, charts populated from analytics data, a top-10 posts table, recent comments list with approve/delete buttons, and quick action buttons aligned to spec.

### Requirement: Posts Management Suite
Admins MUST access `/admin/posts` containing a DataTables-powered grid with thumbnail, title, author, category, tags, status badge (color-coded), views, comments, published date, search and filters (status, category, author, date range), bulk actions (delete, change status, change category), pagination size selector, and row quick actions (edit/view/duplicate/trash). Create/edit screens require rich text editor (TinyMCE/CKEditor) with media picker, excerpt auto-gen, word count, slug preview, SEO box, post settings (featured/trending/allow comments/reading time), author assignment, tags UI, category hierarchy, featured image manager, publish box (status/visibility/scheduling/save draft/preview/publish). Autosave every 60s with last-saved indicator and restore-on-crash is mandatory.

#### Scenario: Autosave draft
- **GIVEN** an editor composing a new post
- **WHEN** 60 seconds elapse without manual save
- **THEN** the system autosaves as draft, updates the “Last saved” timestamp, and if the browser reloads unexpectedly the draft content is restored from autosave storage.

### Requirement: Category & Tag Administration
`/admin/categories` MUST show a tree view with drag-and-drop reordering, columns for icon, name, slug, parent, post count, status, actions, inline editing, search, and bulk actions (delete/activate/deactivate). Category modal must support name, slug, parent select, description, icon/color pickers, display order, status toggle, SEO meta fields, and dual save buttons. The system must block deletion when posts exist until reassigned, support merge operation, and provide quick post views per category. `/admin/tags` requires table view with name, slug, post count, created date, actions, search/filter/sort, inline create/edit with auto slugs, bulk delete/merge, unused-tag finder, and tag cloud visualization.

#### Scenario: Merge categories
- **WHEN** an admin selects two categories and chooses “Merge”
- **THEN** the UI prompts for a destination category, moves associated posts to that target, updates hierarchy/order, and removes the old categories without orphaning posts.

### Requirement: Media Library Management
`/admin/media` MUST include grid (default) and list modes, drag-and-drop uploader with multi-file queue, progress bars, 10MB limit (configurable) and file type validation. Filters include media type, date ranges (7/30/custom), uploader, text search, unused-media toggle. Grid cards show thumbnail/icon, truncated filename, size, upload date, with hover actions (view/edit/delete/copy URL). Details modal displays preview, metadata (type/size/dimensions/uploader/date), editable filename/alt/title/caption, delete confirmation, replace file, basic edits (crop/resize/rotate), thumbnail generation (thumb/medium/large), bulk delete/download, gallery selection for post insertion, and storage usage stats.

#### Scenario: Edit media metadata
- **WHEN** an editor opens a media item and updates the alt text + caption
- **THEN** saving persists the metadata, regenerates derived thumbnails if needed, and subsequent insertions into posts use the updated alt/caption values.

### Requirement: Comments Moderation Console
`/admin/comments` MUST provide tabs (All, Pending with badge, Approved, Spam, Trash) each showing a table with checkbox, avatar, author name (email on hover), excerpt (expandable), post reference link, submitted timestamp, status badge, and row action dropdown. Filters include search (author/email/content), date range, post filter, comment type (regular/reply). Bulk actions support approve, spam, trash, delete. Inline expand reveals full text, author details (IP/user agent), parent comment, quick actions (approve/unapprove, admin reply, edit, spam, trash, view on site). Inline editing and ability to move comment between posts are required. Basic spam detection must flag duplicates, multiple links, blacklisted words, suspicious patterns, with optional Akismet integration. Notifications should alert admins of new comments and highlight pending counts on sidebar.

#### Scenario: Approve pending comment inline
- **WHEN** a moderator expands a pending comment row and clicks Approve
- **THEN** the comment status updates instantly, the row badge switches to Approved, counts refresh, and the comment is removed from the Pending tab list without a page reload.

### Requirement: User Management & Profiles
`/admin/users` MUST show avatar, name, email, role badge, post count, status badge, registered date, last login, and actions. Filters cover role, status, search, registration date range. Bulk actions allow delete, change role, activate/deactivate. Create/edit forms include avatar upload, name, email, password/confirmation, bio, role selection, status toggle, email verification, and social links (Twitter, LinkedIn, GitHub, Website). Role permissions must match Prompt 8 definitions. Admins can impersonate users, send welcome emails, reset passwords, and view user-specific activity logs with stats. Each admin can edit their own profile, change password, and review activity timeline.

#### Scenario: Change author role
- **WHEN** an admin selects multiple authors and chooses “Promote to Editor”
- **THEN** their roles change, they gain editor privileges (posts/comments/categories access) but still lack user/settings management, and audit logs capture the role change plus actor.

### Requirement: Pages & Contact Management
`/admin/pages` MUST list title, slug, template, status, order, updated date, actions, support drag-and-drop ordering, inline quick edit, and bulk delete. Page editor mirrors the post editor minus advanced post settings, but includes page attributes (parent page, template selection: default/full-width/contact/about/custom, order), featured image optional, SEO meta fields, and publish box. Default pages (About, Contact, Privacy, Terms, FAQ) must be seeded. Contact template includes a form storing submissions in `contact_messages`; admins can view and respond.

#### Scenario: Create contact page
- **WHEN** an admin selects the contact template and publishes the Contact page
- **THEN** the frontend renders the contact form with Name/Email/Subject/Message validation, submissions persist to `contact_messages`, and admins can review them in `/admin/contact-messages`.

### Requirement: Newsletter & Subscriber Tools
`/admin/newsletters` MUST list email, status badge, verification flag, subscription date, source, actions. Filters allow status, verified flag, daterange, search. Bulk actions include export CSV, delete, future send mass email placeholder. Manual add form supports email input, auto-verify option, source note. Subscriber detail view shows metadata, last activity, verification state, unsubscribe history, and actions (edit, delete, send test email). Export respects filters and outputs CSV columns email/status/verified/date. Double opt-in flows must be manageable from the admin, including resend verification links.

#### Scenario: Export filtered subscribers
- **WHEN** a manager filters subscribed + verified users within a date range and clicks Export
- **THEN** a CSV downloads containing only matching records with headers Email, Status, Verified, Date, Source.

### Requirement: Settings Console
Settings MUST be grouped under General, SEO, Social, Email, Comments, Media, Reading, Appearance (Prompt 11). Each section exposes the detailed fields (site name/logo/favicon/timezone/date format/posts per page, SEO defaults + analytics IDs, social URLs/OG defaults, SMTP config/test email, comment moderation preferences, media limits/thumbnails, homepage selection, dark mode + color pickers + breadcrumbs, etc.). Each section saves independently with validation, success/failure toasts, and cached values for performance.

#### Scenario: Update email settings
- **WHEN** an admin edits SMTP host/port/credentials and uses “Send test email”
- **THEN** inputs validate, values persist to the settings table, cache invalidates, and the test email result is surfaced to the user via toast notification.

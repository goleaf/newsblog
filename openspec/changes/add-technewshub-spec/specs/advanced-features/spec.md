## ADDED Requirements

### Requirement: Bookmarks & Reactions
Logged-in users MUST be able to bookmark posts (“Save for later”) toggled via icon; bookmarks persist in a `bookmarks` table (user_id, post_id, timestamps) and surface in a profile “Reading List” page showing saved posts, with removal controls and counts. Post reactions (👍 ❤️ 😂 😮 😢 😡) MUST store per-user (or anonymous session with throttling) entries in `reactions` table, display aggregate counts, and animate reaction picker. Permissions should prevent duplicate reactions per user per post and allow updates.

#### Scenario: Bookmark a post
- **WHEN** an authenticated user clicks the bookmark icon on a post
- **THEN** the button toggles to filled, the record stores in `bookmarks`, the count updates, and the post appears in the user’s Reading List page.

### Requirement: Related Content & Recommendations
Related posts MUST weigh shared category (40%), shared tags (40%), similar publish date (10%), and author (10%) to surface 4–6 items, cached per post. Personalized “Recommended for you” sections should consider user reading history, bookmarks, and comments; if not logged in, fall back to global trending. Reading-progress sync MUST remember last scroll location per user/post and offer “Continue reading” lists synced across devices.

#### Scenario: Display related posts
- **WHEN** a reader reaches the end of an article
- **THEN** the related block shows up to 4 posts computed via the weighted algorithm, excluding the current post and cached for faster rendering.

### Requirement: Author Experience & Spotlighting
Author profile pages MUST support rich-text bios, social links, expertise tags, total view counts, average reading time, follower counts (if enabled), and “Contact author” channels. Featured authors (badge, author of the month, sidebar widget) highlight top contributors based on post counts/views/engagement; admins can curate or auto-select. Provide ability to follow authors (optional) to enhance recommendations.

#### Scenario: Feature an author of the month
- **WHEN** an admin designates an author as featured
- **THEN** their profile shows a badge, they appear in “Featured Authors” sidebar/widget, and related API/frontend queries flag them accordingly.

### Requirement: Content Navigation & Enhancements
TechNewsHub MUST implement breadcrumbs site-wide (except homepage) with schema markup, responsive collapse on mobile. Auto-generate table of contents for long posts by parsing H2/H3 headings, with sticky sidebar display, smooth scroll anchors, section highlighting, and collapse toggle. Reading time calculation MUST consider words/200 wpm + 12s per image + 20s per code block. Optional content warnings allow admins to flag sensitive posts, overlaying a blur with opt-in button storing session preference. Provide post templates (tutorial/review/list/interview/how-to) pre-populating editor structures with fields like steps, pros/cons, numbered lists, Q&A blocks.

#### Scenario: Show content warning
- **WHEN** a post is marked with a warning message
- **THEN** the frontend displays the warning overlay with summary text and “View content” button that removes the blur for the session and logs acknowledgement.

### Requirement: Draft Previews & Scheduling Enhancements
Editors MUST generate secure preview URLs for drafts with random tokens (optionally password-protected) expiring after 7 days, tracking view counts. Scheduling MUST allow publish, update, and unpublish times with timezone awareness and optional recurrence (e.g., weekly columns). Scheduled jobs MUST honor these timestamps and notify authors when actions execute.

#### Scenario: Share draft preview
- **WHEN** an editor clicks “Generate preview link” for an unpublished post
- **THEN** the system creates a tokenized URL valid for 7 days (password optional), logs preview views, and allows revocation at any time.

### Requirement: Versioning, Guest Posts, and Templates
Posts MUST maintain revision history via `post_revisions` (post_id, user_id, title, content, created_at). Editors can view diffs, restore prior versions, and track who made changes. Guest posting form allows external contributors to submit posts with name/email/bio, storing drafts for admin review/approval; published guest posts display credit. Template system (tutorial/review/list/interview/how-to) should be selectable when creating posts, seeding structured blocks and metadata for consistent formatting.

#### Scenario: Restore revision
- **WHEN** an editor views the revision history and selects an earlier version
- **THEN** the content preview shows diff, and choosing Restore updates the post body/title while logging the action as a new revision.

### Requirement: Series, Featured Content, and Social Proof
TechNewsHub MUST allow creation of `series` (name, slug, description) and `post_series` pivot with ordering; posts display series navigation/progress and series landing pages aggregate entries. Provide post series progress indicator. Social proof elements include “Trending now,” “X people are reading this,” view counters refreshed in near real-time, and badges for “Most shared this week.” Popularity data should feed widgets. Print stylesheet must strip navigation/sidebar/comments, enforce white background, black text, display URLs inline, provide page breaks, and support a “Print article” control.

#### Scenario: Navigate series posts
- **WHEN** a reader views a post belonging to a series
- **THEN** the page shows series title, current position (e.g., 2 of 5), previous/next within the series, and links to the series landing page.

### Requirement: Print Stylesheet
TechNewsHub MUST provide a dedicated print stylesheet (`resources/css/print.css`) that hides non-essential chrome (header/footer/sidebar/comments/share buttons/navigation/ads), forces black text on white, adjusts typography for readability, appends URLs to hyperlinked text, avoids page breaks inside images/headings, includes logo/post metadata, optionally renders a QR code linking to the article, and prints the current date + URL at the bottom.

#### Scenario: Print-ready article
- **WHEN** a reader prints a post or clicks the “Print article” control
- **THEN** the browser loads `print.css`, removes navigation/comments, renders the logo/metadata, copies URLs inline, adds page breaks before major headings, and shows the printed date/URL footer.

### Requirement: Export Content
TechNewsHub MUST allow exporting individual or batches of posts as PDF (using DomPDF/Browsershot), Markdown, HTML, or plain text while optionally embedding featured media, flagging the process via admin export buttons, and packaging bulk exports (zip files) for download.

#### Scenario: Export multiple posts
- **WHEN** an admin selects multiple posts, chooses “Export,” and requests PDF + Markdown
- **THEN** the system generates the requested formats (PDF with embedded images, Markdown files), bundles them into a ZIP, and triggers the download with success notification.

### Requirement: Import Content
TechNewsHub MUST support imports from WordPress XML, Medium HTML folders, Markdown directories, and CSV batches, offering field mapping, previews, per-file validation, and artisan commands (`import:wordpress`, `import:medium`, `import:markdown`) to process uploads safely.

#### Scenario: Preview WordPress import
- **WHEN** a user runs `php artisan import:wordpress /tmp/export.xml`
- **THEN** the command parses the XML, displays a preview mapping authors/categories, reports conflicts, and upon confirmation imports posts/media accordingly.

### Requirement: Advanced Search
TechNewsHub MUST power advanced search with Laravel Scout or an external engine (Meilisearch/Algolia) to filter by date/author/category/tag, sort by relevance/date/popularity, search within results, save/recall queries for logged users, track recent searches, and highlight matched keywords in snippets.

#### Scenario: Save a filtered search
- **WHEN** a signed-in user filters “Security” posts by this month, sorts by popularity, and saves the query
- **THEN** the search is listed in their “Recent searches,” returning highlighted results when rerun.

### Requirement: Widgets System
TechNewsHub MUST offer widget areas (Primary Sidebar, Footer 1-4) managed via drag-and-drop, stored in a `widgets` table (area, type, settings JSON, order, status), and include built-in widgets (recent/popular posts, categories, tags, newsletter, search, custom HTML, social links, ads, author bio) with live previews and enable/disable toggles.

#### Scenario: Configure footer widgets
- **WHEN** an admin places the Newsletter + Popular posts widgets into Footer 2 and saves
- **THEN** the public footer renders them with the configured titles/limits, and disabling a widget immediately removes it from the frontend without redeploying.

### Requirement: Menu Builder
TechNewsHub MUST provide a drag-and-drop menu builder that persists menus/menu_items with location (Header/Footer/Mobile), nested hierarchy, custom links/pages/categories/tags, CSS classes, icons, and a “open in new tab” flag.

#### Scenario: Build nested menu
- **WHEN** an editor creates a Header menu with nested category links, assigns icons, and saves
- **THEN** the navigation renders those links with proper nesting, icons, and target attributes across breakpoints.

### Requirement: Maintenance Mode
TechNewsHub MUST support branded maintenance mode with artisan toggles (`php artisan down --secret=...`/`up`), custom view (logo, ETA, progress), status indicator, secret token bypass, and IP whitelist for testing.

#### Scenario: Activate maintenance mode
- **WHEN** the ops lead triggers `php artisan down --secret="token"` and whitelists their IP
- **THEN** general visitors see the custom maintenance page while authorized admins bypass via the secret token and see the indicator.

### Requirement: Activity Log
TechNewsHub MUST log admin create/update/delete actions for posts/categories/users/settings with IP/user agent, searchable UI, filters by actor/action/date, and exportable CSV via `activity_logs`.

#### Scenario: Review activity history
- **WHEN** an admin filters the log for “Updated Post” within the past week
- **THEN** the filtered entries show descriptions/IPs, and the export button produces the same dataset.

### Requirement: Notification System
TechNewsHub MUST deliver in-app notifications (new comment/reply, new registration, post published/scheduled) via a header bell, dropdown, read/unread states, mark-all-read button, deletion, and optional real-time updates (Echo/Pusher or polling) with counts derived from a `notifications` table.

#### Scenario: Notify author of scheduled post
- **WHEN** an author schedules a post
- **THEN** they receive a notification in the bell dropdown; marking it read removes the badge count and persists the read flag.

### Requirement: Two-Factor Authentication
TechNewsHub MUST allow administrators to enable 2FA (Fortify/Pragmarx) with QR codes, backup/recovery codes, remember-device cookies (30 days), verify codes on login, and optional recovery flows.

#### Scenario: Set up 2FA
- **WHEN** an admin scans the QR in their profile and saves backup codes
- **THEN** subsequent logins require the TOTP unless the trusted device cookie (30d) is present, and recovery codes unlock the account when needed.

### Requirement: API Rate Limiting Dashboard
TechNewsHub MUST offer dashboard insights on API calls per endpoint, rate-limit violations, top consumers, response times, error rates, abusive IP controls, limit adjustments, and optional API key administration.

#### Scenario: Investigate API abuse
- **WHEN** admins spot repeated 429s for `/api/v1/posts`
- **THEN** they use the dashboard to block the offending IP, adjust the limit for trusted clients, and monitor the response time graph.

### Requirement: GDPR Compliance
TechNewsHub MUST comply with GDPR: cookie consent, privacy policy generator, user data export (JSON/CSV), deletion workflow with admin review, consent logs, and audit trails for access.

#### Scenario: Handle deletion request
- **WHEN** a user requests account deletion
- **THEN** the admin reviews, approves anonymization, logs the decision, and exports the retained data for legal retention.

### Requirement: Performance Dashboard
TechNewsHub MUST show metrics for page load times, query durations, cache hit/miss ratio, memory usage, slow queries log, error rate, and uptime, with admin widgets, historical charts, alerts, and recommendations.

#### Scenario: Slow query alert
- **WHEN** slow queries spike and cache hits drop
- **THEN** the dashboard emits an alert detailing offending queries, allowing ops to investigate.

### Requirement: A/B Testing
TechNewsHub MUST support A/B experiments on titles/featured images/CTAs with 50/50 splits, variant tracking, analytic comparisons, and admin interface to declare winners once statistically significant.

#### Scenario: Run CTA test
- **WHEN** marketing launches two CTA variants on a post
- **THEN** visitors split evenly, each variant logs clicks/views, and the admin selects a winner based on engagement data.

### Requirement: Content Calendar
TechNewsHub MUST provide a FullCalendar-based editorial calendar that visualizes scheduled posts, supports drag/drop rescheduling, filters (author/category), color-coded statuses, quick edits, and iCal export.

#### Scenario: Reschedule from calendar
- **WHEN** an editor drags a post to a new date on the calendar
- **THEN** the scheduled_at updates, the calendar shows the new slot, and subscribers see the updated publication timeline.

### Requirement: Internal Linking Suggestions
TechNewsHub MUST scan content similarity, suggest internal links during editing, allow one-click insertion, track usage, and report orphaned posts lacking inbound links.

#### Scenario: Insert suggested link
- **WHEN** an author writes about “Laravel Scout”
- **THEN** suggestions show relevant posts, insertion happens with one click, and the system records the new internal link.

### Requirement: Broken Link Checker
TechNewsHub MUST run `php artisan links:check`, scan internal/external URLs, list broken links per post with last checked timestamps, and offer ignore/fix actions via admin UI.

#### Scenario: Fix broken URL
- **WHEN** the checker reports an external 404
- **THEN** admins see the post/link, choose to replace/ignore it, and the next run confirms the fix.

### Requirement: Image Alt Text Checker
TechNewsHub MUST audit all media for missing alt text, surface the results with AI-suggested defaults (optional), allow bulk editing, and offer best-practice tips in the admin.

#### Scenario: Bulk alt text update
- **WHEN** an audit finds ten images without alt text
- **THEN** the admin applies the suggested descriptions in bulk and the checklist marks those images as compliant.

### Requirement: Spam Protection
TechNewsHub MUST employ honeypots, timing heuristics, reCAPTCHA v3, optional Akismet, IP/keyword blacklists, rate limiting, sensitivity settings, whitelists, and spam logs for reviewing blocked submissions.

#### Scenario: Prevent bot comment
- **WHEN** a bot triggers the honeypot + rate limit
- **THEN** the submission is blocked, logged, and the admin can ban the IP or adjust sensitivity.

### Requirement: Social Media Auto-Post
TechNewsHub MUST queue automated image-backed posts to Twitter/X, Facebook, and LinkedIn according to templates, handle API credentials, retry failures, and log delivery outcomes.

#### Scenario: Auto-post a launch article
- **WHEN** a post publishes
- **THEN** queued jobs push updates to configured platforms, log responses, and surface success/failure in the admin.

### Requirement: Email Digest
TechNewsHub MUST send responsive daily/weekly/monthly digests showcasing new/popular/trending posts tailored to user interests, include unsubscribe links, track opens/clicks, and respect subscription preferences.

#### Scenario: Weekly digest dispatch
- **WHEN** the weekly digest job runs
- **THEN** subscribers receive a responsive email with thumbnails/excerpts, tracked CTAs, and any unsubscribes are processed automatically.

### Requirement: Final Polish
TechNewsHub MUST strip `dd()`/dumps, remove commented code, follow consistent formatting, maintain PHPDoc, update README, document installation/config/admin/API/troubleshooting, test forms/errors/emails/responsiveness/accessibility, validate SEO/performance/security, and execute the pre-launch checklist (backup, maintenance, caching, monitoring, analytics, SSL, etc.).

#### Scenario: Pre-launch QA
- **WHEN** the final checklist runs
- **THEN** every box (tests, docs, optimization, security, monitoring) passes, the site is in maintenance mode, migrations/caches are handled, the team deploys, and the launch is celebrated.

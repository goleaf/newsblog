## ADDED Requirements

### Requirement: Caching & Query Optimization
Platform MUST use Redis (or fallback file cache) with tagged caches: `posts`, `categories`, `post:{id}` etc. Cache durations—homepage 10m, category 15m, single post 30m, popular posts 1h, categories list 1d, settings 1d. Eager loading is required to avoid N+1 queries; fragment caching for navigation, sidebar widgets, footer, and popular posts. Cache invalidation MUST trigger when posts/categories update or new content publishes. Scheduled jobs should refresh popular rankings hourly.

#### Scenario: Publish clears caches
- **WHEN** a post transitions from draft to published
- **THEN** caches tagged `posts` (homepage, post, popular) clear automatically so new content appears immediately while unrelated caches persist.

### Requirement: Media & Image Optimization
Upload pipeline MUST auto-resize images, generate thumbnail/medium/large variants, convert to WebP with original fallback, compress while preserving quality, strip metadata, and store metadata in `media_library`. Serve responsive images via `srcset`/`picture`, lazy load by default, allow optional CDN base URL. Media settings page controls max upload size (default 10MB) and allowed types (JPG, PNG, GIF, WebP, PDF, DOC/DOCX).

#### Scenario: Upload hero image
- **WHEN** an editor uploads a >3MB JPG
- **THEN** the system optimizes it, saves multiple sizes plus WebP, stores metadata (dimensions, size, mime), and frontend posts reference the responsive sources.

### Requirement: SEO & Metadata Enhancements
TechNewsHub MUST generate XML sitemap (posts, categories, pages) daily and ping search engines; configurable robots.txt with sitemap link; dynamic meta titles/descriptions/keywords per post/page, plus defaults from settings. Provide Open Graph & Twitter Card tags, canonical URLs, schema.org markup (Article, Organization, Author, BreadcrumbList, WebSite). Support optional alternate language tags if multilingual is enabled. Permalinks MUST be clean slug-based and support 301 redirects for slug changes.

#### Scenario: Update SEO defaults
- **WHEN** SEO settings include default meta + GA/GTM IDs and sitemap job runs nightly
- **THEN** pages emit updated meta tags, `sitemap.xml` lists the latest URLs, and robots.txt references the sitemap + obeys allow/deny rules.

### Requirement: Performance Optimization
TechNewsHub MUST implement DB indexes on slugs, foreign keys, statuses, `published_at`, composites for common queries. Assets built via Vite should be minified, versioned, with non-critical JS deferred. Support HTTP/2 (server config) and optionally server push for critical assets. Use lazy loading for images/comments/related posts, infinite scroll for lists, and schedule database maintenance tasks (optimize tables, prune stale caches). Monitor slow queries (>100ms) during development.

#### Scenario: Warm cache and checklist
- **WHEN** `npm run build` executes for production
- **THEN** CSS/JS bundles are minified with hashed filenames, and HTML templates load them via `@vite` ensuring browsers cache effectively.

### Requirement: Security Hardening
TechNewsHub MUST enforce CSRF tokens on forms, escape output to prevent XSS, use Eloquent/parameterized queries, and validate uploads (type/size, store outside public). Implement login (5/min), comment (3/min), contact (2/min), and API rate limits per Prompt 17/16. Configure secure session cookies (HTTP-only, SameSite, secure), strong password rules, password confirmations for sensitive actions, and hashed storage (bcrypt). Set headers: `X-Frame-Options=DENY`, `X-Content-Type-Options=nosniff`, `X-XSS-Protection=1; mode=block`, `Strict-Transport-Security`, and CSP. Optionally integrate malware scanning for uploads.

#### Scenario: Block excessive comment submissions
- **WHEN** a user tries to submit >3 comments within a minute
- **THEN** the comment endpoint returns a rate-limit error using the standard JSON envelope, protecting the system from spam bursts.

### Requirement: Analytics & Tracking
Track post views (unique per session) via `post_views`, aggregate for dashboards, and maintain popular-post rankings hourly. Capture user actions (login, CRUD) in activity logs. Dashboard MUST expose views over time, popular posts/categories, traffic sources (if GA/analytics IDs configured), and engagement metrics. Provide export endpoints/UI for views data and activity logs with date filters and CSV output.

#### Scenario: Export view analytics
- **WHEN** an admin requests views between two dates
- **THEN** the system compiles CSV with date, post, and count data drawn from cached analytics, ready for download.

### Requirement: Backup & Maintenance Automation
Schedule artisan commands for daily DB backup (2 AM), weekly log cleanup, sitemap generation daily, hourly stale cache pruning, hourly popular posts refresh, newsletter sending daily, unused media cleanup, spam comment purge, old draft deletion. Backups MUST copy SQLite DB, optionally push to S3, and enforce a 30-day retention policy. Provide commands for table optimization and log rotation.

#### Scenario: Rotate backups
- **WHEN** the backup command runs nightly for 40 days
- **THEN** only the most recent 30 backup files remain locally/S3, older ones delete automatically per retention rules.

### Requirement: Multilingual, PWA, and Email Notifications
TechNewsHub MUST support optional localization (language selector, session/cookie storage, translation files, RTL styles, translated content fields). Provide optional PWA manifest + service worker for offline caching, add-to-home prompts, offline page, and push notification hooks. Email notifications (queued) MUST include templates for welcome, verification, password reset, new comment to author, comment reply, newsletter subscribe/unsubscribe confirmations, contact submission to admin, weekly digest, etc. Queue workers (Supervisor) must process emails asynchronously with configurable preferences per user.

#### Scenario: Queue welcome email
- **WHEN** a new user registers and opts into notifications
- **THEN** a queued welcome email is dispatched using the template, processed by the worker without blocking the HTTP response, and respects the user’s notification preferences.

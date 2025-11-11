## ADDED Requirements

### Requirement: Maintenance Mode Experience
The platform MUST support enabling maintenance mode with branded Tailwind template, estimated downtime, contact links, and optional secret token or IP whitelist granting admin access. Status indicator should display in admin header when maintenance is active.

#### Scenario: Activate maintenance
- **WHEN** an operator runs `php artisan down --secret="token"` and visits the site
- **THEN** non-whitelisted visitors see the custom maintenance page with countdown, social links, and contact info, while admins using the secret token bypass the page and see an in-app banner noting maintenance mode.

### Requirement: Activity Logging
All admin actions (create/update/delete posts, categories, settings, etc.) MUST log actor, action, model, metadata, IP, user agent into `activity_logs` table, searchable/filterable in admin with detail view and export capability.

#### Scenario: Review post update log
- **WHEN** an admin edits a post title
- **THEN** an activity entry records the admin, timestamp, model `Post`, before/after summary, IP, user agent, and the log appears in the activity list filterable by user and action type.

### Requirement: GDPR & Privacy Toolkit
The platform MUST provide a cookie consent banner, privacy policy generator template, data export (JSON/CSV) for user data (profile, posts, comments, activity logs), data deletion workflow with admin approval, consent history tracking, and audit trail of personal data access. Ensure legal pages accessible and localization-ready.

#### Scenario: Process data deletion request
- **WHEN** a user submits a deletion request
- **THEN** the system queues the request for admin review, upon approval anonymizes or deletes personal data, logs the action, sends confirmation email, and retains backup reference for legal compliance.

### Requirement: Performance Dashboard
Admin dashboard MUST surface page load metrics, database query times, cache hit/miss, memory usage, slow queries, error rates, uptime, with charts (real-time and historical) and alert thresholds for degradation notifications. The dashboard should present automated optimization recommendations (e.g., enable caching, compress images) based on observed metrics.

#### Scenario: Monitor cache hit drop
- **WHEN** cache hit ratio falls below configured threshold
- **THEN** the dashboard highlights the metric in red, logs the event, and optionally triggers an in-app notification or email alert.

### Requirement: Broken Link Monitoring
A scheduled command (`php artisan links:check`) MUST crawl posts for internal/external links, flagging broken ones with status codes, affected posts, last checked date, and remediation options (fix, ignore). Results displayed in admin report with export.

#### Scenario: Detect broken external link
- **WHEN** the nightly job finds a 404 link in a post
- **THEN** the broken link report lists the post, URL, failure status, first detected date, and provides quick actions to edit or mark ignored, while recording the check in history.

### Requirement: Final Polish Checklist
Codebase MUST be free of `dd()/dump()` calls, stray comments, inconsistent formatting; include PHPDoc where relevant; update README with install/config/admin guides; ensure success/error messaging localized; run accessibility, responsiveness, SEO, security, and performance validations per pre-launch checklist before go-live. Checklist shall cover sitemap generation/submission, Google Search Console & Analytics setup, meta tag verification, page speed audits, dependency/security updates, permission checks, authentication/CSRF/rate-limiting tests, caching, backups, monitoring, legal pages, favicon, social previews, email/form verification, and document a launch runbook (backup, enable maintenance, deploy, migrate, clear caches, smoke test, disable maintenance, monitor, announce, retrospective).

#### Scenario: Pre-launch verification
- **WHEN** the team prepares for launch
- **THEN** they follow the pre-launch checklist verifying forms, emails, analytics, caching, backups, SSL, error monitoring, legal pages, ensuring all automated tests pass and documentation is current.

## ADDED Requirements

### Requirement: Automated Testing Stack
Project MUST configure PHPUnit (phpunit.xml), in-memory SQLite for tests, factories + seeders covering all models, and Laravel test helpers. Feature tests must cover authentication flows, post lifecycle (create/update/delete/publish/schedule/slug/view count/featured flag), categories (hierarchy, deletion protection), comments (submission/approval/reply/spam), API endpoints (public/auth/admin, rate limits, pagination, error formats), search, newsletter double opt-in. Unit tests must exercise model relationships, scopes, accessors/mutators, helper functions (slugging, reading time, excerpts), and services (image, email, cache, SEO). Laravel Dusk/browser tests cover end-to-end flows (registration, login, admin post creation, media upload, settings updates, frontend search/comments) across mobile/tablet/desktop breakpoints.

#### Scenario: Run feature tests
- **WHEN** `php artisan test` executes in CI
- **THEN** all described feature/unit tests pass using SQLite in-memory DB with factories/seeders ensuring coverage across auth, posts, taxonomy, comments, API, search, newsletter.

### Requirement: Performance, Accessibility, and Security Testing
Performance tests (e.g., Artisan commands or scripts) MUST measure homepage, single post, API endpoints, and concurrency via tools like ab/Loader.io. Monitor N+1 queries, log slow queries >100ms, and profile memory usage. Accessibility audits (aXe/WAVE) MUST be part of QA checklist verifying WCAG compliance, keyboard navigation, contrast. SEO validations MUST assert presence of meta tags, canonical URLs, sitemap, robots.txt, structured data, page speed metrics. Security testing MUST include automated checks for XSS, SQLi, CSRF, file upload validation, composer audit for dependencies, and vulnerability scanning.

#### Scenario: Accessibility audit gate
- **WHEN** QA runs the accessibility script on staging
- **THEN** any WCAG failures block release until resolved, and the report is attached to the release checklist.

### Requirement: CI/CD Pipeline & Quality Gates
GitHub Actions (or equivalent) MUST run on push/PR: install dependencies, run `php artisan test`, run Pint (`./vendor/bin/pint --test`), run PHPStan/Larastan (level ≥5), report coverage (≥80%), optionally upload artifacts. Deployment workflow triggered on `main` push must SSH to server and execute `deploy.sh` (git pull, composer install --no-dev, npm build, migrate --force, cache clear, permissions, restart Supervisor/PHP-FPM). Failing tests or static analysis must block deployments.

#### Scenario: CI gate prevents merge
- **WHEN** a PR introduces Pint violations
- **THEN** the CI job fails, preventing the deploy job from running until the code is formatted/fixed.

### Requirement: Server Provisioning & Maintenance
Documented deployment steps MUST provision Ubuntu 22.04 (2 CPU/4GB RAM/40GB SSD), install PHP 8.2 + required extensions, Composer, Node 18, Nginx, Supervisor, cron. Nginx config (provided) must enable gzip, cache busting for static assets, secure PHP handling, asset caching, and deny dotfiles. `deploy.sh` must perform git pull, dependency installs, migrations, caching, permissions, service restarts. Supervisor config must manage `queue:work` with restart policies. Cron must run `php artisan schedule:run` every minute. Certbot-Let's Encrypt must manage SSL with auto-renew tests. Monitoring/logging must cover uptime, CPU/RAM/disk, slow queries, queue failures, with alerts. Firewall (UFW) should allow 22/80/443 only.

#### Scenario: Execute deploy script
- **WHEN** CI triggers deployment and `deploy.sh` runs on the server
- **THEN** the script pulls latest code, installs prod dependencies, builds assets, runs migrations with `--force`, clears caches, fixes permissions, restarts workers/PHP-FPM, and reports success without manual steps.

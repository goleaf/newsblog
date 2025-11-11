# Project Context

## Purpose
Deliver TechNewsHub, a Laravel 11-powered news/blog CMS focused on information systems, programming, and technology content. Provide both a rich public experience (responsive storytelling, accessibility, interactive features) and a robust admin experience (content management, analytics, security, DevOps automation) that can serve editorial teams and developers alike.

## Tech Stack
- Laravel 11 (PHP 8.2)
- SQLite for local/dev persistence; Redis/file cache as needed
- Tailwind CSS + Vite for styling/build tooling
- Laravel Breeze for authentication, Sanctum for APIs, Intervention/Image for media
- Optional: Meilisearch/Algolia or Laravel Scout for search, DomPDF/Browsershot for exports
- Node.js 18+, npm, Composer, Laravel Scribe for API docs

## Project Conventions

### Code Style
Follow PSR-12 for PHP, use Laravel Pint for formatting, and prefer expressive Blade/Tailwind markup. Keep controllers thin, rely on form requests for validation, and wrap complex behaviors in services or jobs. Use PHPDoc on public methods, avoid `dd()`/debug dumps in committed code, and keep assets versioned via `@vite`.

### Architecture Patterns
Adopt a modular spec-driven structure: `platform-core`, `admin-panel`, `frontend-experience`, `api-platform`, `quality-ops`, `testing-deployment`, `advanced-features`. Emphasize Eloquent models with scopes/accessors, observers for domain logic (slug, reading time, views), API resources for consistent JSON envelopes, and queued jobs for long-running tasks (exports, digests, social posting). Use middleware layers (`auth`, `api.admin`, `json.response`, `throttle`) and leverage caching/tagging for performance.

### Testing Strategy
Run PHPUnit feature/unit suites (SQLite in-memory), factories/seeders provide data, and include Laravel Dusk for critical browser flows. Add Pint, PHPStan/Larastan level 5+, and aim for ≥80% coverage. Include performance, accessibility (aXe/WAVE), SEO, and security tests (XSS, SQLi, CSRF) plus load tests for key pages. Validate API contracts with Sanctum tokens and rate-limit simulations.

### Git Workflow
Use feature branches off `main`, open spec-backed change proposals (via OpenSpec) before implementation, and require `composer test`/Pint/PHPStan to pass before merging. Apply descriptive commits referencing change IDs. Merge via pull request once CI/CD (GitHub Actions) approves; deployment happens from `main` through `deploy.sh`.

## Domain Context
TechNewsHub targets tech-savvy readers and editorial teams. Content centers on programming, infosec, and technology trends. Admins manage posts (articles/tutorials), pages, newsletters, settings, and compliance (GDPR, maintenance). Public UX must handle hero/trending sections, interactive search, newsletters, sharing, accessibility, and detailed analytics.

## Important Constraints
- SQLite for local/dev DB; plan for easy switch to production-ready engine if needed
- Strict role-based permissions: admin/editor/author
- Performance targets: caching strategies, lazy loading, progress updates, CDN-friendly assets
- Security: CSP, headers, rate limiting, 2FA, spam prevention, file validation
- Accessibility: WCAG AA, keyboard nav, focus management, print styles, dark mode

## External Dependencies
- Laravel Sanctum, Breeze, Fortify/Pragma (for 2FA)
- Intervention/Image (media optimization)
- Laravel Scribe (API docs)
- Redis (preferred cache); fallback file cache acceptable
- Optional: Meilisearch/Algolia, DomPDF/Browsershot, Pusher/Laravel Echo, social media APIs, email providers (SMTP/Mailgun), storage services (S3 for backups), analytics (Google Analytics/Search Console), monitoring (Sentry/Telescope), queue workers (Supervisor)

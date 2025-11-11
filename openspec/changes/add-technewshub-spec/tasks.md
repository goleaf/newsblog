## 0. Diagnostics & Planning
- [x] 0.1 Validate `https://newsblog.test/` loads without browser errors.
- [x] 0.2 Capture route/Blade discrepancies and categorize blockers.
- [ ] 0.3 Prepare prioritized fix list aligning with Tailwind refactor strategy.

## 1. Platform Core & Infrastructure
- [ ] 1.1 Scaffold Laravel 11 "TechNewsHub" with SQLite, Breeze, Sanctum, Intervention/Image.
- [ ] 1.2 Implement migrations for users, categories, tags, posts, taxonomy pivots, comments, media library, pages, newsletters, settings, post views, contact messages per spec.
- [ ] 1.3 Build enriched Eloquent models, scopes, observers, and relationships (auto slugging, reading time, view tracking, etc.).
- [ ] 1.4 Wire caching, queues, scheduled jobs, and core services that other capabilities depend on.

## 2. Admin Panel
- [ ] 2.1 Create admin layout (sidebar, header, breadcrumbs, dark mode) and dashboard widgets/charts.
- [ ] 2.2 Deliver posts CRUD with editor, autosave, filters, bulk actions, SEO box, media picker, validation.
- [ ] 2.3 Ship taxonomy, media library, comments, users, pages, newsletter, and settings modules with workflows and protections described in Prompts 5-11.

## 3. Frontend Experience
- [ ] 3.1 Build site layout, navigation/mega menus, hero/trending areas, cards, sidebar widgets, and footer.
- [ ] 3.2 Implement single post, category/tag/archive/search/author pages, plus interactive behaviors (search, comments, share, newsletter, view counters, progress, accessibility, toast/lightbox/sticky behavior, loading states).

## 4. API Platform
- [ ] 4.1 Expose v1 REST API with public, authenticated, and admin endpoints, Sanctum auth, rate limiting tiers, CORS, and API resources.
- [ ] 4.2 Integrate Laravel Scribe (or equivalent) for interactive API docs at /docs with examples.

## 5. Quality, Performance, and Advanced Features
- [ ] 5.1 Implement caching strategy, SEO/image optimization, security headers, analytics, backups, multilingual/PWA options, and email notification flows.
- [ ] 5.2 Add advanced UX features: bookmarks, reactions, related-posts scoring, TOC/breadcrumbs/read-time, content warnings, templates, draft previews, scheduling extensions, revisions, guest posts, featured authors, series, reading-progress sync, recommendations, social proof, enhanced print.

## 6. Testing, Deployment, and CI/CD
- [ ] 6.1 Configure factories, seeders, PHPUnit, Pest/Laravel test harness, Dusk, and coverage tooling per Prompt 18.
- [ ] 6.2 Add performance/security tests, accessibility/SEO validations, Composer audit cadence.
- [ ] 6.3 Provision deployment scripts, server automation (Nginx, Supervisor, cron), GitHub Actions pipelines, SSL, monitoring/logging described in Prompt 19.

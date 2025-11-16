# TechNewsHub Roadmap (OpenSpec)

This roadmap tracks implementation via OpenSpec changes. Use `openspec validate <id> --strict` to validate a change and `openspec show <id>` to inspect details. Archive completed changes with `openspec archive <id> --yes`.

- Plan
  - plan-technewshub-implementation — Master implementation plan (proposal, tasks, design)
- Foundation
  - phase-1-foundation — Verify Sanctum, Scout/Meilisearch, Pint, Redis, S3/CloudFront, core schema
- Auth & Users
  - phase-2-auth — Auth controllers/requests/views, roles/policies, rate limits
- CMS
  - phase-3-cms — Article controllers/requests/views, ArticleService, view tracking, categories/tags/media
- Comments & Moderation
  - phase-4-comments-moderation — Threaded comments, reactions, auto‑moderation, queue
- Search & Discovery
  - phase-5-search-discovery — Search endpoints/filters, logs/clicks, UI + suggestions
- Bookmarks & Reading Lists
  - phase-6-bookmarks-readinglists — Bookmark toggle/list; reading lists CRUD/reorder/share
- Social & Engagement
  - phase-7-social-engagement — Share tracking, follow system, activity feed
- Notifications
  - phase-8-notifications — Notifications + preferences + jobs + UI
- Newsletters
  - phase-9-newsletters — Subscribe/confirm/unsubscribe, generation/sending/tracking/scheduling, admin UI
- Analytics
  - phase-10-analytics — Metrics service/controller/views, caching, jobs
- Recommendations
  - phase-11-recommendations — Similarities, recommendations, CTR tracking
- REST API
  - phase-12-api — v1 endpoints/resources, Sanctum auth, rate limits, docs
- Performance
  - phase-13-performance — Caching/invalidation, DB tuning, queues, assets/CDN
- Security
  - phase-14-security — Passwords, sessions/CSRF/headers, rate limits, sanitization, GDPR
- Accessibility & Responsiveness
  - phase-15-accessibility — Responsive UI, keyboard nav, ARIA, contrast, screen reader support
- SEO
  - phase-16-seo — Sitemaps, meta tags, clean URLs, structured data, robots.txt
- Admin & Monitoring
  - phase-17-admin-monitoring — Admin dashboard, health checks, logging/monitoring, settings, Horizon, performance monitor
- Deployment & Infra
  - phase-18-deployment — Docker/Nginx/envs, CI/CD, scripts, backups, monitoring/alerts
- Testing & QA
  - phase-19-testing-qa — Unit/feature/API/performance/security/a11y coverage
- Docs & Launch
  - phase-20-docs-launch — Docs, final QA, prod optimization, monitoring, launch checklist

Legacy and earlier changes
- add-technewshub-spec — Initial capability specs
- expand-technewshub-spec-phase-2 — Expanded specs (phase 2)
- refactor-technewshub-frontend — Frontend refactor proposal
- add-full-app-tests — Testing expansion plan


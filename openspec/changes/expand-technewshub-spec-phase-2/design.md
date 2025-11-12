## Context
Prompts 19-40 expand TechNewsHub beyond publishing basics into governance, automation, and compliance. The additions span frontend rendering, admin UX, backend services, queues, security, and integrations. We must ensure the plan coordinates storage (e.g., widgets/menu schemas), asynchronous jobs (exports, imports, social posts, broken-link scans), analytics dashboards, and security posture.

## Goals / Non-Goals
- Goals: codify requirements for print, import/export, search, widgets, menus, maintenance, activity logging, notifications, 2FA, API monitoring, GDPR, performance dashboards, experimentation, calendars, editorial assistance, link health, spam protection, social/email automation, and final quality gates.
- Non-Goals: choose exact UI libraries beyond Tailwind + existing tech stack; implement vendor-specific authentication or analytics providers; define infrastructure-as-code.

## Decisions
- Treat each thematic cluster as its own capability spec to keep deltas maintainable.
- Favor asynchronous queues (already mandated in phase 1) for heavy operations: imports, exports, link/spam scans, email digests, social posting.
- Leverage Laravel native tools where possible (Notifications, Jobs, Events) while remaining CDN-free, Tailwind-only, and multi-language-first.

## Risks / Trade-offs
- Breadth may overwhelm implementation; tasks should prioritize critical governance first.
- New tables (widgets, menus, logs, notifications) increase schema surface; migrations must respect SQLite constraints for prototyping.
- Integrations (social APIs, reCAPTCHA) introduce secrets management; specs must flag compliance expectations.

## Migration Plan
1. Extend schema for widgets, menus, notifications, activity logs, tests.
2. Incrementally implement each capability, starting with governance (maintenance, 2FA, activity log) before automation.
3. Roll out content assistance tools alongside editorial UI updates.
4. Backfill automated tests after each capability per project rules.

## Open Questions
- Should advanced search default to Scout database driver or require Meilisearch? (Spec will allow either path.)
- Preferred provider for social auto-posting (native APIs vs. third-party)?
- How to balance optional vs. mandatory GDPR tooling when no user accounts exist? (Spec will target admin/editor roles only.)


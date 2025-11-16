## 1. Planning & Inventory
- [x] 1.1 Confirm authoritative list of `app/` PHP files and categorize by responsibility (controllers, services, jobs, policies, Nova, etc.).
  - Inventory date: 2025-11-16
  - Totals: 333 PHP files under `app/`
  - Breakdown (files):
    - Console/Commands: 18
    - Http/Controllers: 54
    - Http/Requests: 56
    - Http/Middleware: 7
    - Jobs: 8
    - Models: 45
    - Policies: 11
    - Services: 28
    - Nova: 69
    - Observers: 3
    - Enums: 7
    - Providers: 2
    - Traits: 2
    - View: 5
    - Support: 2
    - Exceptions: 4
    - DataTransferObjects: 1
    - Mail: 8
    - Listeners: 1
  - Reproduce:
    - List: `rg --files app -g "*.php" | sort`
    - Count by group (example): `rg --files app/Services -g "*.php" | wc -l`
- [x] 1.2 Establish batching strategy to deliver coverage incrementally without breaking the build.
  - Batch 1 — Domain Core: Models, Enums, Observers, Providers
  - Batch 2 — Services: Unit tests for pure services (Search, Sitemap, Caching, Settings, etc.)
  - Batch 3 — Policies: Authorization matrix and edge cases
  - Batch 4 — Public HTTP: Controllers + Form Requests + Middleware (feature tests)
  - Batch 5 — Admin HTTP: Admin controllers + Form Requests (feature tests)
  - Batch 6 — Jobs & Console: Queue jobs and artisan commands
  - Batch 7 — Nova: Resources, Actions, Filters, Lenses (feature tests)
  - Batch 8 — Mail, Listeners, View Components, Support/DTOs

## 2. Infrastructure Alignment
- [ ] 2.1 Enforce single Tailwind layout and extract all inline CSS/JS into `resources/` assets.
- [ ] 2.2 Ensure all user-facing strings are translatable via JSON language files.
- [ ] 2.3 Introduce/align Form Request classes for each controller action with validation rules and messages.

## 3. Test Scaffolding
- [ ] 3.1 Audit factories, seeders, and test utilities; create missing ones needed for coverage.
- [ ] 3.2 Create/expand PHPUnit base helpers for Nova, jobs, mail, and console command testing.
- [ ] 3.3 Author Feature/Unit tests for each namespace batch (controllers, services, jobs, mailables, console, policies, observers, traits, DTOs, Nova).
  - Progress (Batch 1 — Domain Core):
    - [x] Models: Category meta defaults, Post meta tags + structured data
    - [x] Providers: AppServiceProvider bindings (singleton + policy mapping)
    - [ ] Observers: Deferred to Batch 3/7 where related behaviors are exercised

## 4. Execution & Validation
- [x] 4.1 Run targeted `php artisan test` subsets per batch and resolve failures.
  - Executed: `php artisan test tests/Unit/Models/CategoryDescendantsTest.php`
  - Executed: `php artisan test tests/Unit/Models/PostMetaDataTest.php`
  - Executed: `php artisan test tests/Unit/Providers/AppServiceProviderTest.php`
- [ ] 4.2 Execute full test suite after each major batch; fix regressions.
- [ ] 4.3 Run `npm run build` and `vendor/bin/pint --dirty` to ensure assets and formatting stay consistent.
  - Ran Pint: `vendor/bin/pint --dirty` (1 file fixed)

## 5. Documentation & Reporting
- [ ] 5.1 Update `tasks.md` and `todo.md` with progress for each batch.
- [ ] 5.2 Summarize command run outputs and any notable fixes.








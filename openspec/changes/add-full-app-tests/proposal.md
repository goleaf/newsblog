# Change: Achieve Full Test Coverage for `app/` Namespace

## Why
- The project owner has mandated that every class under `app/` must have automated test coverage.
- Existing coverage is sparse and uneven, leaving critical behaviors (controllers, services, jobs, Nova resources, policies, commands) unverified.
- Introducing exhaustive coverage requires coordinated refactors (Form Requests, translation compliance, Tailwind-only views) that must be planned before implementation.

## What Changes
- Inventory each class under `app/` and map it to new or updated PHPUnit Feature/Unit tests.
- Introduce or refactor supporting infrastructure (factories, Form Requests, localization files, tailwind-based view scaffolding) to enable reliable testing.
- Establish translation-first messaging by moving hard-coded strings into JSON language files to satisfy multi-language requirements.
- Convert legacy styling or layout assumptions into the single Tailwind-driven layout while keeping tests deterministic.
- Update build and CI workflows (npm build, Pint, targeted `php artisan test`) to validate the new coverage.

## Impact
- Affected specs: `testing`
- Affected code: Extensive. Controllers, requests, services, jobs, mailables, policies, Nova resources, view components, Tailwind assets, translation files, factories, seeding utilities, and test suites.
- Risk: High. Without phased delivery and test scaffolding, changes can destabilize production features (auth flows, Nova dashboards, queue jobs). Requires staged execution and continuous validation.








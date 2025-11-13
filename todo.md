## Priority Tasks

0. Align on the directive to create tests for every file under `app/` and break work into deliverable batches.
1. Catalogue every class under `app/` and document its intended test coverage.
2. Map existing tests to application classes and flag uncovered areas.
3. Prioritize controller + service coverage with dedicated Form Requests and translation assertions.
4. Backfill jobs, listeners, mailables, DTOs, and traits with focused unit tests.
5. Extend coverage to models, policies, observers, providers, and Nova resources.
6. Run targeted and full PHPUnit suites, iterating on failures until green.

## Backlog Follow-Ups

1. Verify multilingual validation/messages through Form Requests; add missing translations.
2. Document mapping between routes, controllers, and new tests.
3. Capture executed commands and outcomes in `tasks.md`.

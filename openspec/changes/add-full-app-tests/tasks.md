## 1. Planning & Inventory
- [ ] 1.1 Confirm authoritative list of `app/` PHP files and categorize by responsibility (controllers, services, jobs, policies, Nova, etc.).
- [ ] 1.2 Establish batching strategy to deliver coverage incrementally without breaking the build.

## 2. Infrastructure Alignment
- [ ] 2.1 Enforce single Tailwind layout and extract all inline CSS/JS into `resources/` assets.
- [ ] 2.2 Ensure all user-facing strings are translatable via JSON language files.
- [ ] 2.3 Introduce/align Form Request classes for each controller action with validation rules and messages.

## 3. Test Scaffolding
- [ ] 3.1 Audit factories, seeders, and test utilities; create missing ones needed for coverage.
- [ ] 3.2 Create/expand PHPUnit base helpers for Nova, jobs, mail, and console command testing.
- [ ] 3.3 Author Feature/Unit tests for each namespace batch (controllers, services, jobs, mailables, console, policies, observers, traits, DTOs, Nova).

## 4. Execution & Validation
- [ ] 4.1 Run targeted `php artisan test` subsets per batch and resolve failures.
- [ ] 4.2 Execute full test suite after each major batch; fix regressions.
- [ ] 4.3 Run `npm run build` and `vendor/bin/pint --dirty` to ensure assets and formatting stay consistent.

## 5. Documentation & Reporting
- [ ] 5.1 Update `tasks.md` and `todo.md` with progress for each batch.
- [ ] 5.2 Summarize command run outputs and any notable fixes.






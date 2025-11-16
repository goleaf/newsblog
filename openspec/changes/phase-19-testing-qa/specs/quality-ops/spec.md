## ADDED Requirements

### Requirement: Test Coverage Across Domains
The platform MUST include targeted unit/feature/API/performance/security/accessibility tests for core features with minimal flakiness.

#### Scenario: Filtered test runs
- WHEN running a targeted subset (e.g., `php artisan test --filter=PostScopesTest`)
- THEN the subset executes successfully and verifies relevant behavior.


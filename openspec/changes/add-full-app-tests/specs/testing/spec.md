## ADDED Requirements
### Requirement: Comprehensive Test Coverage for `app/`
All PHP classes under the `app/` namespace MUST be covered by automated PHPUnit tests that assert their intended behavior, including controllers, requests, services, jobs, mailables, events, listeners, observers, policies, providers, console commands, traits, DTOs, Nova resources, filters, metrics, tools, actions, middleware, and helpers.

#### Scenario: Controller and Request Coverage
- **WHEN** a controller action is executed through its HTTP route
- **THEN** the corresponding Form Request is validated, localized messages are asserted, and the response behavior is verified via a Feature test.

#### Scenario: Service and Job Coverage
- **WHEN** a service method or queued job is invoked within a test
- **THEN** the test SHALL mock or fake external integrations, assert side effects (database, notifications, queues), and validate localization compliance.

#### Scenario: Console and Nova Coverage
- **WHEN** console commands or Nova resources/actions are exercised in tests
- **THEN** the tests SHALL verify authorization logic, data transformations, and Any UI-presented strings via translation files without relying on runtime Nova authentication.

#### Scenario: Asset and Localization Alignment
- **WHEN** tests rely on views, components, or notifications
- **THEN** the project SHALL use Tailwind-based layouts and JSON-language strings so tests do not reference inline CSS/JS or untranslated literals.








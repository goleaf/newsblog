## ADDED Requirements

### Requirement: Sanctum Configuration Verified
API authentication MUST be configured with Laravel Sanctum, including stateful domains via env, `web` guard, and optional token prefix. No interactive changes are required to verify.

#### Scenario: Sanctum config present
- GIVEN `config/sanctum.php`
- WHEN loading configuration
- THEN `guard` contains `web`, `stateful` domains are derived from env, and `token_prefix` is configurable.

### Requirement: Scout + Meilisearch Configuration Verified
Full‑text search MUST be wired via Laravel Scout with Meilisearch values driven by environment variables. Batch chunk sizes and queueing behavior MUST be configurable.

#### Scenario: Scout driver and Meilisearch are configurable via env
- GIVEN `config/scout.php`
- WHEN reading `driver` and `meilisearch` settings
- THEN the driver defaults to env `SCOUT_DRIVER` and Meilisearch host/key are read from `MEILISEARCH_*`.


## ADDED Requirements

### Requirement: Environment & Storage Configuration Verified
The application MUST provide environment samples for Redis and S3/CloudFront and configure local/public/S3 filesystems. Public storage MUST be linkable via `storage:link` and S3 URLs MUST be supported via `AWS_URL`.

#### Scenario: Environment files contain Redis and S3 variables
- GIVEN the repository
- WHEN reading `.env.example` (and environment variants)
- THEN variables `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, and `AWS_*` keys exist with sane defaults.

#### Scenario: Filesystems configured for local and S3 with URL
- GIVEN `config/filesystems.php`
- WHEN checking the `disks` array
- THEN `local`, `public`, and `s3` disks exist and `s3` includes `url` mapping to `AWS_URL`.

### Requirement: Core Schema Migrations Exist
The repository MUST include migrations for foundational domain tables (users, categories, posts, tags, pivots, comments, post views, newsletters, settings, search logs) to enable a working baseline.

#### Scenario: Foundational migrations are present
- GIVEN `database/migrations`
- WHEN enumerating migration filenames
- THEN files exist that create: users; categories (with parent support); posts; tags; post_tag (or equivalent); comments; post_views; newsletters and newsletter_sends; settings; search_logs (and search_clicks), plus any required pivots (e.g., category_post) supporting relationships.

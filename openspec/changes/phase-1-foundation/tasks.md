## Phase 1 Verification Checklist

- [x] Verify Sanctum installed and configured
  - composer.json contains `laravel/sanctum`
  - `config/sanctum.php` present; guards and stateful domains set via env

- [x] Verify Scout + Meilisearch configured
  - composer.json contains `laravel/scout` and `meilisearch/meilisearch-php`
  - `config/scout.php` present; `SCOUT_DRIVER=meilisearch` and `MEILISEARCH_HOST` in env files

- [x] Verify Pint configured
  - composer.json require-dev contains `laravel/pint`
  - `.pint.json` present

- [x] Verify Redis env settings present
  - env files define `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`

- [x] Verify S3 / CloudFront storage config
  - `config/filesystems.php` includes `s3` disk with `url` support
  - env files include `AWS_*` variables and (when applicable) `AWS_URL`

- [x] Verify DB schema coverage snapshot
  - Sanity-check core migrations for categories, posts, tags, post_tag, comments, post_views, newsletters, settings, search logs
  - Note: deeper schema assertions to be covered by dedicated spec phases
  - Observed present migrations: users, categories, posts, tags, post_tag, comments, media library, newsletters (+ sends), pages, settings, contact messages, post views, search logs, search clicks, series (+ pivots), broken links, engagement metrics, widget areas/widgets, notifications, category_post pivot, performance indexes

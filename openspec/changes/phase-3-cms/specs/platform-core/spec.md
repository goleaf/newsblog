## ADDED Requirements

### Requirement: Article Domain Behavior
The `Article` (Post) domain MUST include relationships (author, category, tags, comments, views), scopes (published, popular, trending, scheduled), accessors (formatted date, reading time text, excerpt limited, featured image URL), and helpers (`incrementViewCount`, `isPublished`, `canBeEditedBy`). Reading time MUST derive from word count.

#### Scenario: Publishing and scheduling
- GIVEN a draft with `scheduled_at` in the future
- WHEN status becomes scheduled
- THEN it appears in `scheduled` scope but not `published` until `published_at` is in the past; slug & reading time are set if missing.

### Requirement: ArticleService Minimal Responsibilities
The platform MUST provide a service responsible for creating/updating/publishing articles with cache invalidation hooks and reading time calculation, delegating featured image processing to the media service.

#### Scenario: Update invalidates caches
- WHEN updating title/excerpt/content/category/status on an article
- THEN related view/query caches and sitemap are invalidated/regenerated as configured.


## ADDED Requirements

### Requirement: Caching Strategy & Invalidation
The platform MUST implement caching for views and queries with clear invalidation on content changes and a warm-up routine post-deploy.

#### Scenario: Invalidate on post update
- WHEN updating a post title/content/category
- THEN homepage/category/post caches and sitemap are invalidated/regenerated.


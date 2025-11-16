## Phase 3 – CMS Checklist

- [ ] Article management (controllers + requests + views)
  - ArticleController: index/show/create/store/edit/update/destroy; publish/unpublish actions
  - StoreArticleRequest / UpdateArticleRequest with validation and slug generation
  - Views: list with pagination, detail with reading progress, create/edit forms, preview
- [ ] ArticleService
  - Create/update/publish with cache invalidation and reading time calculation
  - Featured image processing hook (delegates to media service)
- [ ] View Tracking Middleware
  - Unique + total views; avoid duplicate within a session
- [ ] Category management (controller + views + admin CRUD)
- [ ] Tag management (controller + views + autocomplete)
- [ ] Media management (controller + service)
  - Validation, optimization, responsive variants, optional S3/CDN upload


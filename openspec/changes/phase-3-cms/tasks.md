## Phase 3 – CMS Checklist

- [ ] Article management (controllers + requests + views)
  - ArticleController: index/show/create/store/edit/update/destroy; publish/unpublish actions
  - StoreArticleRequest / UpdateArticleRequest with validation and slug generation
  - Views: list with pagination, detail with reading progress, create/edit forms, preview
- [x] ArticleService
  - Create/update/publish with cache invalidation and reading time calculation
  - Featured image processing hook (delegates to media service)
- [x] View Tracking Middleware
  - Unique + total views; avoid duplicate within a session
- [x] Category management (controller + views + admin CRUD)
  - Implemented: app/Http/Controllers/CategoryController.php with filters/sorting/caching; views under resources/views/categories; tests in tests/Feature/CategoryPageTest.php
- [x] Tag management (controller + views + autocomplete)
  - Implemented: app/Http/Controllers/TagController.php with filters/sorting/caching; views under resources/views/tags; tests in tests/Feature/TagPageTest.php
- [x] Media management (controller + service)
  - Implemented: app/Http/Controllers/MediaController.php + App\Services\ImageProcessingService
  - Validation, optimization, responsive variants, optional S3/CDN upload

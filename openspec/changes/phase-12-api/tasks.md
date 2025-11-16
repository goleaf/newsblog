## Phase 12 – API Checklist

- [ ] Sanctum token routes & abilities
  - Added /api/v1/tokens (list, create, delete) with abilities and optional expiry
- [x] v1 endpoints: articles (list/show + create/update/delete), media, search, widgets, post interactions (reactions/bookmark)
  - Implemented in routes/api.php under prefix v1
- [x] v1 endpoints: categories (list, articles), comments (list, create), users (me), bookmarks (list)
  - Implemented in routes/api.php with controllers in App\Http\Controllers\Api
- [x] API Resources: ArticleResource, UserResource, CommentResource, CategoryResource, TagResource
  - Implemented resources under app/Http/Resources with basic shapes
- [x] Rate limits and CORS
  - Rate limits configured: throttle:api + custom groups (search)
- [ ] API docs (Scribe/OpenAPI)
 
### Updates on 2025-11-16

- [x] v1 endpoints: tags (list, articles)
  - Implemented TagController under Api with routes in routes/api.php
- [x] Extended comments API: update, delete
  - Added UpdateCommentRequest, update/destroy actions and routes under auth:sanctum
- [x] Sanctum token management endpoints
  - Added TokenController with list/create/delete and tests

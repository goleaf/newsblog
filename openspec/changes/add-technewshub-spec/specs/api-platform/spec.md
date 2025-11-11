## ADDED Requirements

### Requirement: Public API Endpoints (v1)
Versioned routes (`/api/v1/...`) MUST expose: `GET /posts` (paginated, filterable by category/tag/author/search, sortable latest/popular/trending, returning pagination meta), `GET /posts/{slug}` (full post + author/category/tags/comments), `GET /posts/{id}/related`, `GET /categories`, `GET /categories/{slug}`, `GET /categories/{slug}/posts`, `GET /tags`, `GET /tags/{slug}/posts`, `GET /search`, newsletter subscribe/verify/unsubscribe endpoints, `GET /posts/{id}/comments`, `POST /comments`, and `POST /contact`. Only approved comments return via API.

#### Scenario: Fetch filtered posts
- **WHEN** a client calls `GET /api/v1/posts?category=security&per_page=12&sort=popular`
- **THEN** the response is 200 JSON containing `success=true`, a `data.posts` array with author/category/tag snippets, and `meta` pagination keys per the standard format.

### Requirement: Authenticated User Endpoints
Sanctum-authenticated users MUST access `POST /register`, `POST /login`, `POST /logout`, `GET /user`, `GET /user/posts`, `POST /user/posts` (create draft), `PUT /user/posts/{id}`, `DELETE /user/posts/{id}`, `GET /user/comments`, `PUT /user/comments/{id}`, `DELETE /user/comments/{id}`. Authorization must ensure users only mutate their own resources and drafts default to `draft` status unless elevated roles publish.

#### Scenario: Update own post via API
- **GIVEN** a logged-in author with a draft post
- **WHEN** they `PUT /api/v1/user/posts/{id}` with updated content
- **THEN** the response returns `success=true`, the updated post data, and other users cannot access or modify that draft via the same endpoint.

### Requirement: Admin API Endpoints
Admin-scoped routes (guarded by Sanctum + `api.admin` middleware) MUST provide CRUD endpoints for posts, categories, tags, users, comments, pages, settings, plus dashboard stats at `/api/v1/admin/stats` returning totals (posts, views, comments, subscribers, new users). These endpoints must mirror admin panel capabilities, including pagination, filtering, bulk operations where applicable, and respect caching invalidation.

#### Scenario: Retrieve dashboard stats
- **WHEN** an admin calls `GET /api/v1/admin/stats`
- **THEN** JSON returns aggregated counts for total posts, today/week/month views, pending comments, subscriber totals, and new user counts, matching the admin dashboard widgets.

### Requirement: API Infrastructure & Documentation
All API responses MUST follow the standard JSON envelope with `success`, `data`, `message`, and optional `meta` pagination block. Errors MUST include `success=false`, `message`, and `errors` hash keyed by field. Rate limiting tiers: public 60 rpm, authenticated 100 rpm, admin 200 rpm enforced via throttle middleware. Middleware stack MUST include `json.response` (forces JSON), `auth:sanctum`, `api.admin`, and role guards. API Resources (PostResource, CategoryResource, TagResource, UserResource, CommentResource) MUST control serialization, compute derived fields, and conditionally include relationships based on auth context. Enable URL versioning `/api/v1/`, configure CORS (origins allowlist, credential support). Generate interactive docs via Laravel Scribe at `/docs`, including request params, sample payloads, and response examples.

#### Scenario: Validate docs generation
- **WHEN** `php artisan scribe:generate` runs and `/docs` is visited
- **THEN** the documentation lists all public/auth/admin endpoints with descriptions, parameters, headers, example responses following the envelope format, and supports “Try It” requests using Sanctum tokens.

## ADDED Requirements

### Requirement: v1 API Endpoints & Resources
The API MUST expose namespaced v1 endpoints for articles, categories, comments, users (me + public), bookmarks, and search, using Eloquent API Resources and Sanctum authentication.

#### Scenario: Authenticated create article
- WHEN POSTing to /api/v1/articles with valid data and token
- THEN an article is created and returned via ArticleResource.

#### Scenario: Rate limiting headers
- WHEN exceeding a rate limit
- THEN a 429 response with rate limit headers is returned.


# API Documentation Guide for Developers

This guide explains how to document API endpoints using Scribe annotations in Laravel controllers.

## Table of Contents

1. [Overview](#overview)
2. [Basic Annotations](#basic-annotations)
3. [Grouping Endpoints](#grouping-endpoints)
4. [Documenting Parameters](#documenting-parameters)
5. [Response Examples](#response-examples)
6. [Authentication](#authentication)
7. [Best Practices](#best-practices)
8. [Regenerating Documentation](#regenerating-documentation)

## Overview

We use [Scribe](https://scribe.knuckles.wtf/) to automatically generate API documentation from PHPDoc annotations in our controllers. Scribe extracts information from:

- PHPDoc comments
- Form Request validation rules
- Route definitions
- API Resource transformers

## Basic Annotations

### Controller Group

Add a `@group` annotation at the class level to organize endpoints:

```php
/**
 * @group Posts
 *
 * API endpoints for managing and retrieving blog posts.
 */
class PostController extends Controller
{
    // ...
}
```

### Method Documentation

Document each endpoint method with a title and description:

```php
/**
 * List Posts
 *
 * Get a paginated list of published posts. You can filter by category, tag, or search term.
 */
public function index(Request $request)
{
    // ...
}
```

## Grouping Endpoints

Organize your API into logical groups:

- **Posts**: Article management
- **Users**: User profiles and accounts
- **Comments**: Comment system
- **Bookmarks**: Saved articles
- **Search**: Search functionality
- **Notifications**: Notification system
- **Auth Tokens**: Token management

## Documenting Parameters

### URL Parameters

Use `@urlParam` for route parameters:

```php
/**
 * Get Single Post
 *
 * @urlParam id integer required The post ID or slug. Example: 1
 */
public function show($id)
{
    // ...
}
```

### Query Parameters

Use `@queryParam` for query string parameters:

```php
/**
 * List Posts
 *
 * @queryParam category string Filter by category slug. Example: technology
 * @queryParam tag string Filter by tag slug. Example: laravel
 * @queryParam search string Search in title and content. Example: php
 * @queryParam page integer Page number for pagination. Example: 1
 */
public function index(Request $request)
{
    // ...
}
```

### Body Parameters

Use `@bodyParam` for request body fields:

```php
/**
 * Create Post
 *
 * @bodyParam title string required The article title. Must not exceed 255 characters. Example: Getting Started with Laravel
 * @bodyParam content string required The full article content. Example: <p>Article content...</p>
 * @bodyParam category_id integer required The category ID. Example: 1
 * @bodyParam status string required Article status. Must be one of: draft, published, scheduled. Example: published
 * @bodyParam is_featured boolean optional Mark as featured article. Example: false
 */
public function store(StorePostRequest $request)
{
    // ...
}
```

**Parameter Format:**
```
@bodyParam name type required/optional Description. Example: value
```

**Types:** `string`, `integer`, `boolean`, `array`, `object`, `file`

## Response Examples

### Success Response

Use `@response` to document successful responses:

```php
/**
 * Get Single Post
 *
 * @response 200 {
 *   "data": {
 *     "id": 1,
 *     "title": "Example Post",
 *     "slug": "example-post",
 *     "content": "Full post content...",
 *     "author": {
 *       "id": 1,
 *       "name": "John Doe"
 *     }
 *   }
 * }
 */
```

### Error Responses

Document error scenarios:

```php
/**
 * Get Single Post
 *
 * @response 200 {
 *   "data": {...}
 * }
 * @response 404 {
 *   "message": "Post not found"
 * }
 */
```

### Multiple Status Codes

```php
/**
 * Create Post
 *
 * @response 201 {
 *   "data": {
 *     "id": 1,
 *     "title": "New Post"
 *   }
 * }
 * @response 422 {
 *   "message": "The given data was invalid.",
 *   "errors": {
 *     "title": ["The title field is required."]
 *   }
 * }
 * @response 403 {
 *   "message": "This action is unauthorized."
 * }
 */
```

### Response Scenarios

Use scenarios for different response types:

```php
/**
 * Delete Post
 *
 * @response 204 scenario="Success"
 * @response 403 scenario="Unauthorized" {
 *   "message": "This action is unauthorized."
 * }
 */
```

## Authentication

### Marking Authenticated Endpoints

Use `@authenticated` to indicate authentication is required:

```php
/**
 * Create Post
 *
 * Create a new article. Requires authentication.
 *
 * @authenticated
 */
public function store(StorePostRequest $request)
{
    // ...
}
```

### Unauthenticated Endpoints

For public endpoints, use `@unauthenticated` (or omit authentication annotation):

```php
/**
 * List Posts
 *
 * Get a paginated list of published posts.
 *
 * @unauthenticated
 */
public function index(Request $request)
{
    // ...
}
```

## Best Practices

### 1. Be Descriptive

Write clear, concise descriptions that explain what the endpoint does:

```php
/**
 * Update Post
 *
 * Update an existing article. Requires authentication and ownership or admin role.
 * Only the article author or administrators can update articles.
 */
```

### 2. Provide Realistic Examples

Use realistic example values that make sense:

```php
// Good
@bodyParam title string required The article title. Example: Getting Started with Laravel

// Bad
@bodyParam title string required The article title. Example: abc123
```

### 3. Document All Parameters

Include all parameters, even optional ones:

```php
@bodyParam title string required The article title. Example: My Article
@bodyParam excerpt string optional Short description. Max 500 characters. Example: This is a brief summary
@bodyParam is_featured boolean optional Mark as featured. Default: false. Example: false
```

### 4. Include Validation Rules

Mention important validation rules in descriptions:

```php
@bodyParam email string required User email. Must be unique. Example: user@example.com
@bodyParam password string required Password. Must be at least 8 characters. Example: SecurePass123
```

### 5. Document Relationships

Explain related resources in responses:

```php
/**
 * Get Single Post
 *
 * Retrieve a post with its author, category, tags, and approved comments.
 *
 * @response 200 {
 *   "data": {
 *     "id": 1,
 *     "title": "Example",
 *     "author": {...},
 *     "category": {...},
 *     "tags": [...],
 *     "comments": [...]
 *   }
 * }
 */
```

### 6. Use Consistent Formatting

Follow consistent patterns across all endpoints:

- Use sentence case for descriptions
- End descriptions with periods
- Use consistent example values
- Format JSON responses with proper indentation

### 7. Document Edge Cases

Mention special behaviors or edge cases:

```php
/**
 * Delete Post
 *
 * Delete an article. The article will be soft-deleted and can be restored later.
 * Deleting a post also soft-deletes all associated comments.
 *
 * @authenticated
 */
```

## Regenerating Documentation

After adding or updating documentation annotations:

```bash
# Generate documentation
php artisan scribe:generate

# Force regeneration (ignores cache)
php artisan scribe:generate --force
```

The documentation will be available at:
- HTML: `http://localhost/docs`
- Postman: `http://localhost/docs.postman`
- OpenAPI: `http://localhost/docs.openapi`

## Configuration

The Scribe configuration is in `config/scribe.php`. Key settings:

```php
'type' => 'laravel',  // Generate as Laravel Blade views
'auth' => [
    'enabled' => true,
    'in' => 'bearer',
    'name' => 'Authorization',
],
'example_languages' => ['bash', 'javascript', 'php', 'python'],
```

## Example: Complete Endpoint Documentation

Here's a complete example of a well-documented endpoint:

```php
/**
 * @group Posts
 *
 * API endpoints for managing and retrieving blog posts.
 */
class PostController extends Controller
{
    /**
     * Create Post
     *
     * Create a new article. Requires authentication and author or admin role.
     * The article will be assigned to the authenticated user as the author.
     *
     * @authenticated
     *
     * @bodyParam title string required The article title. Must not exceed 255 characters. Example: Getting Started with Laravel
     * @bodyParam slug string optional The article slug. Auto-generated from title if not provided. Example: getting-started-with-laravel
     * @bodyParam excerpt string optional Short description. Max 500 characters. Example: Learn the basics of Laravel framework
     * @bodyParam content string required The full article content in HTML. Example: <p>Laravel is a web application framework...</p>
     * @bodyParam featured_image string optional URL to the featured image. Example: https://example.com/images/laravel.jpg
     * @bodyParam status string required Article status. Must be one of: draft, published, scheduled. Example: published
     * @bodyParam category_id integer required The category ID. Must exist in categories table. Example: 1
     * @bodyParam published_at datetime optional Publication date. Required if status is published. Example: 2024-01-01 12:00:00
     * @bodyParam is_featured boolean optional Mark as featured article. Default: false. Example: false
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "title": "Getting Started with Laravel",
     *     "slug": "getting-started-with-laravel",
     *     "excerpt": "Learn the basics of Laravel framework",
     *     "content": "<p>Laravel is a web application framework...</p>",
     *     "featured_image": "https://example.com/images/laravel.jpg",
     *     "status": "published",
     *     "published_at": "2024-01-01T12:00:00.000000Z",
     *     "author": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "category": {
     *       "id": 1,
     *       "name": "Technology",
     *       "slug": "technology"
     *     },
     *     "created_at": "2024-01-01T12:00:00.000000Z",
     *     "updated_at": "2024-01-01T12:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "title": ["The title field is required."],
     *     "content": ["The content field is required."],
     *     "category_id": ["The selected category id is invalid."]
     *   }
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        // Implementation
    }
}
```

## Resources

- [Scribe Documentation](https://scribe.knuckles.wtf/laravel/)
- [Scribe Annotations Reference](https://scribe.knuckles.wtf/laravel/reference/annotations)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Laravel API Resources](https://laravel.com/docs/eloquent-resources)

## Support

For questions about API documentation:
- Check the [Scribe documentation](https://scribe.knuckles.wtf/)
- Review existing documented endpoints in the codebase
- Ask in the team's development channel

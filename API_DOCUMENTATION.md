# API Documentation

## Overview

This document provides information about accessing and using the Technology News Platform API documentation.

## Accessing the Documentation

The API documentation is available in multiple formats:

### 1. Interactive HTML Documentation

Visit the interactive documentation at:
```
http://localhost/docs
```

The interactive documentation includes:
- Complete endpoint reference
- Request/response examples in multiple languages (Bash, JavaScript, PHP, Python)
- Try It Out functionality to test endpoints directly from the browser
- Authentication instructions
- Rate limiting information

### 2. Postman Collection

Download the Postman collection for easy API testing:
```
http://localhost/docs.postman
```

Or access it directly at:
```
storage/app/private/scribe/collection.json
```

Import this collection into Postman to:
- Test all API endpoints
- Use pre-configured requests
- Manage authentication tokens
- Organize requests by feature

### 3. OpenAPI Specification

Download the OpenAPI 3.0 specification:
```
http://localhost/docs.openapi
```

Or access it directly at:
```
storage/app/private/scribe/openapi.yaml
```

Use the OpenAPI spec to:
- Generate client libraries in any language
- Import into API testing tools (Insomnia, Paw, etc.)
- Validate API requests and responses
- Generate mock servers

## Documentation Structure

The API documentation is organized into the following groups:

### Core Resources
- **Posts/Articles**: Create, read, update, and delete articles
- **Categories**: Browse and filter by categories
- **Tags**: Manage and search tags
- **Users**: User profiles and account management

### Engagement Features
- **Comments**: Threaded commenting system
- **Bookmarks**: Save and organize articles
- **Reading Lists**: Create and share reading lists
- **Reactions**: Like and react to content

### Social Features
- **Follow System**: Follow users and authors
- **Activity Feed**: Track user activities
- **Social Sharing**: Share content on social platforms

### Advanced Features
- **Search**: Full-text search with filtering
- **Notifications**: Real-time notification system
- **Newsletter**: Subscription management
- **Recommendations**: Personalized content suggestions

### Administration
- **Moderation**: Content moderation tools
- **Analytics**: Usage metrics and reporting

## Authentication

The API uses Laravel Sanctum for authentication with Bearer tokens.

### Quick Start

1. **Create an API Token**:
```bash
curl -X POST http://localhost/api/v1/tokens \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your@email.com",
    "password": "your-password",
    "token_name": "My API Token",
    "abilities": ["*"]
  }'
```

2. **Use the Token**:
```bash
curl -X GET http://localhost/api/v1/users/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

For detailed authentication instructions, see the [Authentication section](http://localhost/docs#authenticating-requests) in the interactive documentation.

## Rate Limiting

API requests are rate-limited to ensure fair usage:

| Endpoint Type | Rate Limit |
|--------------|------------|
| Public endpoints | 60 requests/minute |
| Authenticated endpoints | 60 requests/minute |
| Write operations | 30 requests/minute |
| Search endpoints | 30 requests/minute |
| Token creation | 5 requests/minute |

Rate limit information is included in response headers:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Requests remaining
- `Retry-After`: Seconds to wait (when rate limited)

## Response Format

All API responses are in JSON format:

### Success Response
```json
{
  "data": {
    // Resource data
  }
}
```

### Paginated Response
```json
{
  "data": [...],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150
  }
}
```

### Error Response
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request succeeded |
| 201 | Created - Resource created successfully |
| 204 | No Content - Request succeeded with no response body |
| 400 | Bad Request - Invalid request parameters |
| 401 | Unauthorized - Authentication required or failed |
| 403 | Forbidden - Authenticated but not authorized |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Internal Server Error - Server error |

## Example Requests

### List Articles
```bash
curl -X GET "http://localhost/api/v1/articles?page=1&category=technology" \
  -H "Accept: application/json"
```

### Create Article (Authenticated)
```bash
curl -X POST "http://localhost/api/v1/articles" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "My New Article",
    "content": "<p>Article content here...</p>",
    "excerpt": "Short description",
    "category_id": 1,
    "status": "published"
  }'
```

### Get Current User
```bash
curl -X GET "http://localhost/api/v1/users/me" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Create Bookmark
```bash
curl -X POST "http://localhost/api/v1/bookmarks" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "post_id": 5
  }'
```

### Search Articles
```bash
curl -X GET "http://localhost/api/v1/search?q=laravel&category=technology" \
  -H "Accept: application/json"
```

## Regenerating Documentation

If you make changes to the API endpoints or documentation annotations, regenerate the docs:

```bash
php artisan scribe:generate
```

## Support

For API support or questions:
- Email: support@example.com
- Documentation: http://localhost/docs
- GitHub Issues: [Your Repository URL]

## Version

Current API Version: **v1**

All endpoints are prefixed with `/api/v1/`

## Additional Resources

- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Scribe Documentation](https://scribe.knuckles.wtf/)
- [OpenAPI Specification](https://swagger.io/specification/)

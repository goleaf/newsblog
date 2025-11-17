# Introduction

Welcome to the Technology News Platform API documentation. This RESTful API provides comprehensive access to articles, user management, comments, bookmarks, social features, and more.

<aside>
    <strong>Base URL</strong>: <code>http://localhost</code>
</aside>

## Overview

The API is organized around REST principles. It accepts JSON-encoded request bodies, returns JSON-encoded responses, and uses standard HTTP response codes, authentication, and verbs.

### Key Features

- **Articles Management**: Create, read, update, and delete articles
- **User Profiles**: Manage user accounts and profiles
- **Comments**: Threaded commenting system with reactions
- **Bookmarks & Reading Lists**: Save and organize articles
- **Social Features**: Follow users, track activity, share content
- **Search**: Full-text search with advanced filtering
- **Notifications**: Real-time notification system
- **Newsletter**: Subscription and preference management

### API Versioning

The current API version is **v1**. All endpoints are prefixed with `/api/v1/`.

### Rate Limiting

API requests are rate-limited to ensure fair usage:

- **Public endpoints**: 60 requests per minute
- **Authenticated endpoints**: 60 requests per minute
- **Write operations**: 30 requests per minute
- **Search endpoints**: 30 requests per minute
- **Token creation**: 5 requests per minute

Rate limit information is included in response headers:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Requests remaining in current window
- `Retry-After`: Seconds to wait before retrying (when rate limited)

### Response Format

All responses are returned in JSON format with the following structure:

**Success Response:**
```json
{
  "data": {
    // Resource data
  }
}
```

**Paginated Response:**
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
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

**Error Response:**
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

The API uses standard HTTP status codes:

- `200 OK`: Request succeeded
- `201 Created`: Resource created successfully
- `204 No Content`: Request succeeded with no response body
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required or failed
- `403 Forbidden`: Authenticated but not authorized
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

### Code Examples

<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>

### Support

For API support, please contact us at support@example.com or visit our developer forum.


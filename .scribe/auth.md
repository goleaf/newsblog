# Authentication

The API uses **Laravel Sanctum** for authentication with Bearer tokens. Some endpoints are public and don't require authentication, while others require you to be authenticated.

## Obtaining an API Token

To authenticate API requests, you need to obtain an API token. There are two ways to do this:

### 1. Create Token via API (Recommended)

Send a POST request to `/api/v1/tokens` with your login credentials:

```bash
curl -X POST http://localhost/api/v1/tokens \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "your-password",
    "token_name": "My API Token",
    "abilities": ["*"]
  }'
```

**Response:**
```json
{
  "data": {
    "token": "1|abcdef123456...",
    "token_name": "My API Token",
    "abilities": ["*"],
    "expires_at": null
  }
}
```

### 2. Generate Token via Dashboard

1. Log in to your account
2. Navigate to your profile settings
3. Go to the "API Tokens" section
4. Click "Generate New Token"
5. Copy the generated token (it will only be shown once)

## Using Your Token

Include your API token in the `Authorization` header of your requests using the Bearer scheme:

```bash
curl -X GET http://localhost/api/v1/users/me \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

## Token Abilities (Permissions)

Tokens can be created with specific abilities to limit what actions they can perform:

- `*`: Full access to all endpoints
- `read`: Read-only access to resources
- `write`: Create and update resources
- `delete`: Delete resources
- `articles:read`: Read articles only
- `articles:write`: Create and update articles
- `comments:write`: Create and update comments
- `bookmarks:manage`: Manage bookmarks

**Example: Creating a read-only token**
```json
{
  "email": "user@example.com",
  "password": "your-password",
  "token_name": "Read-Only Token",
  "abilities": ["read", "articles:read"]
}
```

## Managing Tokens

### List Your Tokens

```bash
GET /api/v1/tokens
```

### Update Token Abilities

```bash
PUT /api/v1/tokens/{tokenId}
```

### Revoke a Token

```bash
DELETE /api/v1/tokens/{tokenId}
```

## Token Security Best Practices

1. **Keep tokens secure**: Never share your API tokens or commit them to version control
2. **Use specific abilities**: Create tokens with only the permissions needed
3. **Rotate tokens regularly**: Periodically revoke old tokens and create new ones
4. **Use HTTPS**: Always use HTTPS in production to prevent token interception
5. **Revoke compromised tokens**: Immediately revoke any token you suspect has been compromised

## Public vs Authenticated Endpoints

### Public Endpoints (No Authentication Required)

- `GET /api/v1/articles` - List articles
- `GET /api/v1/articles/{id}` - Get single article
- `GET /api/v1/categories` - List categories
- `GET /api/v1/tags` - List tags
- `GET /api/v1/search` - Search articles
- `GET /api/v1/users/{id}` - View public user profile

### Authenticated Endpoints (Token Required)

- `POST /api/v1/articles` - Create article
- `PUT /api/v1/articles/{id}` - Update article
- `DELETE /api/v1/articles/{id}` - Delete article
- `GET /api/v1/users/me` - Get current user
- `POST /api/v1/bookmarks` - Create bookmark
- `POST /api/v1/comments` - Create comment
- All notification endpoints
- All reading list endpoints
- All follow/unfollow endpoints

## Error Responses

### 401 Unauthorized

Returned when authentication is required but not provided or invalid:

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

Returned when authenticated but lacking required permissions:

```json
{
  "message": "This action is unauthorized."
}
```

### 429 Too Many Requests

Returned when rate limit is exceeded:

```json
{
  "message": "Too Many Attempts."
}
```

**Response Headers:**
- `X-RateLimit-Limit`: 60
- `X-RateLimit-Remaining`: 0
- `Retry-After`: 60

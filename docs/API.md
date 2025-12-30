# WonderWay API Documentation

## Base Information

- **Base URL**: `http://localhost:8000/api`
- **Production URL**: `https://api.wonderway.com`
- **Version**: 3.0.0
- **Authentication**: Bearer Token (JWT)

## Authentication

### Headers
```http
Authorization: Bearer {your-jwt-token}
Content-Type: application/json
Accept: application/json
```

### Auth Endpoints

#### Register
```http
POST /api/register
```
**Body:**
```json
{
  "name": "John Doe",
  "username": "johndoe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login
```http
POST /api/login
```
**Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

#### Get User Info
```http
GET /api/me
```

## Core Features

### Posts

#### Get Timeline
```http
GET /api/timeline
```

#### Create Post
```http
POST /api/posts
```
**Body:**
```json
{
  "content": "Hello World!",
  "image": "base64_image_data",
  "is_draft": false
}
```

#### Like Post
```http
POST /api/posts/{id}/like
```

#### Quote Post
```http
POST /api/posts/{id}/quote
```
**Body:**
```json
{
  "content": "Great post!"
}
```

### Comments

#### Get Post Comments
```http
GET /api/posts/{id}/comments
```

#### Create Comment
```http
POST /api/posts/{id}/comments
```
**Body:**
```json
{
  "content": "Nice post!"
}
```

### Users & Social

#### Follow User
```http
POST /api/users/{id}/follow
```

#### Get User Profile
```http
GET /api/users/{id}
```

#### Search Users
```http
GET /api/search/users?q=john
```

### Messaging

#### Get Conversations
```http
GET /api/messages/conversations
```

#### Send Message
```http
POST /api/messages/users/{id}
```
**Body:**
```json
{
  "content": "Hello there!",
  "media_url": "optional_media_url"
}
```

### Advanced Features

#### A/B Testing (User Participation)
```http
POST /api/ab-tests/assign
```
**Body:**
```json
{
  "test_name": "button_color_test"
}
```

#### Track A/B Test Event
```http
POST /api/ab-tests/track
```
**Body:**
```json
{
  "test_name": "button_color_test",
  "event_type": "click",
  "event_data": {"button": "signup"}
}
```

#### Analytics Tracking
```http
POST /api/analytics/track
```
**Body:**
```json
{
  "event_type": "page_view",
  "event_data": {"page": "timeline"}
}
```

## Response Format

### Success Response
```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation successful"
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["The email field is required."]
    }
  }
}
```

## Rate Limiting

- **General API**: 60 requests per minute
- **Authentication**: 5 requests per minute
- **Likes**: 60 requests per minute
- **Messages**: 60 requests per minute

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

## Admin Panel

**Note**: Admin functionality is now handled through Filament PHP panel at `/admin`, not via API endpoints.

- **URL**: `http://localhost:8000/admin`
- **Features**: User management, content moderation, analytics, A/B testing, advertisements
- **Authentication**: Separate admin login system

## WebSocket Events

### Real-time Timeline
```javascript
// Listen for new posts
Echo.channel('timeline')
    .listen('PostPublished', (e) => {
        console.log('New post:', e.post);
    });
```

### Private Messages
```javascript
// Listen for messages
Echo.private(`user.${userId}`)
    .listen('MessageSent', (e) => {
        console.log('New message:', e.message);
    });
```

## Testing

Use the following test credentials:
- **Regular User**: `test@example.com` / `<test-password>`
- **Admin User**: `<admin-email>` / `<admin-password>`
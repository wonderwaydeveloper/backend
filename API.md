# Clevlance API Documentation

Base URL: `https://api.clevlance.com`

## Authentication

All API requests require authentication using Bearer token (except public endpoints).

```http
Authorization: Bearer {your_token}
```

---

## 🔐 Authentication Endpoints

### Register (Multi-Step)

**Step 1: Email/Phone**
```http
POST /api/auth/register/step1
Content-Type: application/json

{
  "email": "user@example.com",
  "phone": "+1234567890"
}
```

**Step 2: Verification Code**
```http
POST /api/auth/register/step2
Content-Type: application/json

{
  "email": "user@example.com",
  "code": "123456"
}
```

**Step 3: Complete Profile**
```http
POST /api/auth/register/step3
Content-Type: application/json

{
  "name": "John Doe",
  "username": "johndoe",
  "password": "SecurePass123",
  "birth_date": "1990-01-01"
}
```

### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123",
  "device_name": "iPhone 14"
}

Response 200:
{
  "token": "1|abc123...",
  "user": {...},
  "expires_at": "2024-01-01T00:00:00Z"
}
```

### Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}

Response 200:
{
  "message": "Logged out successfully"
}
```

### Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}

Response 200:
{
  "id": 1,
  "name": "John Doe",
  "username": "johndoe",
  "email": "user@example.com",
  "avatar": "https://...",
  "is_verified": true,
  "is_premium": false,
  "followers_count": 150,
  "following_count": 200
}
```

### Two-Factor Authentication

**Enable 2FA**
```http
POST /api/auth/2fa/enable
Authorization: Bearer {token}

Response 200:
{
  "qr_code": "data:image/png;base64,...",
  "secret": "ABC123..."
}
```

**Verify 2FA**
```http
POST /api/auth/2fa/verify
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "123456"
}
```

---

## 📝 Posts Endpoints

### Create Post
```http
POST /api/posts
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Hello World!",
  "media_ids": [1, 2],
  "hashtags": ["tech", "laravel"],
  "visibility": "public",
  "reply_settings": "everyone"
}

Response 201:
{
  "id": 123,
  "content": "Hello World!",
  "user": {...},
  "media": [...],
  "hashtags": [...],
  "created_at": "2024-01-01T00:00:00Z"
}
```

### Get Timeline
```http
GET /api/timeline?page=1&limit=20
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": 123,
      "content": "...",
      "user": {...},
      "likes_count": 10,
      "comments_count": 5,
      "reposts_count": 2
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 100
  }
}
```

### Get Single Post
```http
GET /api/posts/{post_id}
Authorization: Bearer {token}

Response 200:
{
  "id": 123,
  "content": "...",
  "user": {...},
  "media": [...],
  "likes_count": 10,
  "is_liked": false,
  "is_bookmarked": false
}
```

### Update Post
```http
PUT /api/posts/{post_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Updated content"
}
```

### Delete Post
```http
DELETE /api/posts/{post_id}
Authorization: Bearer {token}

Response 204: No Content
```

### Like Post
```http
POST /api/posts/{post_id}/like
Authorization: Bearer {token}

Response 200:
{
  "message": "Post liked",
  "likes_count": 11
}
```

### Unlike Post
```http
DELETE /api/posts/{post_id}/like
Authorization: Bearer {token}

Response 200:
{
  "message": "Post unliked",
  "likes_count": 10
}
```

### Repost
```http
POST /api/posts/{post_id}/repost
Authorization: Bearer {token}

Response 201:
{
  "id": 124,
  "original_post": {...}
}
```

### Quote Post
```http
POST /api/posts/{post_id}/quote
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Great post!",
  "quoted_post_id": 123
}
```

### Bookmark Post
```http
POST /api/posts/{post_id}/bookmark
Authorization: Bearer {token}

Response 200:
{
  "message": "Post bookmarked"
}
```

---

## 💬 Comments Endpoints

### Get Comments
```http
GET /api/posts/{post_id}/comments?page=1
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": 1,
      "content": "Nice post!",
      "user": {...},
      "likes_count": 5,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Create Comment
```http
POST /api/posts/{post_id}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Great post!",
  "parent_id": null
}
```

### Delete Comment
```http
DELETE /api/comments/{comment_id}
Authorization: Bearer {token}

Response 204: No Content
```

---

## 👥 User & Profile Endpoints

### Get User Profile
```http
GET /api/users/{username}
Authorization: Bearer {token}

Response 200:
{
  "id": 1,
  "name": "John Doe",
  "username": "johndoe",
  "bio": "...",
  "avatar": "...",
  "followers_count": 150,
  "following_count": 200,
  "posts_count": 50,
  "is_following": false,
  "is_followed_by": false
}
```

### Update Profile
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "John Doe",
  "bio": "Software Developer",
  "location": "San Francisco",
  "website": "https://johndoe.com"
}
```

### Follow User
```http
POST /api/users/{user_id}/follow
Authorization: Bearer {token}

Response 200:
{
  "message": "User followed",
  "is_following": true
}
```

### Unfollow User
```http
POST /api/users/{user_id}/unfollow
Authorization: Bearer {token}

Response 200:
{
  "message": "User unfollowed"
}
```

### Get Followers
```http
GET /api/users/{user_id}/followers?page=1
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": 2,
      "name": "Jane Doe",
      "username": "janedoe",
      "avatar": "..."
    }
  ]
}
```

### Get Following
```http
GET /api/users/{user_id}/following?page=1
Authorization: Bearer {token}
```

### Block User
```http
POST /api/users/{user_id}/block
Authorization: Bearer {token}

Response 200:
{
  "message": "User blocked"
}
```

### Mute User
```http
POST /api/users/{user_id}/mute
Authorization: Bearer {token}

Response 200:
{
  "message": "User muted"
}
```

---

## 🔍 Search Endpoints

### Search Posts
```http
GET /api/search/posts?q=laravel&page=1
Authorization: Bearer {token}

Query Parameters:
- q: Search query (required)
- user_id: Filter by user
- has_media: true/false
- date_from: YYYY-MM-DD
- date_to: YYYY-MM-DD
- min_likes: Minimum likes
- sort: relevance|latest|popular

Response 200:
{
  "data": [...],
  "meta": {...}
}
```

### Search Users
```http
GET /api/search/users?q=john&page=1
Authorization: Bearer {token}

Query Parameters:
- q: Search query (required)
- verified: true/false
- min_followers: Minimum followers
- sort: relevance|followers|newest
```

### Search Hashtags
```http
GET /api/search/hashtags?q=tech&page=1
Authorization: Bearer {token}

Query Parameters:
- q: Search query (required)
- min_posts: Minimum posts
- sort: relevance|popular|recent
```

### Search All
```http
GET /api/search/all?q=laravel
Authorization: Bearer {token}

Response 200:
{
  "posts": [...],
  "users": [...],
  "hashtags": [...]
}
```

---

## 📈 Trending Endpoints

### Get Trending Hashtags
```http
GET /api/trending/hashtags?limit=10&timeframe=24
Authorization: Bearer {token}

Query Parameters:
- limit: Number of results (default: 10)
- timeframe: Hours to analyze (default: 24)

Response 200:
{
  "data": [
    {
      "id": 1,
      "name": "laravel",
      "posts_count": 1500,
      "velocity": 150,
      "trend_direction": "up"
    }
  ]
}
```

### Get Trending Posts
```http
GET /api/trending/posts?limit=20&timeframe=24
Authorization: Bearer {token}
```

### Get Trending Users
```http
GET /api/trending/users?limit=10&timeframe=168
Authorization: Bearer {token}
```

### Get Personalized Trending
```http
GET /api/trending/personalized?limit=10
Authorization: Bearer {token}

Response 200:
{
  "data": [...]
}
```

---

## 💌 Messaging Endpoints

### Get Conversations
```http
GET /api/messages/conversations?page=1
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "user": {...},
      "last_message": {...},
      "unread_count": 3
    }
  ]
}
```

### Get Messages with User
```http
GET /api/messages/users/{user_id}?page=1
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": 1,
      "content": "Hello!",
      "sender_id": 1,
      "is_read": true,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Send Message
```http
POST /api/messages/users/{user_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Hello!",
  "media_id": null
}

Response 201:
{
  "id": 1,
  "content": "Hello!",
  "created_at": "2024-01-01T00:00:00Z"
}
```

### Mark as Read
```http
POST /api/messages/{message_id}/read
Authorization: Bearer {token}

Response 200:
{
  "message": "Marked as read"
}
```

### Typing Indicator
```http
POST /api/messages/users/{user_id}/typing
Authorization: Bearer {token}

Response 200:
{
  "message": "Typing indicator sent"
}
```

---

## 🔔 Notifications Endpoints

### Get Notifications
```http
GET /api/notifications?page=1
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": "uuid",
      "type": "like",
      "data": {...},
      "read_at": null,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ]
}
```

### Get Unread Count
```http
GET /api/notifications/unread-count
Authorization: Bearer {token}

Response 200:
{
  "count": 5
}
```

### Mark as Read
```http
POST /api/notifications/{notification_id}/read
Authorization: Bearer {token}

Response 200:
{
  "message": "Marked as read"
}
```

### Mark All as Read
```http
POST /api/notifications/mark-all-read
Authorization: Bearer {token}

Response 200:
{
  "message": "All notifications marked as read"
}
```

---

## 📊 Analytics Endpoints

### Get User Analytics
```http
GET /api/analytics/user
Authorization: Bearer {token}

Response 200:
{
  "followers_growth": [...],
  "posts_performance": {...},
  "engagement_rate": 5.2
}
```

### Get Post Analytics
```http
GET /api/analytics/posts/{post_id}
Authorization: Bearer {token}

Response 200:
{
  "views": 1000,
  "likes": 50,
  "comments": 10,
  "reposts": 5,
  "engagement_rate": 6.5
}
```

---

## 🏘️ Communities Endpoints

### Get Communities
```http
GET /api/communities?page=1
Authorization: Bearer {token}
```

### Create Community
```http
POST /api/communities
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Laravel Developers",
  "description": "...",
  "is_private": false
}
```

### Join Community
```http
POST /api/communities/{community_id}/join
Authorization: Bearer {token}

Response 200:
{
  "message": "Joined community"
}
```

### Get Community Posts
```http
GET /api/communities/{community_id}/posts?page=1
Authorization: Bearer {token}
```

---

## 💰 Monetization Endpoints

### Get Premium Plans
```http
GET /api/monetization/premium/plans
Authorization: Bearer {token}

Response 200:
{
  "plans": [
    {
      "id": 1,
      "name": "Premium",
      "price": 9.99,
      "features": [...]
    }
  ]
}
```

### Subscribe to Premium
```http
POST /api/monetization/premium/subscribe
Authorization: Bearer {token}
Content-Type: application/json

{
  "plan_id": 1,
  "payment_method": "card"
}
```

### Get Creator Fund Analytics
```http
GET /api/monetization/creator-fund/analytics
Authorization: Bearer {token}

Response 200:
{
  "total_earnings": 150.50,
  "this_month": 45.20,
  "pending_payout": 100.30
}
```

---

## 📤 Media Upload Endpoints

### Upload Image
```http
POST /api/media/upload/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: (binary)

Response 201:
{
  "id": 1,
  "url": "https://...",
  "type": "image",
  "size": 1024000
}
```

### Upload Video
```http
POST /api/media/upload/video
Authorization: Bearer {token}
Content-Type: multipart/form-data

file: (binary)

Response 201:
{
  "id": 2,
  "url": "https://...",
  "type": "video",
  "duration": 60
}
```

---

## 🏥 Health Check

### Health Status
```http
GET /api/health

Response 200:
{
  "status": "ok",
  "database": "ok",
  "cache": "ok",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

---

## ⚠️ Error Responses

### 400 Bad Request
```json
{
  "error": "Validation failed",
  "message": "The given data was invalid",
  "errors": {
    "email": ["The email field is required"]
  }
}
```

### 401 Unauthorized
```json
{
  "error": "Unauthenticated",
  "message": "Please login"
}
```

### 403 Forbidden
```json
{
  "error": "Forbidden",
  "message": "You don't have permission to perform this action"
}
```

### 404 Not Found
```json
{
  "error": "Not Found",
  "message": "Resource not found"
}
```

### 429 Too Many Requests
```json
{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded",
  "retry_after": 60
}
```

### 500 Internal Server Error
```json
{
  "error": "Server Error",
  "message": "An unexpected error occurred"
}
```

---

## 🔒 Rate Limiting

Different endpoints have different rate limits:

- **Authentication**: 5 requests/15 minutes
- **Posts**: 300 requests/3 hours
- **Search**: 180 requests/15 minutes
- **Trending**: 75 requests/15 minutes
- **Messages**: 60 requests/minute
- **Follow/Unfollow**: 400 requests/day

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

---

## 📝 Pagination

All list endpoints support pagination:

```http
GET /api/posts?page=1&limit=20
```

Response includes meta information:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
  }
}
```

---

## 🔄 WebSocket Events

Connect to: `wss://api.clevlance.com:8080`

### Subscribe to Channels
```javascript
// Private channel
Echo.private(`user.${userId}`)
  .listen('NewMessage', (e) => {
    console.log(e.message);
  });

// Presence channel
Echo.join(`online`)
  .here((users) => {
    console.log(users);
  })
  .joining((user) => {
    console.log(user.name);
  })
  .leaving((user) => {
    console.log(user.name);
  });
```

### Available Events
- `NewMessage` - New direct message
- `NewNotification` - New notification
- `PostLiked` - Post was liked
- `NewFollower` - New follower
- `UserOnline` - User came online
- `UserOffline` - User went offline

---

For more information, visit [https://docs.clevlance.com](https://docs.clevlance.com)

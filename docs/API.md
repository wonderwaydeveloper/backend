# WonderWay API Documentation

## مقدمه

WonderWay API یک RESTful API است که امکان دسترسی به تمام قابلیتهای پلتفرم شبکه اجتماعی را فراهم میکند. این API با استفاده از Laravel 12 و بر اساس اصول REST طراحی شده است.

## Base URL

```
Production: https://api.wonderway.com/api
Staging: https://staging-api.wonderway.com/api
Development: http://localhost:8000/api
```

## احراز هویت

### Bearer Token Authentication

تمام endpoint های محافظت شده نیاز به Bearer Token در header دارند:

```http
Authorization: Bearer {your-jwt-token}
```

### دریافت Token

#### ثبت نام
```http
POST /register
Content-Type: application/json

{
    "name": "نام کاربر",
    "username": "username",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "date_of_birth": "1990-01-01"
}
```

**پاسخ موفق:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "نام کاربر",
            "username": "username",
            "email": "user@example.com",
            "avatar": null,
            "is_private": false,
            "created_at": "2024-01-01T00:00:00.000000Z"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    },
    "message": "کاربر با موفقیت ثبت شد"
}
```

#### ورود
```http
POST /login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123",
    "two_factor_code": "123456" // اختیاری
}
```

## Endpoints اصلی

### کاربران (Users)

#### دریافت پروفایل کاربر
```http
GET /users/{id}
Authorization: Bearer {token}
```

**پاسخ:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "نام کاربر",
        "username": "username",
        "bio": "بیوگرافی کاربر",
        "avatar": "https://cdn.wonderway.com/avatars/user1.jpg",
        "cover": "https://cdn.wonderway.com/covers/user1.jpg",
        "followers_count": 150,
        "following_count": 89,
        "posts_count": 45,
        "is_following": true,
        "is_private": false,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

#### بروزرسانی پروفایل
```http
PUT /profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "نام جدید",
    "bio": "بیوگرافی جدید",
    "avatar": "base64_encoded_image_or_url"
}
```

#### فالو کردن کاربر
```http
POST /users/{id}/follow
Authorization: Bearer {token}
```

**پاسخ:**
```json
{
    "success": true,
    "data": {
        "is_following": true,
        "follow_request_sent": false
    },
    "message": "کاربر با موفقیت فالو شد"
}
```

### پستها (Posts)

#### دریافت تایم لاین
```http
GET /posts?page=1&per_page=20&type=timeline
Authorization: Bearer {token}
```

**پارامترهای Query:**
- `page`: شماره صفحه (پیشفرض: 1)
- `per_page`: تعداد پست در هر صفحه (پیشفرض: 20، حداکثر: 50)
- `type`: نوع تایم لاین (`timeline`, `following`, `trending`)
- `hashtag`: فیلتر بر اساس هشتگ
- `user_id`: فیلتر بر اساس کاربر

**پاسخ:**
```json
{
    "success": true,
    "data": {
        "posts": [
            {
                "id": 1,
                "content": "محتوای پست",
                "image": "https://cdn.wonderway.com/posts/image1.jpg",
                "video": null,
                "gif_url": null,
                "likes_count": 25,
                "comments_count": 8,
                "reposts_count": 3,
                "views_count": 150,
                "is_liked": true,
                "is_reposted": false,
                "is_bookmarked": false,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "user": {
                    "id": 2,
                    "name": "نام کاربر",
                    "username": "username",
                    "avatar": "https://cdn.wonderway.com/avatars/user2.jpg"
                },
                "hashtags": [
                    {
                        "id": 1,
                        "name": "تکنولوژی",
                        "slug": "technology"
                    }
                ],
                "poll": null,
                "quoted_post": null
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100,
            "last_page": 5,
            "has_more": true
        }
    }
}
```

#### ایجاد پست جدید
```http
POST /posts
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "محتوای پست جدید #تکنولوژی",
    "image": "base64_encoded_image",
    "video": "base64_encoded_video",
    "gif_url": "https://giphy.com/gifs/example",
    "reply_settings": "everyone", // everyone, following, mentioned
    "is_draft": false,
    "scheduled_at": null, // برای پست زمانبندی شده
    "poll": {
        "question": "سوال نظرسنجی",
        "options": ["گزینه 1", "گزینه 2", "گزینه 3"],
        "duration": 24 // ساعت
    }
}
```

#### لایک کردن پست
```http
POST /posts/{id}/like
Authorization: Bearer {token}
```

**پاسخ:**
```json
{
    "success": true,
    "data": {
        "is_liked": true,
        "likes_count": 26
    },
    "message": "پست لایک شد"
}
```

#### ریپست کردن
```http
POST /posts/{id}/repost
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "نظر شما در مورد ریپست", // اختیاری
    "type": "repost" // repost یا quote
}
```

### کامنتها (Comments)

#### دریافت کامنتهای پست
```http
GET /posts/{id}/comments?page=1&per_page=20
Authorization: Bearer {token}
```

#### اضافه کردن کامنت
```http
POST /posts/{id}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "متن کامنت",
    "parent_id": null, // برای پاسخ به کامنت دیگر
    "image": "base64_encoded_image" // اختیاری
}
```

### پیامرسانی (Messages)

#### دریافت مکالمات
```http
GET /messages/conversations?page=1
Authorization: Bearer {token}
```

**پاسخ:**
```json
{
    "success": true,
    "data": {
        "conversations": [
            {
                "id": 1,
                "participant": {
                    "id": 2,
                    "name": "نام کاربر",
                    "username": "username",
                    "avatar": "https://cdn.wonderway.com/avatars/user2.jpg",
                    "is_online": true
                },
                "last_message": {
                    "id": 100,
                    "content": "آخرین پیام",
                    "created_at": "2024-01-01T12:00:00.000000Z",
                    "is_read": false
                },
                "unread_count": 3,
                "updated_at": "2024-01-01T12:00:00.000000Z"
            }
        ]
    }
}
```

#### ارسال پیام
```http
POST /messages/users/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "متن پیام",
    "image": "base64_encoded_image", // اختیاری
    "reply_to": null // ID پیام برای پاسخ
}
```

### جستجو (Search)

#### جستجوی عمومی
```http
GET /search/all?q=کلمه+کلیدی&type=all&page=1
Authorization: Bearer {token}
```

**پارامترهای Query:**
- `q`: کلمه کلیدی جستجو
- `type`: نوع جستجو (`all`, `users`, `posts`, `hashtags`)
- `page`: شماره صفحه
- `filters`: فیلترهای اضافی (JSON)

#### جستجوی پیشرفته
```http
POST /search/advanced
Authorization: Bearer {token}
Content-Type: application/json

{
    "query": "کلمه کلیدی",
    "filters": {
        "user_id": 123,
        "hashtags": ["تکنولوژی", "هوش_مصنوعی"],
        "date_from": "2024-01-01",
        "date_to": "2024-12-31",
        "has_media": true,
        "language": "fa"
    },
    "sort": "relevance", // relevance, date, popularity
    "page": 1,
    "per_page": 20
}
```

### اعلانات (Notifications)

#### دریافت اعلانات
```http
GET /notifications?page=1&type=all&unread_only=false
Authorization: Bearer {token}
```

**انواع اعلانات:**
- `like`: لایک پست
- `comment`: کامنت جدید
- `follow`: فالو جدید
- `mention`: منشن شدن
- `repost`: ریپست پست
- `message`: پیام جدید

#### خواندن اعلان
```http
POST /notifications/{id}/read
Authorization: Bearer {token}
```

### آپلود فایل (Media Upload)

#### آپلود تصویر
```http
POST /media/upload/image
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "image": file,
    "type": "post", // post, avatar, cover, message
    "resize": true,
    "quality": 80
}
```

**پاسخ:**
```json
{
    "success": true,
    "data": {
        "url": "https://cdn.wonderway.com/images/abc123.jpg",
        "thumbnail": "https://cdn.wonderway.com/thumbnails/abc123.jpg",
        "size": 1024000,
        "dimensions": {
            "width": 1920,
            "height": 1080
        }
    }
}
```

#### آپلود ویدیو
```http
POST /media/upload/video
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "video": file,
    "thumbnail": file, // اختیاری
    "quality": "720p" // 480p, 720p, 1080p
}
```

## Real-time Features

### WebSocket Connection

برای اتصال به WebSocket:

```javascript
const socket = new WebSocket('wss://api.wonderway.com:8080');

// احراز هویت
socket.send(JSON.stringify({
    type: 'auth',
    token: 'your-jwt-token'
}));

// دریافت پیام
socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('Received:', data);
};
```

### رویدادهای Real-time

#### پیام جدید
```json
{
    "type": "message.new",
    "data": {
        "id": 123,
        "content": "متن پیام",
        "sender": {
            "id": 2,
            "name": "نام فرستنده",
            "avatar": "url"
        },
        "created_at": "2024-01-01T12:00:00.000000Z"
    }
}
```

#### اعلان جدید
```json
{
    "type": "notification.new",
    "data": {
        "id": 456,
        "type": "like",
        "message": "کاربری پست شما را لایک کرد",
        "user": {
            "id": 3,
            "name": "نام کاربر",
            "avatar": "url"
        },
        "created_at": "2024-01-01T12:00:00.000000Z"
    }
}
```

## کدهای خطا

### HTTP Status Codes

- `200`: موفقیتآمیز
- `201`: ایجاد شده
- `400`: درخواست نامعتبر
- `401`: عدم احراز هویت
- `403`: عدم دسترسی
- `404`: یافت نشد
- `422`: خطای اعتبارسنجی
- `429`: محدودیت نرخ درخواست
- `500`: خطای سرور

### Error Response Format

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "دادههای ورودی نامعتبر است",
        "details": {
            "email": ["فرمت ایمیل نامعتبر است"],
            "password": ["رمز عبور باید حداقل 8 کاراکتر باشد"]
        }
    }
}
```

### کدهای خطای سفارشی

- `AUTH_001`: اطلاعات ورود نامعتبر
- `AUTH_002`: حساب کاربری غیرفعال
- `AUTH_003`: نیاز به تأیید دومرحلهای
- `USER_001`: کاربر یافت نشد
- `USER_002`: نام کاربری تکراری
- `POST_001`: پست یافت نشد
- `POST_002`: عدم دسترسی به پست
- `MEDIA_001`: فرمت فایل پشتیبانی نمیشود
- `MEDIA_002`: حجم فایل بیش از حد مجاز
- `RATE_001`: محدودیت نرخ درخواست

## Rate Limiting

### محدودیتهای عمومی

- **API عمومی**: 60 درخواست در دقیقه
- **ورود**: 5 تلاش در 15 دقیقه
- **ثبتنام**: 3 تلاش در ساعت
- **آپلود فایل**: 10 فایل در دقیقه
- **ارسال پیام**: 60 پیام در دقیقه

### Headers محدودیت

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1640995200
```

## Pagination

### Query Parameters

```http
GET /posts?page=2&per_page=20
```

### Response Format

```json
{
    "data": [...],
    "pagination": {
        "current_page": 2,
        "per_page": 20,
        "total": 100,
        "last_page": 5,
        "from": 21,
        "to": 40,
        "has_more": true,
        "links": {
            "first": "https://api.wonderway.com/posts?page=1",
            "last": "https://api.wonderway.com/posts?page=5",
            "prev": "https://api.wonderway.com/posts?page=1",
            "next": "https://api.wonderway.com/posts?page=3"
        }
    }
}
```

## Filtering و Sorting

### مثال فیلترینگ

```http
GET /posts?user_id=123&hashtag=تکنولوژی&has_media=true&date_from=2024-01-01
```

### مثال مرتبسازی

```http
GET /posts?sort=created_at&order=desc
```

**گزینههای مرتبسازی:**
- `created_at`: تاریخ ایجاد
- `likes_count`: تعداد لایک
- `comments_count`: تعداد کامنت
- `views_count`: تعداد بازدید

## Webhooks

### تنظیم Webhook

```http
POST /webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
    "url": "https://your-app.com/webhook",
    "events": ["post.created", "user.followed"],
    "secret": "your-webhook-secret"
}
```

### رویدادهای Webhook

- `post.created`: پست جدید ایجاد شد
- `post.liked`: پست لایک شد
- `user.followed`: کاربر فالو شد
- `message.sent`: پیام ارسال شد

### Payload نمونه

```json
{
    "event": "post.created",
    "data": {
        "post": {
            "id": 123,
            "content": "محتوای پست",
            "user_id": 456,
            "created_at": "2024-01-01T12:00:00.000000Z"
        }
    },
    "timestamp": "2024-01-01T12:00:00.000000Z"
}
```

## SDK و کتابخانهها

### JavaScript SDK

```javascript
import WonderWayAPI from '@wonderway/js-sdk';

const api = new WonderWayAPI({
    baseURL: 'https://api.wonderway.com',
    token: 'your-jwt-token'
});

// دریافت تایم لاین
const timeline = await api.posts.getTimeline();

// ایجاد پست
const post = await api.posts.create({
    content: 'محتوای پست جدید'
});
```

### PHP SDK

```php
use WonderWay\SDK\Client;

$client = new Client([
    'base_url' => 'https://api.wonderway.com',
    'token' => 'your-jwt-token'
]);

// دریافت تایم لاین
$timeline = $client->posts()->getTimeline();

// ایجاد پست
$post = $client->posts()->create([
    'content' => 'محتوای پست جدید'
]);
```

## مثالهای کاربردی

### ایجاد اپلیکیشن ساده

```javascript
// 1. احراز هویت
const loginResponse = await fetch('/api/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123'
    })
});

const { token } = await loginResponse.json();

// 2. دریافت تایم لاین
const timelineResponse = await fetch('/api/posts', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});

const timeline = await timelineResponse.json();

// 3. ایجاد پست جدید
const createPostResponse = await fetch('/api/posts', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        content: 'پست جدید من!'
    })
});
```

## تست API

### با cURL

```bash
# ورود
curl -X POST https://api.wonderway.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# دریافت تایم لاین
curl -X GET https://api.wonderway.com/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN"

# ایجاد پست
curl -X POST https://api.wonderway.com/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"content":"پست جدید!"}'
```

### با Postman

1. Collection را از [اینجا](https://api.wonderway.com/postman-collection.json) دانلود کنید
2. در Postman import کنید
3. متغیر `{{token}}` را تنظیم کنید
4. درخواستها را اجرا کنید

## پشتیبانی

برای سوالات و مشکلات API:

- **مستندات**: https://docs.wonderway.com
- **GitHub Issues**: https://github.com/wonderway/api/issues
- **ایمیل**: api-support@wonderway.com
- **Discord**: https://discord.gg/wonderway-dev

## تغییرات و بروزرسانیها

### نسخه 1.2.0 (2024-01-15)
- اضافه شدن Community Notes
- بهبود Performance
- رفع باگهای امنیتی

### نسخه 1.1.0 (2024-01-01)
- اضافه شدن Spaces (اتاقهای صوتی)
- پشتیبانی از Webhooks
- بهبود Real-time messaging

### نسخه 1.0.0 (2023-12-01)
- انتشار اولیه API
- قابلیتهای اصلی پلتفرم
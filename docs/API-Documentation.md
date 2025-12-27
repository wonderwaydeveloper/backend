# WonderWay API Documentation

> مستندات کامل API های WonderWay Backend

## 🔐 احراز هویت

### ثبت نام
```http
POST /api/auth/register
Content-Type: application/json

{
  "name": "نام کاربر",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### ورود
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

### خروج
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

### فعالسازی 2FA
```http
POST /api/auth/2fa/enable
Authorization: Bearer {token}
Content-Type: application/json

{
  "password": "current_password"
}
```

---

## 📝 پست‌ها

### دریافت تایم‌لاین
```http
GET /api/posts
Authorization: Bearer {token}
```

### ایجاد پست
```http
POST /api/posts
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "محتوای پست",
  "image": "path/to/image.jpg",
  "community_id": 1,
  "is_draft": false
}
```

### نمایش پست
```http
GET /api/posts/{id}
Authorization: Bearer {token}
```

### ویرایش پست
```http
PUT /api/posts/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "محتوای جدید"
}
```

### حذف پست
```http
DELETE /api/posts/{id}
Authorization: Bearer {token}
```

### لایک پست
```http
POST /api/posts/{id}/like
Authorization: Bearer {token}
```

### آنلایک پست
```http
DELETE /api/posts/{id}/like
Authorization: Bearer {token}
```

### ریپست
```http
POST /api/posts/{id}/repost
Authorization: Bearer {token}
Content-Type: application/json

{
  "quote": "نظر شما در مورد ریپست"
}
```

---

## 💬 کامنت‌ها

### دریافت کامنت‌های پست
```http
GET /api/posts/{postId}/comments
Authorization: Bearer {token}
```

### ایجاد کامنت
```http
POST /api/posts/{postId}/comments
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "متن کامنت"
}
```

### حذف کامنت
```http
DELETE /api/comments/{id}
Authorization: Bearer {token}
```

---

## 👥 کامیونیتی‌ها

### لیست کامیونیتی‌ها
```http
GET /api/communities
Authorization: Bearer {token}

Query Parameters:
- search: جستجو در نام و توضیحات
- privacy: public|private
- page: شماره صفحه
- per_page: تعداد در صفحه (پیش‌فرض: 20)
```

### ایجاد کامیونیتی
```http
POST /api/communities
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "نام کامیونیتی",
  "description": "توضیحات کامیونیتی",
  "privacy": "public",
  "rules": ["قانون 1", "قانون 2"]
}
```

### نمایش کامیونیتی
```http
GET /api/communities/{id}
Authorization: Bearer {token}
```

### ویرایش کامیونیتی
```http
PUT /api/communities/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "نام جدید",
  "description": "توضیحات جدید"
}
```

### حذف کامیونیتی
```http
DELETE /api/communities/{id}
Authorization: Bearer {token}
```

### پیوستن به کامیونیتی
```http
POST /api/communities/{id}/join
Authorization: Bearer {token}
```

### ترک کامیونیتی
```http
POST /api/communities/{id}/leave
Authorization: Bearer {token}
```

### اعضای کامیونیتی
```http
GET /api/communities/{id}/members
Authorization: Bearer {token}

Query Parameters:
- role: owner|admin|moderator|member
- page: شماره صفحه
```

### پست‌های کامیونیتی
```http
GET /api/communities/{id}/posts
Authorization: Bearer {token}

Query Parameters:
- is_pinned: true|false
- page: شماره صفحه
```

### تایید درخواست عضویت
```http
POST /api/communities/{id}/requests/{requestId}/approve
Authorization: Bearer {token}
```

### رد درخواست عضویت
```http
POST /api/communities/{id}/requests/{requestId}/reject
Authorization: Bearer {token}
```

---

## 💌 پیام‌ها

### لیست مکالمات
```http
GET /api/conversations
Authorization: Bearer {token}
```

### ارسال پیام
```http
POST /api/messages
Authorization: Bearer {token}
Content-Type: application/json

{
  "recipient_id": 2,
  "content": "متن پیام",
  "media": "path/to/media.jpg"
}
```

### پیام‌های کاربر
```http
GET /api/messages/{userId}
Authorization: Bearer {token}

Query Parameters:
- page: شماره صفحه
- per_page: تعداد در صفحه
```

### خواندن پیام
```http
POST /api/messages/{id}/read
Authorization: Bearer {token}
```

---

## 🔍 جستجو

### جستجوی پست‌ها
```http
GET /api/search/posts
Authorization: Bearer {token}

Query Parameters:
- q: کلمه کلیدی جستجو
- hashtag: هشتگ
- user_id: شناسه کاربر
- from_date: تاریخ شروع
- to_date: تاریخ پایان
- page: شماره صفحه
```

### جستجوی کاربران
```http
GET /api/search/users
Authorization: Bearer {token}

Query Parameters:
- q: نام یا نام کاربری
- verified: true|false
- page: شماره صفحه
```

### جستجوی کلی
```http
GET /api/search
Authorization: Bearer {token}

Query Parameters:
- q: کلمه کلیدی
- type: posts|users|hashtags
- page: شماره صفحه
```

---

## 📈 ترندینگ

### هشتگ‌های ترند
```http
GET /api/trending/hashtags
Authorization: Bearer {token}

Query Parameters:
- limit: تعداد نتایج (پیش‌فرض: 10)
- timeframe: 1h|24h|7d
```

### پست‌های ترند
```http
GET /api/trending/posts
Authorization: Bearer {token}

Query Parameters:
- limit: تعداد نتایج
- timeframe: 1h|24h|7d
```

### کاربران ترند
```http
GET /api/trending/users
Authorization: Bearer {token}

Query Parameters:
- limit: تعداد نتایج
```

---

## 👤 پروفایل

### نمایش پروفایل
```http
GET /api/users/{id}
Authorization: Bearer {token}
```

### ویرایش پروفایل
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "نام جدید",
  "bio": "بیوگرافی",
  "location": "موقعیت مکانی",
  "website": "https://example.com"
}
```

### آپلود آواتار
```http
POST /api/profile/avatar
Authorization: Bearer {token}
Content-Type: multipart/form-data

avatar: [file]
```

### پست‌های کاربر
```http
GET /api/users/{id}/posts
Authorization: Bearer {token}

Query Parameters:
- type: posts|replies|media
- page: شماره صفحه
```

---

## 🤝 فالو

### فالو کردن
```http
POST /api/users/{id}/follow
Authorization: Bearer {token}
```

### آنفالو کردن
```http
DELETE /api/users/{id}/follow
Authorization: Bearer {token}
```

### فالوورها
```http
GET /api/users/{id}/followers
Authorization: Bearer {token}

Query Parameters:
- page: شماره صفحه
```

### فالوینگ
```http
GET /api/users/{id}/following
Authorization: Bearer {token}

Query Parameters:
- page: شماره صفحه
```

---

## 🔔 نوتیفیکیشن‌ها

### لیست نوتیفیکیشن‌ها
```http
GET /api/notifications
Authorization: Bearer {token}

Query Parameters:
- unread: true|false
- type: like|comment|follow|mention
- page: شماره صفحه
```

### خواندن نوتیفیکیشن
```http
POST /api/notifications/{id}/read
Authorization: Bearer {token}
```

### خواندن همه نوتیفیکیشن‌ها
```http
POST /api/notifications/read-all
Authorization: Bearer {token}
```

### تعداد نوتیفیکیشن‌های خوانده نشده
```http
GET /api/notifications/unread-count
Authorization: Bearer {token}
```

---

## 📊 نظرسنجی

### ایجاد نظرسنجی
```http
POST /api/polls
Authorization: Bearer {token}
Content-Type: application/json

{
  "question": "سوال نظرسنجی",
  "options": ["گزینه 1", "گزینه 2", "گزینه 3"],
  "expires_at": "2024-12-31 23:59:59",
  "multiple_choice": false
}
```

### رای دادن
```http
POST /api/polls/{id}/vote
Authorization: Bearer {token}
Content-Type: application/json

{
  "option_id": 1
}
```

### نتایج نظرسنجی
```http
GET /api/polls/{id}/results
Authorization: Bearer {token}
```

---

## 📱 دستگاه‌ها

### ثبت دستگاه
```http
POST /api/devices
Authorization: Bearer {token}
Content-Type: application/json

{
  "token": "device_push_token",
  "platform": "ios",
  "device_name": "iPhone 13"
}
```

### حذف دستگاه
```http
DELETE /api/devices/{id}
Authorization: Bearer {token}
```

---

## 📈 آنالیتیکس

### داشبورد آنالیتیکس
```http
GET /api/analytics/dashboard
Authorization: Bearer {token}

Query Parameters:
- period: 7d|30d|90d
```

### آنالیتیکس پست
```http
GET /api/posts/{id}/analytics
Authorization: Bearer {token}
```

### ردیابی رویداد
```http
POST /api/analytics/track
Authorization: Bearer {token}
Content-Type: application/json

{
  "event": "post_view",
  "properties": {
    "post_id": 123,
    "source": "timeline"
  }
}
```

---

## 🎯 تبلیغات

### ایجاد تبلیغ
```http
POST /api/advertisements
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "عنوان تبلیغ",
  "content": "محتوای تبلیغ",
  "target_audience": {
    "age_range": [18, 35],
    "interests": ["technology", "sports"]
  },
  "budget": 100,
  "duration_days": 7
}
```

### تبلیغات هدفمند
```http
GET /api/advertisements/targeted
Authorization: Bearer {token}

Query Parameters:
- limit: تعداد تبلیغات
```

---

## 🔒 امنیت

### گزارش محتوا
```http
POST /api/reports
Authorization: Bearer {token}
Content-Type: application/json

{
  "reportable_type": "post",
  "reportable_id": 123,
  "reason": "spam",
  "description": "توضیحات اضافی"
}
```

### بلاک کردن کاربر
```http
POST /api/users/{id}/block
Authorization: Bearer {token}
```

### آنبلاک کردن کاربر
```http
DELETE /api/users/{id}/block
Authorization: Bearer {token}
```

---

## 📋 بوکمارک

### بوکمارک کردن پست
```http
POST /api/posts/{id}/bookmark
Authorization: Bearer {token}
```

### حذف بوکمارک
```http
DELETE /api/posts/{id}/bookmark
Authorization: Bearer {token}
```

### لیست بوکمارک‌ها
```http
GET /api/bookmarks
Authorization: Bearer {token}

Query Parameters:
- page: شماره صفحه
```

---

## 🎵 اسپیس‌های صوتی

### ایجاد اسپیس
```http
POST /api/spaces
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "عنوان اسپیس",
  "description": "توضیحات",
  "is_public": true,
  "max_participants": 100
}
```

### پیوستن به اسپیس
```http
POST /api/spaces/{id}/join
Authorization: Bearer {token}
```

### ترک اسپیس
```http
POST /api/spaces/{id}/leave
Authorization: Bearer {token}
```

---

## 📊 مانیتورینگ

### داشبورد مانیتورینگ
```http
GET /api/monitoring/dashboard
Authorization: Bearer {token}
```

### متریک‌های سیستم
```http
GET /api/monitoring/metrics
Authorization: Bearer {token}

Query Parameters:
- metric: cpu|memory|disk|network
- period: 1h|24h|7d
```

---

## 🔄 Real-time Events

### اتصال WebSocket
```javascript
const socket = io('ws://localhost:6001', {
  auth: {
    token: 'bearer_token'
  }
});

// دریافت پست جدید در تایم‌لاین
socket.on('post.published', (data) => {
  console.log('New post:', data);
});

// دریافت نوتیفیکیشن
socket.on('notification.sent', (data) => {
  console.log('New notification:', data);
});

// دریافت پیام جدید
socket.on('message.sent', (data) => {
  console.log('New message:', data);
});
```

---

## 📝 Response Format

### موفقیت‌آمیز
```json
{
  "success": true,
  "data": {
    // داده‌های پاسخ
  },
  "message": "عملیات با موفقیت انجام شد"
}
```

### خطا
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "داده‌های ورودی نامعتبر است",
    "details": {
      "email": ["فرمت ایمیل نامعتبر است"]
    }
  }
}
```

### Pagination
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 200
  },
  "links": {
    "first": "http://api.wonderway.com/posts?page=1",
    "last": "http://api.wonderway.com/posts?page=10",
    "prev": null,
    "next": "http://api.wonderway.com/posts?page=2"
  }
}
```

---

## 🔑 HTTP Status Codes

- `200` - موفقیت‌آمیز
- `201` - ایجاد شده
- `204` - بدون محتوا (حذف موفق)
- `400` - درخواست نامعتبر
- `401` - عدم احراز هویت
- `403` - عدم دسترسی
- `404` - یافت نشد
- `422` - خطای اعتبارسنجی
- `429` - محدودیت نرخ درخواست
- `500` - خطای سرور

---

## 🚀 Rate Limiting

- **عمومی:** 1000 درخواست در ساعت
- **احراز هویت:** 100 درخواست در دقیقه
- **آپلود فایل:** 10 درخواست در دقیقه
- **جستجو:** 60 درخواست در دقیقه

---

**تاریخ بروزرسانی:** دسامبر 2024  
**نسخه API:** v1  
**Base URL:** `https://api.wonderway.com`
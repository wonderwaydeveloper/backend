# WonderWay Backend

<div align="center">

![WonderWay Logo](https://via.placeholder.com/200x80/4F46E5/FFFFFF?text=WonderWay)

**پلتفرم پیشرفته شبکه اجتماعی با قابلیت‌های Real-time**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-7.0-DC382D?style=for-the-badge&logo=redis)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)](https://docker.com)

[![CI/CD](https://github.com/wonderway/backend/workflows/CI%2FCD/badge.svg)](https://github.com/wonderway/backend/actions)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)
[![Version](https://img.shields.io/badge/Version-3.0.0-blue.svg?style=for-the-badge)](CHANGELOG.md)

</div>

## 📋 فهرست مطالب

- [درباره پروژه](#-درباره-پروژه)
- [ویژگی‌های کلیدی](#-ویژگیهای-کلیدی)
- [معماری سیستم](#-معماری-سیستم)
- [پیش‌نیازها](#-پیشنیازها)
- [نصب و راه‌اندازی](#-نصب-و-راهاندازی)
- [پیکربندی](#-پیکربندی)
- [استفاده](#-استفاده)
- [API Documentation](#-api-documentation)
- [تست](#-تست)
- [استقرار](#-استقرار)
- [مشارکت](#-مشارکت)
- [امنیت](#-امنیت)
- [لایسنس](#-لایسنس)

## 🚀 درباره پروژه

**WonderWay** یک پلتفرم پیشرفته شبکه اجتماعی است که با استفاده از Laravel 12 و معماری Clean Architecture توسعه یافته است. این پروژه شامل قابلیت‌های مدرن مانند Real-time messaging، Video streaming، AI-powered content moderation و سیستم‌های پیشرفته امنیتی می‌باشد.

### 🎯 اهداف پروژه

- ارائه تجربه کاربری سریع و روان
- پشتیبانی از میلیون‌ها کاربر همزمان
- امنیت بالا و حفاظت از داده‌های کاربران
- قابلیت مقیاس‌پذیری افقی و عمودی
- پشتیبانی از چندین زبان و فرهنگ

## ✨ ویژگی‌های کلیدی

### 🔐 احراز هویت و امنیت
- **Multi-factor Authentication (2FA)** با Google Authenticator
- **Social Login** (Google, GitHub, Facebook)
- **Phone Authentication** با SMS verification
- **JWT Token Management** با Refresh Token
- **Advanced Rate Limiting** و Bot Detection
- **Password Security** با تشخیص رمزهای ضعیف

### 📱 قابلیت‌های اجتماعی
- **Posts & Comments** با پشتیبانی از Media
- **Real-time Timeline** با WebSocket
- **Hashtag System** و Trending Topics
- **Follow/Unfollow** با Follow Requests
- **Direct Messaging** با Real-time delivery
- **Stories (Moments)** با Auto-expire
- **Polls & Surveys** تعاملی

### 🎥 مدیا و محتوا
- **Image Upload** با Auto-resize و Compression
- **Video Upload** با Background processing
- **GIF Integration** با Giphy API
- **Live Streaming** با RTMP support
- **Content Moderation** با AI detection

### 🏘️ کامیونیتی و گروه‌ها
- **Communities** با Role-based permissions
- **Audio Spaces** (مشابه Twitter Spaces)
- **User Lists** و Custom feeds
- **Community Notes** برای Fact-checking

### 👨‍👩‍👧‍👦 کنترل والدین
- **Parental Controls** برای کاربران زیر 18 سال
- **Content Filtering** بر اساس سن
- **Activity Monitoring** برای والدین
- **Safe Mode** برای محیط امن

### 📊 تحلیلات و گزارش‌گیری
- **Real-time Analytics** با Elasticsearch
- **A/B Testing** framework
- **Conversion Tracking** و User Journey
- **Performance Monitoring** با Prometheus/Grafana

### 💰 درآمدزایی
- **Advertisement System** با Targeting
- **Creator Fund** برای تولیدکنندگان محتوا
- **Premium Subscriptions** با ویژگی‌های اضافی
- **In-app Purchases** و Virtual gifts

## 🏗️ معماری سیستم

### Simplified Laravel Architecture (بعد از پاکسازی)
```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │   Controllers   │  │   Middleware    │  │   Requests   │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                    Business Logic Layer                      │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │    Services     │  │      DTOs       │  │    Events    │ │
│  │   (Core Layer)  │  │  (Validation)   │  │ (Laravel)    │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                      Data Access Layer                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │   Eloquent      │  │    Cache        │  │   External   │ │
│  │    Models       │  │   Services      │  │   Services   │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### ✅ تغییرات معماری (فاز ۱ و ۲ تکمیل شده)

#### حذف شده:
- ❌ **Repository Pattern** - لایه اضافی حذف شد
- ❌ **Action Classes** - تکرار با Services حذف شد
- ❌ **CQRS Commands** - پیچیدگی غیرضروری (فاز ۳)
- ❌ **Domain Layer** - Over-engineering (فاز ۴)
- ❌ **Event Sourcing** - پیچیدگی اضافی (فاز ۵)

#### باقیمانده:
- ✅ **Services** - لایه اصلی Business Logic
- ✅ **Controllers** - HTTP Request Handling
- ✅ **Models** - Data Access با Eloquent
- ✅ **Cache Services** - Performance بهینه
- ✅ **Laravel Events** - Event-driven architecture ساده

### Technology Stack

#### Backend Core
- **Framework**: Laravel 12.x
- **Language**: PHP 8.2+
- **Database**: MySQL 8.0 (Primary), Redis (Cache/Sessions)
- **Search**: Elasticsearch 8.x
- **Queue**: Redis with Horizon
- **WebSocket**: Laravel Reverb

#### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Web Server**: Nginx
- **Process Manager**: Supervisor
- **Monitoring**: Prometheus + Grafana
- **CI/CD**: GitHub Actions

#### External Services
- **File Storage**: AWS S3 / Local Storage
- **CDN**: AWS CloudFront
- **Email**: SMTP / AWS SES
- **SMS**: Twilio
- **Push Notifications**: Firebase FCM

## 📋 پیش‌نیازها

### سیستم عامل
- Linux (Ubuntu 20.04+ توصیه می‌شود)
- macOS 10.15+
- Windows 10+ (با WSL2)

### نرم‌افزارهای مورد نیاز
- **PHP**: 8.2 یا بالاتر
- **Composer**: 2.0+
- **Node.js**: 18.0+
- **MySQL**: 8.0+
- **Redis**: 7.0+
- **Docker**: 20.10+ (اختیاری)
- **Git**: 2.30+

### PHP Extensions
```bash
php-fpm php-mysql php-redis php-gd php-xml php-mbstring 
php-curl php-zip php-bcmath php-intl php-opcache
```

## 🛠️ نصب و راه‌اندازی

### روش 1: نصب Manual

#### 1. کلون کردن پروژه
```bash
git clone https://github.com/wonderway/backend.git wonderway-backend
cd wonderway-backend
```

#### 2. نصب Dependencies
```bash
# PHP Dependencies
composer install

# Node.js Dependencies  
npm install
```

#### 3. پیکربندی Environment
```bash
# کپی فایل تنظیمات
cp .env.example .env

# تولید Application Key
php artisan key:generate

# تولید JWT Secret
php artisan jwt:secret
```

#### 4. پیکربندی دیتابیس
```bash
# ایجاد دیتابیس
mysql -u root -p -e "CREATE DATABASE wonderway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# اجرای Migrations
php artisan migrate

# اجرای Seeders
php artisan db:seed
```

#### 5. راه‌اندازی Cache و Queue
```bash
# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue worker (در terminal جداگانه)
php artisan queue:work

# WebSocket server (در terminal جداگانه)
php artisan reverb:start
```

#### 6. اجرای سرور Development
```bash
# Laravel development server
php artisan serve

# یا با npm script برای اجرای همزمان تمام سرویس‌ها
npm run dev
```

### روش 2: نصب با Docker

#### 1. کلون و Build
```bash
git clone https://github.com/wonderway/backend.git wonderway-backend
cd wonderway-backend

# Build و اجرای containers
docker-compose up -d --build
```

#### 2. Setup داخل Container
```bash
# ورود به container
docker exec -it wonderway-app bash

# اجرای migrations
php artisan migrate --seed

# Cache optimization
php artisan optimize
```

#### 3. دسترسی به سرویس‌ها
- **API**: http://localhost
- **WebSocket**: ws://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379
- **Elasticsearch**: http://localhost:9200
- **Grafana**: http://localhost:3000

### روش 3: نصب سریع (Quick Setup)
```bash
# استفاده از composer script
composer run setup

# یا npm script
npm run setup
```

## ⚙️ پیکربندی

### Environment Variables

#### Application Settings
```env
APP_NAME=WonderWay
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.wonderway.com
FRONTEND_URL=https://wonderway.com
APP_LOCALE=fa
APP_FALLBACK_LOCALE=en
```

#### Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wonderway
DB_USERNAME=wonderway_user
DB_PASSWORD=secure_password

# Read/Write Splitting
DB_READ_HOST_1=read1.wonderway.com
DB_READ_HOST_2=read2.wonderway.com
DB_WRITE_HOST=write.wonderway.com

# Database Sharding
DB_SHARDING_ENABLED=true
DB_SHARDS_COUNT=4
```

#### Cache & Session
```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

# Redis Clustering
REDIS_CLUSTER_ENABLED=true
REDIS_CLUSTER_NODE_1_HOST=redis1.wonderway.com
REDIS_CLUSTER_NODE_2_HOST=redis2.wonderway.com
REDIS_CLUSTER_NODE_3_HOST=redis3.wonderway.com
```

#### Security Settings
```env
# JWT Configuration
JWT_SECRET=your-super-secret-jwt-key
JWT_ACCESS_TTL=3600
JWT_REFRESH_TTL=604800

# Security Features
SECURITY_WAF_ENABLED=true
SECURITY_RATE_LIMIT_ENABLED=true
SECURITY_THREAT_THRESHOLD=50
SECURITY_IP_BLOCK_DURATION=3600
```

#### External Services
```env
# Email Service
MAIL_MAILER=smtp
MAIL_HOST=smtp.wonderway.com
MAIL_PORT=587
MAIL_USERNAME=noreply@wonderway.com
MAIL_PASSWORD=email_password

# SMS Service (Twilio)
TWILIO_ACCOUNT_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_PHONE_NUMBER=+1234567890

# Push Notifications (Firebase)
FIREBASE_API_KEY=your_firebase_key
FIREBASE_PROJECT_ID=wonderway-project
FIREBASE_CREDENTIALS_PATH=storage/firebase-credentials.json

# Social Login
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_secret
GITHUB_CLIENT_ID=your_github_client_id
GITHUB_CLIENT_SECRET=your_github_secret
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_secret

# Search Engine
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your_meilisearch_key

ELASTICSEARCH_HOST=localhost:9200
ELASTICSEARCH_INDEX=wonderway
ELASTICSEARCH_USERNAME=elastic
ELASTICSEARCH_PASSWORD=elastic_password

# CDN Configuration
CDN_ENABLED=true
CDN_IMAGES_URL=https://cdn-images.wonderway.com
CDN_VIDEOS_URL=https://cdn-videos.wonderway.com
AWS_CLOUDFRONT_DISTRIBUTION_ID=your_distribution_id

# AWS Services
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=wonderway-storage
```

### Application Configuration

#### Rate Limiting
```php
// config/wonderway.php
'rate_limits' => [
    'login' => '5,5',        // 5 attempts per 5 minutes
    'register' => '3,60',    // 3 attempts per hour
    'post' => '10,1',        // 10 posts per minute
    'follow' => '30,1',      // 30 follows per minute
    'message' => '60,1',     // 60 messages per minute
],
```

#### Content Limits
```php
'post' => [
    'max_length' => 280,
    'max_images' => 4,
    'image_max_size' => 2048, // KB
],
'message' => [
    'max_length' => 1000,
    'media_max_size' => 10240, // KB
],
```

#### Cache TTL Settings
```php
'cache' => [
    'trending_ttl' => 3600,        // 1 hour
    'user_suggestions_ttl' => 1800, // 30 minutes
    'timeline_ttl' => 300,         // 5 minutes
],
```

## 📖 استفاده

### Authentication

#### ثبت‌نام کاربر جدید
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "علی احمدی",
    "username": "ali_ahmadi",
    "email": "ali@example.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "date_of_birth": "1990-01-01"
  }'
```

#### ورود به سیستم
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "ali@example.com",
    "password": "SecurePass123!"
  }'
```

#### ورود با شماره تلفن
```bash
# ارسال کد تایید
curl -X POST http://localhost:8000/api/auth/phone/send-code \
  -H "Content-Type: application/json" \
  -d '{"phone": "+989123456789"}'

# تایید کد و ورود
curl -X POST http://localhost:8000/api/auth/phone/verify \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+989123456789",
    "code": "123456"
  }'
```

### Posts Management

#### ایجاد پست جدید
```bash
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "سلام دنیا! این اولین پست من در WonderWay است 🚀 #wonderway",
    "reply_settings": "everyone"
  }'
```

#### آپلود تصویر
```bash
curl -X POST http://localhost:8000/api/media/upload/image \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "image=@/path/to/image.jpg" \
  -F "alt_text=توضیح تصویر"
```

#### ایجاد Thread
```bash
curl -X POST http://localhost:8000/api/threads \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "posts": [
      {"content": "این شروع یک thread است... 1/3"},
      {"content": "ادامه مطلب در این قسمت... 2/3"},
      {"content": "و در نهایت نتیجه‌گیری 3/3"}
    ]
  }'
```

### Real-time Features

#### اتصال به WebSocket
```javascript
// Frontend JavaScript
const socket = new WebSocket('ws://localhost:8080');

socket.onopen = function(event) {
    console.log('Connected to WebSocket');
    
    // Subscribe to user's timeline
    socket.send(JSON.stringify({
        type: 'subscribe',
        channel: 'timeline.user.123'
    }));
};

socket.onmessage = function(event) {
    const data = JSON.parse(event.data);
    console.log('New message:', data);
};
```

#### Real-time Notifications
```javascript
// Subscribe to notifications
socket.send(JSON.stringify({
    type: 'subscribe',
    channel: 'notifications.user.123'
}));

// Listen for new notifications
socket.onmessage = function(event) {
    const notification = JSON.parse(event.data);
    if (notification.type === 'new_follower') {
        showNotification(`${notification.data.follower.name} شما را دنبال کرد`);
    }
};
```

### Advanced Features

#### A/B Testing
```bash
# ایجاد تست A/B جدید
curl -X POST http://localhost:8000/api/ab-tests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Timeline Design",
    "description": "Testing new timeline layout",
    "variants": [
      {"name": "control", "weight": 50},
      {"name": "new_design", "weight": 50}
    ],
    "target_percentage": 10
  }'

# دریافت variant برای کاربر
curl -X POST http://localhost:8000/api/ab-tests/assign \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"test_name": "New Timeline Design"}'
```

#### Analytics Tracking
```bash
# ثبت رویداد Analytics
curl -X POST http://localhost:8000/api/analytics/track \
  -H "Content-Type: application/json" \
  -d '{
    "event": "post_viewed",
    "properties": {
      "post_id": 123,
      "view_duration": 5000,
      "source": "timeline"
    }
  }'
```

## 📚 API Documentation

### Swagger/OpenAPI
پس از راه‌اندازی پروژه، مستندات کامل API در آدرس زیر در دسترس است:

```
http://localhost:8000/api/documentation
```

### API Endpoints Overview

#### Authentication
- `POST /api/register` - ثبت‌نام کاربر جدید
- `POST /api/login` - ورود به سیستم
- `POST /api/logout` - خروج از سیستم
- `GET /api/me` - اطلاعات کاربر فعلی
- `POST /api/auth/2fa/enable` - فعال‌سازی 2FA

#### Posts & Content
- `GET /api/posts` - لیست پست‌ها
- `POST /api/posts` - ایجاد پست جدید
- `GET /api/posts/{id}` - نمایش پست
- `PUT /api/posts/{id}` - ویرایش پست
- `DELETE /api/posts/{id}` - حذف پست
- `POST /api/posts/{id}/like` - لایک پست
- `POST /api/posts/{id}/repost` - بازنشر پست

#### Social Features
- `POST /api/users/{id}/follow` - دنبال کردن کاربر
- `GET /api/users/{id}/followers` - لیست دنبال‌کنندگان
- `GET /api/users/{id}/following` - لیست دنبال‌شوندگان
- `GET /api/timeline` - تایم‌لاین شخصی
- `GET /api/trending/hashtags` - هشتگ‌های ترند

#### Messaging
- `GET /api/messages/conversations` - لیست مکالمات
- `POST /api/messages/users/{id}` - ارسال پیام
- `GET /api/messages/users/{id}` - تاریخچه پیام‌ها

#### Communities
- `GET /api/communities` - لیست کامیونیتی‌ها
- `POST /api/communities` - ایجاد کامیونیتی
- `POST /api/communities/{id}/join` - عضویت در کامیونیتی

### Response Format
تمام API responses به فرمت JSON و با ساختار استاندارد ارائه می‌شوند:

```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "عملیات با موفقیت انجام شد",
  "meta": {
    "pagination": {
      "current_page": 1,
      "total_pages": 10,
      "per_page": 20,
      "total": 200
    }
  }
}
```

### Error Handling
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "داده‌های ارسالی نامعتبر است",
    "details": {
      "email": ["فرمت ایمیل صحیح نیست"],
      "password": ["رمز عبور باید حداقل 8 کاراکتر باشد"]
    }
  }
}
```

## 🧪 تست

### اجرای تست‌ها

#### تمام تست‌ها
```bash
php artisan test
```

#### تست‌های مشخص
```bash
# Unit Tests
php artisan test --testsuite=Unit

# Feature Tests  
php artisan test --testsuite=Feature

# تست مشخص
php artisan test tests/Feature/AuthenticationTest.php
```

#### تست با Coverage
```bash
php artisan test --coverage
```

### انواع تست‌ها

#### Unit Tests
- Model relationships و business logic
- Service classes و helper functions
- Validation rules و custom rules

#### Feature Tests
- API endpoints و HTTP responses
- Authentication و authorization
- Database interactions
- File uploads و media processing

#### Integration Tests
- External service integrations
- Queue jobs و event listeners
- WebSocket connections
- Email و SMS notifications

### Test Database
```bash
# ایجاد test database
mysql -u root -p -e "CREATE DATABASE wonderway_test;"

# اجرای migrations برای test
php artisan migrate --env=testing
```

### Continuous Testing
```bash
# Watch mode برای development
php artisan test --watch

# Parallel testing
php artisan test --parallel
```

## 🚀 استقرار

### Production Deployment

#### 1. Server Requirements
```bash
# Minimum server specs
- CPU: 4 cores
- RAM: 8GB
- Storage: 100GB SSD
- Bandwidth: 1Gbps

# Recommended for high traffic
- CPU: 8+ cores  
- RAM: 32GB+
- Storage: 500GB+ NVMe SSD
- Bandwidth: 10Gbps
```

#### 2. Docker Production Setup
```bash
# Clone repository
git clone https://github.com/wonderway/backend.git
cd wonderway-backend

# Copy production environment
cp .env.production .env

# Build and deploy
docker-compose -f docker-compose.prod.yml up -d --build

# Run migrations
docker exec wonderway-app php artisan migrate --force

# Optimize application
docker exec wonderway-app php artisan optimize
```

#### 3. Load Balancer Configuration
```nginx
# /etc/nginx/sites-available/wonderway
upstream wonderway_backend {
    server app1.wonderway.com:80 weight=3;
    server app2.wonderway.com:80 weight=3;
    server app3.wonderway.com:80 weight=2;
}

server {
    listen 443 ssl http2;
    server_name api.wonderway.com;
    
    ssl_certificate /etc/ssl/certs/wonderway.crt;
    ssl_certificate_key /etc/ssl/private/wonderway.key;
    
    location / {
        proxy_pass http://wonderway_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    location /ws {
        proxy_pass http://wonderway_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

#### 4. Database Optimization
```sql
-- MySQL Production Settings
[mysqld]
innodb_buffer_pool_size = 16G
innodb_log_file_size = 2G
innodb_flush_log_at_trx_commit = 2
query_cache_size = 256M
max_connections = 1000
thread_cache_size = 50
table_open_cache = 4000
```

#### 5. Redis Clustering
```bash
# Redis Cluster Setup
redis-cli --cluster create \
  redis1.wonderway.com:7000 \
  redis2.wonderway.com:7000 \
  redis3.wonderway.com:7000 \
  redis1.wonderway.com:7001 \
  redis2.wonderway.com:7001 \
  redis3.wonderway.com:7001 \
  --cluster-replicas 1
```

### CI/CD Pipeline

#### GitHub Actions Workflow
```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.SSH_KEY }}
          script: |
            cd /var/www/wonderway-backend
            git pull origin main
            docker-compose down
            docker-compose up -d --build
            docker exec wonderway-app php artisan migrate --force
            docker exec wonderway-app php artisan optimize
```

### Monitoring & Logging

#### Application Monitoring
```bash
# Prometheus metrics endpoint
curl http://localhost:9090/metrics

# Grafana dashboard
http://localhost:3000
```

#### Log Management
```bash
# Application logs
tail -f storage/logs/laravel.log

# Security logs  
tail -f storage/logs/security.log

# Performance logs
tail -f storage/logs/performance.log
```

## 🤝 مشارکت

### Development Workflow

#### 1. Fork و Clone
```bash
# Fork repository on GitHub
git clone https://github.com/YOUR_USERNAME/wonderway-backend.git
cd wonderway-backend

# Add upstream remote
git remote add upstream https://github.com/wonderway/backend.git
```

#### 2. Branch Strategy
```bash
# Create feature branch
git checkout -b feature/new-awesome-feature

# Create bugfix branch  
git checkout -b bugfix/fix-critical-issue

# Create hotfix branch
git checkout -b hotfix/security-patch
```

#### 3. Development Standards

#### Code Style
```bash
# PHP CS Fixer
composer run cs-fix

# Check code style
composer run cs-check
```

#### Commit Messages
```
feat: add real-time notifications system
fix: resolve memory leak in timeline cache
docs: update API documentation
test: add unit tests for user service
refactor: optimize database queries
```

#### 4. Pull Request Process
1. Fork the repository
2. Create your feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Update documentation
6. Submit pull request with clear description

### Code Review Guidelines

#### Required Checks
- [ ] All tests pass
- [ ] Code coverage > 80%
- [ ] No security vulnerabilities
- [ ] Performance impact assessed
- [ ] Documentation updated
- [ ] Breaking changes documented

#### Review Criteria
- Code quality and readability
- Security best practices
- Performance optimization
- Test coverage
- Documentation completeness

## 🔒 امنیت

### Security Features

#### 1. Authentication Security
- **Multi-factor Authentication** (TOTP)
- **Password Hashing** با Bcrypt
- **JWT Token Management** با Refresh Tokens
- **Session Security** با Secure Cookies
- **Account Lockout** پس از تلاش‌های ناموفق

#### 2. API Security
- **Rate Limiting** پیشرفته
- **CORS Protection** 
- **CSRF Protection**
- **SQL Injection Prevention**
- **XSS Protection**
- **Input Validation** و Sanitization

#### 3. Data Protection
- **Database Encryption** برای داده‌های حساس
- **File Upload Security** با Virus Scanning
- **Content Security Policy** (CSP)
- **HTTPS Enforcement**
- **Secure Headers** (HSTS, X-Frame-Options)

#### 4. Monitoring & Logging
- **Security Event Logging**
- **Intrusion Detection**
- **Anomaly Detection**
- **Real-time Alerts**
- **Audit Trail** برای تمام عملیات

### Security Configuration

#### Environment Security
```env
# Security Settings
SECURITY_WAF_ENABLED=true
SECURITY_RATE_LIMIT_ENABLED=true
SECURITY_THREAT_THRESHOLD=50
SECURITY_IP_BLOCK_DURATION=3600
SECURITY_ENCRYPTION_KEY=your-32-character-secret-key
```

#### Headers Security
```php
// config/security.php
'headers' => [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'",
],
```

### Vulnerability Reporting

اگر مشکل امنیتی پیدا کردید، لطفاً از طریق ایمیل security@wonderway.com با ما تماس بگیرید.

**لطفاً مسائل امنیتی را در GitHub Issues گزارش نکنید.**

## 📄 لایسنس

این پروژه تحت لایسنس MIT منتشر شده است. برای جزئیات بیشتر فایل [LICENSE](LICENSE) را مطالعه کنید.

```
MIT License

Copyright (c) 2024 WonderWay

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 📞 تماس و پشتیبانی

### تیم توسعه
- **Lead Developer**: [نام توسعه‌دهنده اصلی]
- **Backend Team**: [اعضای تیم بک‌اند]
- **DevOps Engineer**: [مهندس DevOps]

### ارتباط با ما
- **Website**: https://wonderway.com
- **Email**: info@wonderway.com
- **Support**: support@wonderway.com
- **Security**: security@wonderway.com

### لینک‌های مفید
- [📖 Documentation](https://docs.wonderway.com)
- [🐛 Bug Reports](https://github.com/wonderway/backend/issues)
- [💡 Feature Requests](https://github.com/wonderway/backend/discussions)
- [📊 Status Page](https://status.wonderway.com)
- [📱 Mobile Apps](https://wonderway.com/download)

---

<div align="center">

**ساخته شده با ❤️ توسط تیم WonderWay**

[![GitHub Stars](https://img.shields.io/github/stars/wonderway/backend?style=social)](https://github.com/wonderway/backend/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/wonderway/backend?style=social)](https://github.com/wonderway/backend/network/members)
[![GitHub Issues](https://img.shields.io/github/issues/wonderway/backend)](https://github.com/wonderway/backend/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/wonderway/backend)](https://github.com/wonderway/backend/pulls)

</div># #   a"�� �   j%� j%Q%�%� �%� j%�%j%� j%�   %� j%c%%� j%� j%�%�%� 
 
 # # #   �� �   %� j%� j%�%%� j%� �%�   j%� %#%� �%� %�   j%$%j%� %� 
 
 # # # #   %� j%� j%�%  �%�%:   j%� j%�%%�   R e p o s i t o r y   P a t t e r n   ( 4   j%%j%� j%c%j%� ) 
 -   * * j%� j%�%%�   j%$%j%� %� * * :   1 5   %� j%� �%� %�   R e p o s i t o r y   %�   I n t e r f a c e 
 -   * * %� j%� �%� j%� %� * * :   %#j%� %� j%$%  4 0 %   %[%�%� %� �%� j%� %� �%� j%�   %� %� j%V%%�   %� j%%j%� %� �%� %�   j%� j%�%  S e r v i c e s 
 -   * * j%� j%%j%� * * :   3 0 1 / 3 0 2   %� %� %� %� 
 
 # # # #   %� j%� j%�%  �%�%:   j%� j%�%%�   A c t i o n s   P a t t e r n   ( 2   j%%j%� j%c%j%� )     
 -   * * j%� j%�%%�   j%$%j%� %� * * :   1 0   %� j%� �%� %�   A c t i o n   j%� %#j%�%j%� j%�%�%� 
 -   * * %� j%� �%� j%� %� * * :   C o n t r o l l e r s   j%%j%� j%� %� �� � j%� j%�%j%�   �%� %#%[%j%� j%�%%� %� �%�   j%� j%�%  S e r v i c e s 
 -   * * j%� j%%j%� * * :   4 0 8 / 4 0 8   %� %� %� %� 
 
 # # #   a"�� �   %� j%� j%�%%� j%� �%�   j%� j%�%  j%� %� j%� j%U%j%� j%�%
 
 # # # #   %� j%� j%�%  �%%:   j%� j%�%%�   C Q R S   P a t t e r n 
 -   * * %� j%� %� * * :   j%� j%�%%�   C o m m a n d B u s   %�   C o m m a n d / Q u e r y   c l a s s e s 
 -   * * j%� j%� %� �%� %� * * :   1 - 2   j%�%%� j%�%
 
 # # # #   %� j%� j%�%  �%$%:   j%� j%�%%�   D o m a i n   L a y e r 
 -   * * %� j%� %� * * :   j%� j%�%%�   D o m a i n   S e r v i c e s   %�   V a l u e   O b j e c t s 
 -   * * j%� j%� %� �%� %� * * :   1   j%�%%� j%�%
 
 # # # #   %� j%� j%�%  �%a%- �%V%:   %[%j%� %#j%%j%� j%�%�%�   %� %� j%� �%� �%� 
 -   * * j%$%j%� %� %� * * :   E v e n t S o u r c i n g j%�   D e s i g n   P a t t e r n s j%�   D T O s   j%� j%b%j%� %� �%� 
 -   * * j%� j%� %� �%� %� * * :   2 - 3   j%�%%� j%�%
 
 # # #   a"�� �   %� j%� j%� �%� j%�   %#%� �%� 
 -   * * %#j%� %� j%$%  %� j%� �%� %� * * :   2 5 +   %� j%� �%� %�   j%� j%�%%�   j%$%j%� %� 
 -   * * %#j%� %� j%$%  %[%�%� %� �%� j%� %� �%� * * :   6 5 % +   
 -   * * j%� %� j%� %� j%�   j%%j%�%j%c%j%� * * :   9 7 %   j%%j%�%�%� j%c%�� � j%� j%�%  j%� j%�%  j%� j%�%%� j%� %� %� 
 -   * * j%� %� j%U%  j%c%%� %� %#j%�%j%� * * :   1 0 0 %   f u n c t i o n a l i t y   j%� j%� %� �%� 
 
 - - - 
 
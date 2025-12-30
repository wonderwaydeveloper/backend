# WonderWay - پلتفرم شبکه اجتماعی پیشرفته

<div align="center">

![WonderWay Logo](https://via.placeholder.com/200x80/4F46E5/FFFFFF?text=WonderWay)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-7.0-DC382D?style=for-the-badge&logo=redis)](https://redis.io)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)](https://docker.com)

**پلتفرم شبکه اجتماعی مدرن با قابلیتهای پیشرفته و امنیت بالا**

[مستندات](#-مستندات) • [نصب](#-نصب-و-راهاندازی) • [API](#-api-documentation) • [مشارکت](#-مشارکت)

</div>

---

## 📋 فهرست مطالب

- [درباره پروژه](#-درباره-پروژه)
- [ویژگیهای کلیدی](#-ویژگیهای-کلیدی)
- [معماری سیستم](#-معماری-سیستم)
- [پیشنیازها](#-پیشنیازها)
- [نصب و راهاندازی](#-نصب-و-راهاندازی)
- [پیکربندی](#-پیکربندی)
- [API Documentation](#-api-documentation)
- [تستها](#-تستها)
- [دیپلویمنت](#-دیپلویمنت)
- [امنیت](#-امنیت)
- [مانیتورینگ](#-مانیتورینگ)
- [مشارکت](#-مشارکت)
- [لایسنس](#-لایسنس)

---

## 🚀 درباره پروژه

**WonderWay** یک پلتفرم شبکه اجتماعی مدرن و پیشرفته است که با استفاده از Laravel 12 و معماری Clean Architecture توسعه یافته است. این پلتفرم شامل قابلیتهای کاملی مانند پستگذاری، کامنت، لایک، فالو، پیامرسانی، کنترل والدین و بسیاری از ویژگیهای دیگر است.

### 🎯 اهداف پروژه

- ارائه تجربه کاربری بینظیر در شبکههای اجتماعی
- پیادهسازی امنیت پیشرفته و حفاظت از دادهها
- قابلیت مقیاسپذیری بالا برای میلیونها کاربر
- پشتیبانی از قابلیتهای Real-time
- کنترل والدین برای کاربران زیر 18 سال

---

## ✨ ویژگیهای کلیدی

### 🔐 احراز هویت و امنیت
- **احراز هویت چندمرحلهای (2FA)** با Google Authenticator
- **ورود اجتماعی** (Google, GitHub, Facebook)
- **احراز هویت با شماره تلفن** و SMS
- **سیستم امنیتی پیشرفته** با WAF و Rate Limiting
- **رمزگذاری دادهها** و JWT Security

### 📱 قابلیتهای اصلی
- **پستگذاری** با پشتیبانی از تصاویر، ویدیو و GIF
- **سیستم کامنت** و پاسخدهی
- **لایک و ریپست** پستها
- **Thread ها** (رشته پستها)
- **Quote Posts** (نقل قول پستها)
- **نظرسنجی (Poll)** در پستها
- **بوکمارک** پستها
- **پست زمانبندی شده** (Scheduled Posts)
- **ویرایش پستها** با تاریخچه

### 👥 شبکه اجتماعی
- **فالو کردن** کاربران
- **درخواست فالو** برای اکانتهای خصوصی
- **پیامرسانی خصوصی** Real-time
- **وضعیت آنلاین** کاربران
- **پیشنهاد کاربران** هوشمند
- **Communities** (انجمنها)

### 🎥 رسانه و محتوا
- **آپلود تصاویر و ویدیو** با پردازش خودکار
- **پشتیبانی از GIF** از طریق Giphy
- **Spaces** (اتاقهای صوتی)
- **Moments** (مجموعه پستها)
- **Community Notes** (یادداشتهای جامعه)
- **Lists** (لیستهای کاربران)

### 👨👩👧👦 کنترل والدین
- **لینک والدین-فرزند** با تأیید دوطرفه
- **کنترل محتوا** و فیلترینگ
- **گزارش فعالیت** فرزندان
- **تنظیمات امنیتی** ویژه کودکان

### 🔍 جستجو و کشف
- **جستجوی پیشرفته** با فیلترها
- **هشتگهای ترند**
- **پیشنهاد محتوا** شخصیسازی شده
- **فیلترهای جستجو** پیشرفته
- **منشن سیستم**

### 📊 آنالیتیکس و مانیتورینگ
- **آنالیتیکس کامل** پستها و کاربران
- **A/B Testing** برای بهینهسازی
- **Conversion Tracking**
- **Performance Monitoring** Real-time

### 💰 درآمدزایی
- **تبلیغات هدفمند**
- **اشتراک Premium**
- **Creator Fund** برای سازندگان محتوا
- **سیستم پرداخت** یکپارچه

---

## 🏗️ معماری سیستم

### Clean Architecture
پروژه بر اساس اصول Clean Architecture طراحی شده است:

```
app/
├── Actions/           # اکشنهای کسبوکار
├── Application/       # لایه Application
├── CQRS/             # Command Query Responsibility Segregation
├── Domain/           # لایه Domain (Entities, Value Objects)
├── Infrastructure/   # لایه Infrastructure
├── Services/         # سرویسهای کسبوکار
├── Repositories/     # الگوی Repository
├── DTOs/            # Data Transfer Objects
└── Contracts/       # Interfaces و Contracts
```

### Design Patterns
- **Repository Pattern** برای دسترسی به دادهها
- **Service Pattern** برای منطق کسبوکار
- **Factory Pattern** برای ایجاد اشیاء
- **Observer Pattern** برای Event Handling
- **Strategy Pattern** برای الگوریتمهای مختلف

### Event Sourcing
- **Event Store** برای ذخیره رویدادها
- **Event Handlers** برای پردازش رویدادها
- **Projections** برای نمایش دادهها

---

## 📋 پیشنیازها

### سیستمعامل
- **Linux/macOS/Windows** (توصیه: Ubuntu 20.04+)

### نرمافزارهای مورد نیاز
- **PHP 8.2+** با extensions زیر:
  - `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`
  - `gd`, `zip`, `opcache`, `redis`, `sockets`
- **Composer 2.0+**
- **Node.js 18+** و **npm**
- **MySQL 8.0+** یا **MariaDB 10.6+**
- **Redis 7.0+**

### ابزارهای توسعه
- **Git**
- **Docker & Docker Compose** (برای containerization)
- **FFmpeg** (برای پردازش ویدیو)

---

## 🛠️ نصب و راهاندازی

### 1. کلون کردن پروژه
```bash
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend
```

### 2. نصب Dependencies
```bash
# نصب PHP dependencies
composer install

# نصب Node.js dependencies
npm install
```

### 3. پیکربندی محیط
```bash
# کپی فایل محیط
cp .env.example .env

# تولید کلید اپلیکیشن
php artisan key:generate
```

### 4. پیکربندی دیتابیس
```bash
# ویرایش فایل .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wonderway
DB_USERNAME=your_username
DB_PASSWORD=your_password

# اجرای migrations
php artisan migrate

# اجرای seeders
php artisan db:seed
```

### 5. پیکربندی Redis
```bash
# در فایل .env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 6. راهاندازی سرویسها
```bash
# شروع سرور Laravel
php artisan serve

# شروع Queue Worker
php artisan queue:work

# شروع WebSocket Server
php artisan reverb:start
```

### 7. نصب با Docker (توصیه شده)
```bash
# ساخت و اجرای containers
docker-compose up -d

# اجرای migrations در container
docker-compose exec app php artisan migrate

# اجرای seeders
docker-compose exec app php artisan db:seed
```

---

## ⚙️ پیکربندی

### متغیرهای محیط مهم

#### اپلیکیشن
```env
APP_NAME=WonderWay
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
FRONTEND_URL=https://your-frontend-domain.com
```

#### امنیت
```env
JWT_SECRET=your-super-secret-jwt-key
SECURITY_WAF_ENABLED=true
SECURITY_RATE_LIMIT_ENABLED=true
SECURITY_THREAT_THRESHOLD=50
```

#### ایمیل
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
```

#### SMS (Twilio)
```env
TWILIO_ACCOUNT_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_PHONE_NUMBER=your-phone-number
```

#### Social Login
```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
```

---

## 📚 API Documentation

### Base URL
```
Production: https://api.wonderway.com
Development: http://localhost:8000/api
```

### Authentication
تمام API endpoints که نیاز به احراز هویت دارند، باید Bearer Token در header داشته باشند:

```http
Authorization: Bearer your-jwt-token
```

### Endpoints اصلی

#### احراز هویت
```http
POST /api/register          # ثبتنام کاربر جدید
POST /api/login             # ورود کاربر
POST /api/logout            # خروج کاربر
GET  /api/me                # اطلاعات کاربر فعلی
```

#### پستها
```http
GET    /api/posts           # دریافت لیست پستها
POST   /api/posts           # ایجاد پست جدید
GET    /api/posts/{id}      # دریافت پست خاص
PUT    /api/posts/{id}      # ویرایش پست
DELETE /api/posts/{id}      # حذف پست
POST   /api/posts/{id}/like # لایک کردن پست
POST   /api/posts/{id}/quote # نقل قول پست
```

#### کاربران
```http
GET  /api/users/{id}              # پروفایل کاربر
POST /api/users/{id}/follow       # فالو کردن کاربر
GET  /api/users/{id}/followers    # فالوورهای کاربر
GET  /api/users/{id}/following    # فالوینگهای کاربر
```

#### پیامرسانی
```http
GET  /api/messages/conversations  # لیست مکالمات
GET  /api/messages/users/{id}     # پیامهای با کاربر خاص
POST /api/messages/users/{id}     # ارسال پیام
```

### Swagger Documentation
مستندات کامل API در آدرس زیر در دسترس است:
```
http://localhost:8000/api/documentation
```

---

## 🧪 تستها

### اجرای تستها
```bash
# اجرای تمام تستها
php artisan test

# اجرای تستهای Feature
php artisan test --testsuite=Feature

# اجرای تستهای Unit
php artisan test --testsuite=Unit

# اجرای تست با Coverage
php artisan test --coverage
```

---

## 🚀 دیپلویمنت

### Docker Deployment (توصیه شده)

```bash
# کپی فایل محیط production
cp .env.production .env

# ساخت و اجرای containers
docker-compose -f docker-compose.yml up -d

# اجرای migrations
docker-compose exec app php artisan migrate --force

# بهینهسازی Laravel
docker-compose exec app php artisan optimize
```

---

## 🔒 امنیت

### اقدامات امنیتی پیادهسازی شده

- **Web Application Firewall (WAF)**
- **Rate Limiting پیشرفته**
- **رمزگذاری دادهها**
- **تشخیص تهدید**
- **حفاظت از CSRF و XSS**
- **Audit Trail**

---

## 📊 مانیتورینگ

### ابزارهای مانیتورینگ

- **Application Performance Monitoring**
- **Error Tracking**
- **Metrics Collection**
- **Log Management**
- **Real-time Monitoring**

---

## 🤝 مشارکت

برای مشارکت در پروژه، لطفاً [راهنمای مشارکت](CONTRIBUTING.md) را مطالعه کنید.

### مراحل مشارکت

1. Fork کردن repository
2. ایجاد branch جدید
3. انجام تغییرات
4. اجرای تستها
5. ایجاد Pull Request

---

## 📄 لایسنس

این پروژه تحت لایسنس MIT منتشر شده است. برای اطلاعات بیشتر فایل [LICENSE](LICENSE) را مطالعه کنید.

---

## 📞 پشتیبانی و تماس

- **GitHub Issues**: [مشکلات و پیشنهادات](https://github.com/your-username/wonderway-backend/issues)
- **Documentation**: [مستندات کامل](docs/README.md)

---

<div align="center">

**ساخته شده با ❤️ توسط تیم WonderWay**

[⬆ بازگشت به بالا](#wonderway---پلتفرم-شبکه-اجتماعی-پیشرفته)

</div>
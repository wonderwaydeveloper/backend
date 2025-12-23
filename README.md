# 🌟 WonderWay

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)
![License](https://img.shields.io/badge/license-Proprietary-red.svg)
![Tests](https://img.shields.io/badge/tests-436%20passing-brightgreen.svg)

**شبکه اجتماعی پیشرفته و مدرن با Laravel 12**

⚠️ **این پروژه کاملاً انحصاری و محافظت شده است** ⚠️

</div>

---

## 🎯 ویژگیهای کلیدی

### 📱 **هسته اصلی**
- ✅ **پستگذاری پیشرفته**: متن، تصویر، ویدیو، GIF، نظرسنجی
- ✅ **Thread Posts**: پستهای زنجیرهای
- ✅ **Quote Tweets**: نقل قول از پستها
- ✅ **سیستم لایک و کامنت**: با real-time updates
- ✅ **Hashtag و Mention**: با پشتیبانی کامل
- ✅ **Bookmark**: ذخیره پستها

### 💬 **پیامرسانی**
- ✅ **پیامهای خصوصی**: real-time messaging
- ✅ **گروههای چت**: پیامرسانی گروهی
- ✅ **وضعیت آنلاین**: نمایش وضعیت کاربران
- ✅ **Typing indicators**: نمایش در حال تایپ

### 🎥 **رسانه و سرگرمی**
- ✅ **Live Streaming**: پخش زنده با چت
- ✅ **Spaces**: اتاقهای صوتی
- ✅ **Stories**: محتوای 24 ساعته
- ✅ **Moments**: لحظات ویژه
- ✅ **پشتیبانی ویدیو 4K**: با thumbnail generation

### 🛡️ **امنیت و کنترل**
- ✅ **احراز هویت دو مرحلهای (2FA)**: Google Authenticator
- ✅ **کنترل والدین**: محافظت از کودکان
- ✅ **تشخیص اسپم**: هوش مصنوعی
- ✅ **Web Application Firewall**: محافظت در برابر حملات
- ✅ **Community Notes**: تأیید محتوا توسط جامعه

### 💰 **درآمدزایی**
- ✅ **Premium Subscriptions**: اشتراک ویژه
- ✅ **Advertisement System**: سیستم تبلیغات
- ✅ **Creator Fund**: صندوق حمایت از سازندگان

### 🌍 **بینالمللیسازی**
- ✅ **چندزبانه**: فارسی، عربی، انگلیسی، آلمانی و...
- ✅ **RTL Support**: پشتیبانی کامل از راست به چپ
- ✅ **Localization**: محلیسازی کامل

## 🏗️ معماری

### **Clean Architecture**
```
├── Domain Layer (Business Logic)
│   ├── Entities
│   ├── Value Objects
│   └── Business Rules
├── Application Layer
│   ├── Use Cases
│   ├── DTOs
│   └── Services
├── Infrastructure Layer
│   ├── Repositories
│   ├── External APIs
│   └── Database
└── Presentation Layer
    ├── Controllers
    ├── Middleware
    └── Resources
```

### **Design Patterns**
- 🏭 **Factory Pattern**: ایجاد objects
- 👁️ **Observer Pattern**: event handling
- 🎯 **Strategy Pattern**: الگوریتمهای مختلف
- 📦 **Repository Pattern**: دسترسی به داده
- 🔄 **CQRS**: جداسازی Command و Query
- 📡 **Event Sourcing**: ذخیره رویدادها

## ⚡ نصب سریع

### **پیشنیازها**
- 🐘 **PHP** >= 8.2
- 🎼 **Composer** >= 2.0
- 🗄️ **MySQL** >= 8.0 یا **PostgreSQL** >= 13
- 🔴 **Redis** >= 7.0
- 🎬 **FFmpeg** (برای پردازش ویدیو)
- 📦 **Node.js** >= 18 (برای frontend assets)

### **نصب خودکار**
```bash
# کلون پروژه
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend

# نصب خودکار (تمام مراحل)
composer run setup

# یا نصب دستی
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build

# اجرای سرور توسعه
composer run dev
```

### **Docker (توصیه شده)**
```bash
# اجرای کامل با Docker
docker-compose up -d

# اجرای با streaming services
docker-compose -f docker-compose.streaming.yml up -d
```

## 🔧 پیکربندی تفصیلی

### **متغیرهای محیطی (.env)**
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wonderway
DB_USERNAME=<username>
DB_PASSWORD=<password>

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting (Reverb)
REVERB_APP_ID=<app_id>
REVERB_APP_KEY=<app_key>
REVERB_APP_SECRET=<app_secret>
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# File Storage
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=<access_key>
AWS_SECRET_ACCESS_KEY=<secret_key>
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=<bucket_name>

# Streaming
STREAMING_SERVER_URL=rtmp://localhost:1935
STREAMING_HLS_PATH=/var/www/html/hls

# Search
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700

# Security
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

## 📡 API Reference

### **Authentication**
```http
POST   /api/register          # ثبت نام
POST   /api/login             # ورود
POST   /api/logout            # خروج
POST   /api/2fa/enable        # فعالسازی 2FA
POST   /api/2fa/verify        # تأیید 2FA
```

### **Posts & Content**
```http
GET    /api/posts             # لیست پستها
POST   /api/posts             # ایجاد پست
GET    /api/posts/{id}        # نمایش پست
PUT    /api/posts/{id}        # ویرایش پست
DELETE /api/posts/{id}        # حذف پست
POST   /api/posts/{id}/like   # لایک پست
POST   /api/posts/{id}/repost # ریپست
```

### **Live Features**
```http
POST   /api/streams           # شروع استریم
GET    /api/streams/live      # استریمهای زنده
POST   /api/spaces            # ایجاد Space
GET    /api/spaces/active     # Spaceهای فعال
POST   /api/stories           # ایجاد Story
GET    /api/stories/timeline  # تایملاین Stories
```

### **مستندات کامل**
- 📚 **Swagger UI**: `/api/documentation`
- 📄 **OpenAPI Spec**: `/docs/api-spec.yaml`
- 🔗 **Postman Collection**: `WonderWay.postman_collection.json`

## 🧪 تست و کیفیت کد

### **اجرای تستها**
```bash
# تمام تستها
php artisan test

# تست با coverage
composer run test-coverage

# تستهای مشخص
php artisan test --filter=PostTest
```

### **کیفیت کد**
```bash
# بررسی code style
composer run cs-check

# اصلاح خودکار code style
composer run cs-fix
```

### **آمار تست**
- ✅ **436 تست** (100% موفق)
- 📊 **Coverage**: 95%+
- 🏃 **Feature Tests**: 411
- 🔬 **Unit Tests**: 25

## 🚀 استقرار

### **Production Deployment**
```bash
# استقرار خودکار
./deploy-production.sh

# یا دستی
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
php artisan up
```

## 📊 وضعیت ورژنبندی

### **✅ استاندارد Semantic Versioning**
پروژه از [Semantic Versioning 2.0.0](https://semver.org/) پیروی میکند:

- **MAJOR.MINOR.PATCH** (مثال: 1.0.0)
- **Major**: تغییرات ناسازگار API
- **Minor**: ویژگیهای جدید سازگار
- **Patch**: رفع باگها

### **📋 نسخه فعلی: v1.0.0**
- ✅ **Changelog**: کامل و بهروز
- ✅ **Git Tags**: استاندارد
- ✅ **Release Notes**: مفصل
- ✅ **Migration Path**: مشخص

## ⚠️ لایسنس و حقوق

### **🔒 Proprietary License**
این پروژه کاملاً انحصاری و محافظت شده است:

- ❌ **استفاده غیرمجاز ممنوع**
- ❌ **کپی یا توزیع ممنوع**
- ❌ **مهندسی معکوس ممنوع**
- ⚖️ **نقض = پیگرد قانونی**

### **📧 تماس برای مجوز**
- **Legal**: legal@wonderway.com
- **Business**: business@wonderway.com
- **Technical**: tech@wonderway.com

---

<div align="center">

### 🛡️ محافظت شده توسط قوانین کپی رایت بین‌المللی

**© 2025 WonderWay. All Rights Reserved.**

**استفاده غیرمجاز ممنوع و قابل پیگرد قانونی است**

</div>
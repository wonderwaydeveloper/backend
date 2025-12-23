# WonderWay

شبکه اجتماعی مدرن با Laravel 12

## ویژگی‌ها

- پست‌گذاری (متن، تصویر، ویدیو)
- سیستم لایک و کامنت
- پیام‌های خصوصی
- Live Streaming
- Spaces (اتاق‌های صوتی)

## پیش‌نیازها

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Redis >= 7.0
- FFmpeg

## نصب

```bash
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## پیکربندی

فایل `.env` را ویرایش کنید:

```env
DB_DATABASE=wonderway
DB_USERNAME=<username>
DB_PASSWORD=<password>
REDIS_HOST=127.0.0.1
```

## API

### Authentication
```http
POST /api/register
POST /api/login
```

### Posts
```http
GET  /api/posts
POST /api/posts
```

## تست

```bash
php artisan test
```

## لایسنس

MIT
# مستندات WonderWay

خوش آمدید به مستندات کامل پلتفرم شبکه اجتماعی WonderWay. این مجموعه شامل تمام اطلاعات لازم برای درک، توسعه، دیپلوی و نگهداری سیستم است.

## 📚 فهرست مستندات

### 🚀 شروع سریع
- **[README.md](../README.md)** - معرفی کلی پروژه و راهنمای نصب
- **[CONTRIBUTING.md](../CONTRIBUTING.md)** - راهنمای مشارکت در پروژه
- **[CHANGELOG.md](../CHANGELOG.md)** - تاریخچه تغییرات و نسخهها

### 📚 مستندات تخصصی
- **[API.md](./API.md)** - مستندات کامل API
- **[ARCHITECTURE.md](./ARCHITECTURE.md)** - معماری کامل سیستم
- **[SECURITY.md](./SECURITY.md)** - راهنمای کامل امنیت
- **[DEPLOYMENT.md](./DEPLOYMENT.md)** - راهنمای دیپلویمنت
- **[FRONTEND_GUIDE.md](./FRONTEND_GUIDE.md)** - راهنمای توسعه Frontend و Admin Panel
- **[FILAMENT_SETUP.md](./FILAMENT_SETUP.md)** - راهنمای پیادهسازی Filament Admin Panel

## 🎯 برای کاربران مختلف

### 👨‍💻 توسعه‌دهندگان
```
1. شروع با README.md
2. نصب محیط توسعه
3. مطالعه ARCHITECTURE.md
4. بررسی API.md
5. مشارکت با CONTRIBUTING.md
```

### 🔧 DevOps Engineers
```
1. مطالعه DEPLOYMENT.md
2. بررسی SECURITY.md
3. پیکربندی monitoring
4. تنظیم CI/CD
5. برنامه‌ریزی backup
```

### 🏢 مدیران پروژه
```
1. بررسی README.md
2. مطالعه CHANGELOG.md
3. درک ARCHITECTURE.md
4. بررسی SECURITY.md
5. برنامه‌ریزی roadmap
```

### 🎨 Frontend Developers
```
1. مطالعه API.md
2. بررسی authentication
3. درک WebSocket
4. استفاده از SDK
5. تست با Postman
```

## 🛠️ ابزارهای مفید

### مستندات آنلاین
- **Swagger UI**: `http://localhost:8000/api/documentation`
- **GraphQL Playground**: `http://localhost:8000/graphql-playground`
- **Telescope**: `http://localhost:8000/telescope`

### ابزارهای توسعه
- **Postman Collection**: [دانلود](../postman-collection.json)
- **Insomnia Workspace**: [دانلود](../insomnia-workspace.json)
- **VS Code Extensions**: Laravel Extension Pack

### مانیتورینگ
- **Grafana Dashboard**: `http://localhost:3000`
- **Prometheus Metrics**: `http://localhost:9090`
- **Kibana Logs**: `http://localhost:5601`

## 📖 راهنماهای گام به گام

### نصب و راه‌اندازی اولیه

#### 1. پیش‌نیازها
```bash
# بررسی نسخه PHP
php --version  # باید 8.2+ باشد

# بررسی Composer
composer --version

# بررسی Node.js
node --version  # باید 18+ باشد
```

#### 2. کلون و نصب
```bash
git clone https://github.com/wonderway/backend.git
cd wonderway-backend
composer install
npm install
cp .env.example .env
php artisan key:generate
```

#### 3. پیکربندی دیتابیس
```bash
# ویرایش .env
DB_CONNECTION=mysql
DB_DATABASE=wonderway
DB_USERNAME=your_username
DB_PASSWORD=your_password

# اجرای migrations
php artisan migrate
php artisan db:seed
```

#### 4. راه‌اندازی سرویس‌ها
```bash
# سرور اصلی
php artisan serve

# Queue worker
php artisan queue:work

# WebSocket server
php artisan reverb:start
```

### توسعه ویژگی جدید

#### 1. ایجاد Branch
```bash
git checkout -b feature/new-feature
```

#### 2. ایجاد Migration
```bash
php artisan make:migration create_new_table
php artisan migrate
```

#### 3. ایجاد Model
```bash
php artisan make:model NewModel -a
```

#### 4. ایجاد Controller
```bash
php artisan make:controller Api/NewController --api
```

#### 5. تست
```bash
php artisan make:test NewFeatureTest
php artisan test
```

## 🔍 عیب‌یابی رایج

### مشکلات نصب

#### خطای Composer
```bash
# پاک کردن cache
composer clear-cache
composer install --no-cache

# بروزرسانی dependencies
composer update
```

#### خطای NPM
```bash
# پاک کردن node_modules
rm -rf node_modules package-lock.json
npm install
```

#### خطای مجوزها
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
chmod -R 755 storage bootstrap/cache
```

### مشکلات Runtime

#### خطای 500
```bash
# بررسی لاگ‌ها
tail -f storage/logs/laravel.log

# پاک کردن cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

#### مشکل دیتابیس
```bash
# تست اتصال
php artisan tinker
>>> DB::connection()->getPdo();

# بازنشانی دیتابیس
php artisan migrate:fresh --seed
```

#### مشکل Queue
```bash
# راه‌اندازی مجدد worker
php artisan queue:restart

# بررسی failed jobs
php artisan queue:failed
```

## 📊 آمار و متریک‌ها

### Performance Benchmarks
- **Response Time**: < 200ms (95th percentile)
- **Throughput**: 1000+ requests/second
- **Database Queries**: < 50ms average
- **Memory Usage**: < 128MB per request

### Test Coverage
- **Unit Tests**: 90%+
- **Feature Tests**: 85%+
- **Integration Tests**: 80%+
- **E2E Tests**: 70%+

### Security Metrics
- **Vulnerability Scan**: Weekly
- **Dependency Audit**: Daily
- **Security Headers**: A+ Rating
- **SSL/TLS**: A+ Rating

## 🤝 کمک و پشتیبانی

### کانال‌های ارتباطی
- **GitHub Issues**: [مشکلات فنی](https://github.com/wonderway/backend/issues)
- **Discord**: [گفتگوی عمومی](https://discord.gg/wonderway)
- **Email**: [پشتیبانی](mailto:support@wonderway.com)
- **Documentation**: [سایت مستندات](https://docs.wonderway.com)

### نحوه دریافت کمک

#### برای باگ‌ها
1. جستجو در Issues موجود
2. ایجاد Issue جدید با template
3. ارائه اطلاعات کامل
4. پیگیری پاسخ‌ها

#### برای سوالات
1. بررسی مستندات
2. جستجو در Discord
3. پرسیدن در کانال مناسب
4. صبر برای پاسخ جامعه

#### برای درخواست ویژگی
1. بحث در Discord
2. ایجاد Feature Request
3. توضیح use case
4. صبر برای بررسی تیم

## 🔄 بروزرسانی مستندات

این مستندات به طور مداوم بروزرسانی می‌شوند. برای اطلاع از آخرین تغییرات:

- **Watch** کردن repository در GitHub
- **Subscribe** کردن به newsletter
- **Follow** کردن در شبکه‌های اجتماعی

### آخرین بروزرسانی
- **تاریخ**: 2024-01-28
- **نسخه**: 3.2.0
- **تغییرات**: اضافه شدن Community Notes و بهبود Performance

---

**نکته**: این مستندات زنده هستند و با توسعه پروژه بروزرسانی می‌شوند. لطفاً همیشه آخرین نسخه را مطالعه کنید.
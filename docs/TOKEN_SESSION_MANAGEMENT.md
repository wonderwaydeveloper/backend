# Token & Session Management System

## نمای کلی
سیستم جامع مدیریت توکن و جلسات برای پاکسازی خودکار، محدودیت جلسات همزمان، و مدیریت چرخه حیات توکنها.

## فایلهای ایجاد شده

### 1. TokenManagementService
**مسیر**: `app/Services/TokenManagementService.php`

**قابلیتها**:
- پاکسازی توکنهای منقضی شده (access tokens, device tokens, password reset, verification)
- اعمال محدودیت جلسات همزمان
- لغو تمام جلسات کاربر (به جز جلسه فعلی)
- بهروزرسانی فعالیت دستگاه
- دریافت جلسات فعال کاربر
- لغو جلسه خاص
- بررسی نیاز به refresh توکن
- refresh خودکار access token

**متدهای کلیدی**:
```php
cleanupExpiredTokens(): array
enforceConcurrentSessionLimits(User $user): void
revokeAllUserSessions(User $user, ?string $exceptTokenId = null): int
updateDeviceActivity(User $user, string $fingerprint, array $deviceInfo): void
getUserActiveSessions(User $user): array
revokeSession(User $user, string $tokenId): bool
shouldRefreshToken(PersonalAccessToken $token): bool
refreshAccessToken(User $user, PersonalAccessToken $currentToken): string
```

### 2. CleanupTokensCommand
**مسیر**: `app/Console/Commands/CleanupTokensCommand.php`

**استفاده**:
```bash
# Dry run (نمایش بدون حذف)
php artisan auth:cleanup-tokens --dry-run

# اجرای واقعی
php artisan auth:cleanup-tokens
```

**خروجی**:
- تعداد توکنهای حذف شده به تفکیک نوع
- آمار کامل پاکسازی

### 3. TokenCleanupJob
**مسیر**: `app/Jobs/TokenCleanupJob.php`

**ویژگیها**:
- اجرا در پسزمینه (Queue)
- Timeout: 5 دقیقه
- تلاش مجدد: 3 بار
- لاگ کامل عملیات

### 4. AutoTokenRefresh Middleware
**مسیر**: `app/Http/Middleware/AutoTokenRefresh.php`

**عملکرد**:
- بررسی خودکار انقضای توکن
- refresh توکن قبل از انقضا (5 دقیقه قبل)
- ارسال توکن جدید در هدر پاسخ

**هدرهای پاسخ**:
- `X-New-Token`: توکن جدید
- `X-Token-Refreshed`: true

## تنظیمات

### محدودیت جلسات همزمان
**فایل**: `config/auth_security.php`

```php
'session' => [
    'timeout' => 7200, // 2 ساعت
    'concurrent_limit' => 3, // حداکثر 3 جلسه همزمان
    'fingerprint_validation' => true,
],
```

### عمر توکنها
```php
'tokens' => [
    'access_token_lifetime' => 3600, // 1 ساعت
    'refresh_token_lifetime' => 604800, // 7 روز
    'remember_token_lifetime' => 1209600, // 14 روز
    'auto_refresh_threshold' => 300, // 5 دقیقه قبل از انقضا
],
```

## زمانبندی خودکار

**فایل**: `routes/console.php`

```php
// پاکسازی هر ساعت (Job)
Schedule::job(\App\Jobs\TokenCleanupJob::class)
    ->hourly()
    ->withoutOverlapping();

// پاکسازی روزانه (Command)
Schedule::command('auth:cleanup-tokens')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();
```

## API Endpoints جدید

### دریافت جلسات فعال
```http
GET /api/auth/sessions
Authorization: Bearer {token}
```

**پاسخ**:
```json
{
  "active_tokens": 2,
  "active_devices": 2,
  "sessions": [
    {
      "id": "123",
      "name": "auth_token",
      "last_used_at": "2024-01-01 12:00:00",
      "expires_at": "2024-01-01 13:00:00",
      "device_info": {
        "device_name": "Chrome on Windows",
        "browser": "Chrome",
        "os": "Windows",
        "ip_address": "192.168.1.0/24"
      }
    }
  ]
}
```

### لغو جلسه خاص
```http
DELETE /api/auth/sessions/{token_id}
Authorization: Bearer {token}
```

### خروج از همه دستگاهها
```http
POST /api/auth/logout-all
Authorization: Bearer {token}
```

**پاسخ**:
```json
{
  "message": "Logged out from all devices",
  "sessions_revoked": 3
}
```

## بهبودهای AuthService

متدهای جدید اضافه شده:
```php
logoutFromAllDevices(User $user): int
getUserSessions(User $user): array
revokeSession(User $user, string $tokenId): bool
```

## بهبودهای DeviceToken Model

### Scopes جدید:
```php
DeviceToken::active()->get()
DeviceToken::inactive()->get()
DeviceToken::trusted()->get()
DeviceToken::recentlyUsed(7)->get()
```

### متدهای کمکی:
```php
$device->markInactive()
$device->updateLastUsed()
$device->isStale() // بررسی عدم استفاده 30+ روز
```

## قوانین پاکسازی

### Access Tokens
- حذف توکنهای منقضی شده (`expires_at < now()`)

### Device Tokens
- حذف دستگاههای غیرفعال (`active = false`)
- حذف دستگاههای استفاده نشده 30+ روز

### Password Reset Tokens
- حذف توکنهای قدیمیتر از 15 دقیقه

### Email Verification Tokens
- حذف توکنهای قدیمیتر از 24 ساعت

## امنیت

### محدودیت جلسات همزمان
- حداکثر 3 جلسه همزمان (قابل تنظیم)
- حفظ جدیدترین جلسات
- حذف خودکار جلسات قدیمی

### Device Fingerprinting
- ردیابی دستگاهها با fingerprint
- بهروزرسانی خودکار last_used_at
- مدیریت دستگاههای معتمد

### Token Refresh
- Refresh خودکار 5 دقیقه قبل از انقضا
- جلوگیری از قطع ناگهانی جلسه
- لاگ خطاهای refresh

## لاگها

### TokenCleanupJob
```php
Log::info('Token cleanup completed', [
    'stats' => [...],
    'total_cleaned' => 10
]);

Log::error('Token cleanup job failed', [
    'error' => '...',
    'trace' => '...'
]);
```

### AutoTokenRefresh
```php
Log::warning('Token refresh failed', [
    'user_id' => 123,
    'error' => '...'
]);
```

## تست و نظارت

### تست دستی
```bash
# Dry run
php artisan auth:cleanup-tokens --dry-run

# اجرای واقعی
php artisan auth:cleanup-tokens
```

### نظارت بر Job
```bash
php artisan queue:work
php artisan queue:failed
```

### بررسی Schedule
```bash
php artisan schedule:list
php artisan schedule:run
```

## بهترین روشها

1. **اجرای Schedule**: مطمئن شوید cron job تنظیم شده است
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Queue Worker**: برای اجرای Job در پسزمینه
   ```bash
   php artisan queue:work --tries=3 --timeout=300
   ```

3. **Monitoring**: نظارت بر لاگها برای شناسایی مشکلات
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Database Indexes**: اطمینان از وجود index بر روی:
   - `personal_access_tokens.expires_at`
   - `device_tokens.last_used_at`
   - `device_tokens.active`
   - `password_reset_tokens.created_at`

## مزایا

✅ پاکسازی خودکار توکنهای منقضی شده
✅ کاهش حجم دیتابیس
✅ بهبود امنیت با محدودیت جلسات
✅ مدیریت بهتر دستگاهها
✅ Refresh خودکار توکنها
✅ API کامل برای مدیریت جلسات
✅ لاگ و نظارت جامع
✅ قابلیت اجرا در پسزمینه
✅ Dry run برای تست

## نتیجه

سیستم مدیریت توکن و جلسات به طور کامل پیادهسازی شد و شامل:
- پاکسازی خودکار و زمانبندی شده
- محدودیت جلسات همزمان
- API کامل برای مدیریت
- Middleware برای refresh خودکار
- لاگ و نظارت جامع
- تست و dry-run mode
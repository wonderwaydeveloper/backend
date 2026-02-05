# Audit Logging System

## نمای کلی
سیستم جامع audit logging برای ثبت و نظارت بر تمام عملیات امنیتی، تشخیص ناهنجاریها، و ارائه گزارشات تفصیلی.

## فایلهای ایجاد شده/بهبود یافته

### 1. AuditTrailService (بهبود یافته)
**مسیر**: `app/Services/AuditTrailService.php`

**قابلیتهای جدید**:
- لاگ رویدادهای احراز هویت با جزئیات کامل
- تشخیص ناهنجاریهای رفتاری کاربران
- محاسبه سطح تهدید و ریسک
- خلاصه فعالیت کاربران
- هشدارهای real-time برای فعالیتهای پرخطر
- پشتیبانی از IP های proxy و load balancer

**متدهای کلیدی جدید**:
```php
logAuthEvent(string $event, User $user, array $context = []): void
getSecurityEvents(int $days = 7): Collection
getHighRiskActivities(int $days = 1): Collection
getUserActivitySummary(int $userId, int $days = 30): array
detectAnomalousActivity(int $userId): array
```

### 2. AuditMiddleware
**مسیر**: `app/Http/Middleware/AuditMiddleware.php`

**عملکرد**:
- لاگ خودکار درخواستهای API
- نقشهبرداری HTTP methods به audit actions
- اندازهگیری زمان پاسخ
- فیلتر کردن endpoints غیرضروری

### 3. AuditController
**مسیر**: `app/Http/Controllers/Api/AuditController.php`

**Endpoints**:
- `GET /api/auth/audit/my-activity` - فعالیتهای کاربر
- `GET /api/auth/audit/anomalies` - تشخیص ناهنجاری
- `GET /api/auth/audit/security-events` - رویدادهای امنیتی (admin)
- `GET /api/auth/audit/high-risk` - فعالیتهای پرخطر (admin)
- `GET /api/auth/audit/statistics` - آمار کلی (admin)

### 4. AuditLogPolicy
**مسیر**: `app/Policies/AuditLogPolicy.php`

**کنترل دسترسی**:
- کاربران: مشاهده audit logs خودشان
- Admin/Security Admin: مشاهده تمام logs
- Super Admin: حذف logs (برای پاکسازی)

### 5. CleanupAuditLogsCommand
**مسیر**: `app/Console/Commands/CleanupAuditLogsCommand.php`

**استفاده**:
```bash
# Dry run
php artisan audit:cleanup --dry-run

# پاکسازی logs قدیمیتر از 90 روز
php artisan audit:cleanup --days=90

# پاکسازی logs قدیمیتر از 30 روز
php artisan audit:cleanup --days=30
```

## انواع Audit Actions

### Authentication Events
```php
'auth.login'              // ورود موفق
'auth.logout'             // خروج
'auth.register'           // ثبت نام
'auth.failed_login'       // ورود ناموفق
'auth.password_change'    // تغییر رمز عبور
'auth.password_reset'     // بازنشانی رمز عبور
'auth.2fa_enabled'        // فعالسازی 2FA
'auth.2fa_disabled'       // غیرفعالسازی 2FA
'auth.device_verified'    // تایید دستگاه
'auth.session_revoked'    // لغو جلسه
'auth.logout_all'         // خروج از همه دستگاهها
```

### Security Events
```php
'security.suspicious_activity'    // فعالیت مشکوک
'security.rate_limit_exceeded'    // تجاوز از حد درخواست
'security.brute_force'           // حمله brute force
'security.sql_injection'         // تلاش SQL injection
'security.xss_attempt'           // تلاش XSS
'security.csrf_violation'        // نقض CSRF
'security.unauthorized_access'   // دسترسی غیرمجاز
```

### User Management
```php
'user.profile_update'     // بهروزرسانی پروفایل
'user.delete'            // حذف کاربر
'user.suspend'           // تعلیق کاربر
'user.ban'               // مسدود کردن کاربر
'user.role_change'       // تغییر نقش
```

### Content Operations
```php
'post.create'            // ایجاد پست
'post.update'            // ویرایش پست
'post.delete'            // حذف پست
'post.moderate'          // مدیریت محتوا
```

## API Endpoints

### دریافت فعالیتهای کاربر
```http
GET /api/auth/audit/my-activity?action=auth.login&days=30
Authorization: Bearer {token}
```

**پاسخ**:
```json
{
  "audit_trail": [
    {
      "id": 123,
      "action": "auth.login",
      "timestamp": "2024-01-01 12:00:00",
      "ip_address": "192.168.1.100",
      "risk_level": "low",
      "data": {
        "login_method": "credentials",
        "session_count": 2
      }
    }
  ],
  "summary": {
    "total_activities": 45,
    "by_action": {
      "auth.login": 10,
      "post.create": 15
    },
    "by_risk_level": {
      "low": 40,
      "medium": 4,
      "high": 1
    },
    "unique_ips": 3,
    "last_activity": "2024-01-01 12:00:00",
    "suspicious_count": 1
  }
}
```

### تشخیص ناهنجاری
```http
GET /api/auth/audit/anomalies
Authorization: Bearer {token}
```

**پاسخ**:
```json
{
  "anomalies": [
    {
      "type": "new_ip_addresses",
      "count": 2,
      "ips": ["192.168.1.200", "10.0.0.50"]
    },
    {
      "type": "high_activity_volume",
      "recent_count": 50,
      "average": 20
    }
  ],
  "anomaly_count": 2,
  "user_id": 123
}
```

### آمار امنیتی (Admin)
```http
GET /api/auth/audit/statistics?days=7
Authorization: Bearer {admin_token}
```

**پاسخ**:
```json
{
  "statistics": {
    "total_events": 1250,
    "unique_users": 45,
    "unique_ips": 120,
    "high_risk_count": 8,
    "security_events_count": 15
  },
  "top_actions": [
    {"action": "auth.login", "count": 200},
    {"action": "post.create", "count": 150}
  ],
  "period_days": 7
}
```

## سطوح ریسک

### Low Risk
- ورود/خروج عادی
- ایجاد محتوا
- بهروزرسانی پروفایل

### Medium Risk
- تغییر رمز عبور
- حذف محتوا
- ورود ناموفق
- تجاوز از rate limit

### High Risk
- حذف کاربر
- مسدود کردن کاربر
- تلاش SQL injection
- حمله brute force
- صادرات داده

## تشخیص ناهنجاری

### IP جدید
- شناسایی IP های جدید در 7 روز اخیر
- مقایسه با 30 روز گذشته

### حجم فعالیت غیرعادی
- مقایسه فعالیت 7 روز اخیر با میانگین تاریخی
- هشدار برای فعالیت 2 برابر بیشتر از عادی

### الگوهای مشکوک
- ورودهای متعدد ناموفق
- دسترسی به منابع حساس
- فعالیت در ساعات غیرعادی

## هشدارهای Real-time

### Cache-based Rate Limiting
- جلوگیری از spam هشدارها
- محدودیت 1 هشدار در 5 دقیقه برای هر action

### سطوح هشدار
```php
'critical' => ['brute_force', 'sql_injection', 'xss_attempt']
'high'     => ['suspicious_activity', 'rate_limit_exceeded']
'medium'   => ['failed_login', 'unauthorized_access']
```

## پاکسازی خودکار

### زمانبندی
```php
// هفتگی - یکشنبهها ساعت 3 صبح
Schedule::command('audit:cleanup --days=90')
    ->weekly()
    ->sundays()
    ->at('03:00');
```

### قوانین نگهداری
- **عادی**: 90 روز
- **High Risk**: نگهداری دائمی
- **Security Events**: نگهداری دائمی

## امنیت داده

### Sanitization
- حذف اطلاعات حساس (password, token, secret)
- نقشهبرداری recursive برای arrays تودرتو

### کنترل دسترسی
- Policy-based authorization
- Role-based access control
- User isolation (فقط logs خودشان)

## عملکرد

### Database Indexes
```sql
-- Composite indexes برای کوئریهای سریع
INDEX(user_id, timestamp)
INDEX(action, timestamp)  
INDEX(risk_level, timestamp)
INDEX(ip_address, timestamp)
```

### Query Optimization
- استفاده از eager loading
- محدودیت زمانی برای کوئریها
- Pagination برای نتایج بزرگ

## نظارت و گزارشگیری

### Logs
```php
Log::info('Audit logging completed', ['action' => '...']);
Log::warning('High-risk activity detected', [...]);
Log::critical('Security event requires investigation', [...]);
```

### Metrics
- تعداد کل رویدادها
- کاربران منحصربهفرد
- IP های منحصربهفرد  
- رویدادهای پرخطر
- رویدادهای امنیتی

## Integration Points

### AuthService
- لاگ خودکار رویدادهای احراز هویت
- ثبت جزئیات ورود/خروج
- ردیابی تغییرات امنیتی

### SecurityMonitoringService
- ارسال رویدادهای امنیتی به audit
- هماهنگی با rate limiting
- تشخیص تهدیدات

### Middleware
- لاگ خودکار API requests
- اندازهگیری performance
- فیلتر کردن noise

## بهترین روشها

### 1. Performance
```php
// استفاده از queue برای audit logging
dispatch(new LogAuditEvent($action, $data));

// Batch processing برای حجم بالا
AuditLog::insert($batchData);
```

### 2. Storage
```php
// پارتیشن بندی جدول بر اساس تاریخ
// Archive قدیمیترین داده ها
// Compression برای داده های آرشیو
```

### 3. Security
```php
// Hash کردن IP های حساس
'ip_address' => hash('sha256', $ip . config('app.key'))

// Encryption برای داده های بسیار حساس
'sensitive_data' => encrypt($data)
```

## مزایا

✅ ردیابی کامل فعالیتهای کاربران
✅ تشخیص خودکار ناهنجاریها  
✅ هشدارهای real-time
✅ گزارشگیری تفصیلی
✅ کنترل دسترسی دقیق
✅ پاکسازی خودکار
✅ عملکرد بهینه با indexing
✅ امنیت داده با sanitization
✅ API کامل برای مدیریت
✅ سازگاری با compliance requirements

## نتیجه

سیستم audit logging به طور کامل پیادهسازی شد و شامل:
- ثبت جامع رویدادهای امنیتی
- تشخیص ناهنجاریهای رفتاری
- API کامل برای گزارشگیری
- کنترل دسترسی role-based
- پاکسازی خودکار و بهینهسازی
- هشدارهای real-time
- مستندات کامل و راهنمای استفاده
# نقشه راه ادغام فایلهای Config

## 📊 وضعیت فعلی

### آمار کلی
- **تعداد فایل**: 12 فایل
- **خطوط کد**: 872 خط
- **استفاده در کد**: 308 مورد
- **تکرار**: 3 مورد (58 خط)
- **Dead Code**: 58 خط (6.6%)

### فایلهای موجود

| # | فایل | خطوط | استفاده | وضعیت |
|---|------|------|----------|-------|
| 1 | authentication.php | 182 | 48 | ⚠️ دارای Dead Code |
| 2 | security.php | 205 | 49 | ✅ فعال |
| 3 | moderation.php | 41 | 26 | ✅ فعال |
| 4 | limits.php | 73 | 2 | ✅ فعال |
| 5 | monetization.php | 103 | 8 | ✅ فعال |
| 6 | pagination.php | 23 | 34 | ✅ فعال |
| 7 | cache_ttl.php | 24 | 23 | ✅ فعال |
| 8 | validation.php | 89 | 105 | ✅ فعال |
| 9 | media.php | 50 | 7 | ✅ فعال |
| 10 | polls.php | 10 | 4 | ✅ فعال |
| 11 | posts.php | 21 | 2 | ✅ فعال |
| 12 | status.php | 51 | 0 | ✅ Constants |

---

## 🎯 هدف نهایی

### ساختار پیشنهادی (5 فایل)

```
config/
├── security.php        (280 خط) - امنیت، احراز هویت، مدیریت
├── limits.php          (200 خط) - محدودیتها، نقشها، صفحهبندی
├── content.php         (120 خط) - اعتبارسنجی، مدیا، محتوا
├── performance.php     (30 خط)  - کش، بهینهسازی
└── status.php          (51 خط)  - ثابتهای وضعیت
```

**نتیجه**: 5 فایل، 681 خط (کاهش 22%)

---

## 🔍 تحلیل تکرارها

### 1. Rate Limiting (CRITICAL)

**تکرار بین**: `authentication.php` ↔ `security.php`

**authentication.php** (خطوط 27-73):
```php
'rate_limiting' => [
    'login' => ['max_attempts' => 5, 'window_minutes' => 15],
    'register' => ['max_attempts' => 3, 'window_minutes' => 60],
    // ... 30+ endpoints
]
```
- **استفاده**: ❌ فقط در `SecurityAudit.php` برای نمایش
- **وضعیت**: DEAD CODE (47 خط)

**security.php** (خطوط 82-169):
```php
'rate_limiting' => [
    'auth' => ['login' => [...], 'register' => [...]],
    'search' => [...],
    'trending' => [...],
    // ...
]
```
- **استفاده**: ✅ در `RateLimitingService.php` (7 مورد)
- **وضعیت**: ACTIVE

**راهحل**: حذف `authentication.rate_limiting`

---

### 2. Cache TTL (PARTIAL)

**تکرار بین**: `authentication.php` ↔ `cache_ttl.php`

**authentication.php** (خطوط 177-182):
```php
'cache' => [
    'trending_ttl' => 900,
    'timeline_ttl' => 300,
    'user_ttl' => 600,
    'post_ttl' => 1800,
]
```
- **استفاده**: ✅ فقط `user_ttl` و `post_ttl` در `CacheOptimizationService.php`
- **وضعیت**: PARTIAL DUPLICATION

**cache_ttl.php**:
```php
'ttl' => [
    'timeline' => 300,
    'trending' => 3600,  // ⚠️ مقدار متفاوت!
    'post' => 300,       // ⚠️ مقدار متفاوت!
    // ... 18 مورد دیگر
]
```
- **استفاده**: ✅ در 15+ سرویس (23 مورد)
- **وضعیت**: ACTIVE

**راهحل**: ادغام در `cache_ttl.php` و حذف از `authentication.php`

---

### 3. File Upload (DEAD CODE)

**authentication.php** (خطوط 147-151):
```php
'file_upload' => [
    'allowed_extensions' => ['jpg', 'jpeg', 'png', ...],
    'scan_for_malware' => true,
    'max_video_duration' => 300,
]
```
- **استفاده**: ❌ هیچ استفادهای نمیشه
- **وضعیت**: DEAD CODE (5 خط)

**راهحل**: حذف کامل

---

## 📋 نقشه راه اجرایی

### Phase 1: آمادهسازی (Pre-Migration)

#### Step 1.1: ایجاد Branch
```bash
git checkout -b config-consolidation
```

#### Step 1.2: Backup
```bash
mkdir config_backup
copy config\*.php config_backup\
```

#### Step 1.3: ایجاد Test Script
```bash
php artisan make:command ValidateConfigMigration
```

---

### Phase 2: ادغام Security Domain

**هدف**: ادغام `authentication.php` + `security.php` + `moderation.php` → `security.php`

#### Step 2.1: ایجاد security.php جدید

**ساختار**:
```php
return [
    // Authentication (از authentication.php)
    'password' => [...],              // 8 خط
    'tokens' => [...],                // 6 خط
    'session' => [...],               // 5 خط
    'email' => [...],                 // 18 خط
    'device' => [...],                // 9 خط
    'social' => [...],                // 12 خط
    'age_restrictions' => [...],      // 3 خط
    
    // Security (از security.php فعلی)
    'rate_limiting' => [...],         // 88 خط
    'threat_detection' => [...],      // 15 خط
    'bot_detection' => [...],         // 30 خط
    'monitoring' => [...],            // 20 خط
    'captcha' => [...],               // 3 خط
    'file_security' => [...],         // 3 خط
    'password_security' => [...],     // 12 خط
    'waf' => [...],                   // 20 خط
    
    // Moderation (از moderation.php)
    'spam' => [...],                  // 28 خط
];
```

**خطوط**: 280 خط

#### Step 2.2: حذف بخشهای تکراری
- ❌ حذف `authentication.rate_limiting` (47 خط)
- ❌ حذف `authentication.cache` (6 خط)
- ❌ حذف `authentication.file_upload` (5 خط)

#### Step 2.3: Update References

**فایلهای نیاز به تغییر** (48 مورد):

```php
// BEFORE
config('authentication.password.security.min_length')
config('authentication.tokens.access_lifetime_seconds')
config('authentication.email.verification_expire_minutes')
config('authentication.device.max_devices')
config('authentication.social.google.client_id')

// AFTER
config('security.password.security.min_length')
config('security.tokens.access_lifetime_seconds')
config('security.email.verification_expire_minutes')
config('security.device.max_devices')
config('security.social.google.client_id')
```

**لیست فایلها**:
1. `app/Console/Commands/SecurityAudit.php` (2 مورد)
2. `app/Console/Commands/TestEmailTemplatesCommand.php` (1 مورد)
3. `app/Http/Controllers/Api/DeviceController.php` (3 مورد)
4. `app/Http/Controllers/Api/SocialAuthController.php` (2 مورد)
5. `app/Http/Middleware/SecurityHeaders.php` (1 مورد)
6. `app/Http/Middleware/UnifiedSecurityMiddleware.php` (2 مورد)
7. `app/Models/DeviceToken.php` (1 مورد)
8. `app/Notifications/ResetPasswordNotification.php` (1 مورد)
9. `app/Rules/MinimumAge.php` (1 مورد)
10. `app/Rules/SecureEmail.php` (1 مورد)
11. `app/Services/AuthService.php` (8 مورد)
12. `app/Services/DeviceFingerprintService.php` (2 مورد)
13. `app/Services/EmailService.php` (4 مورد)
14. `app/Services/PasswordSecurityService.php` (6 مورد)
15. `app/Services/SessionTimeoutService.php` (2 مورد)
16. `app/Services/SmsService.php` (1 مورد)
17. `app/Services/VerificationCodeService.php` (3 مورد)

#### Step 2.4: Testing
```bash
php artisan test --filter=Authentication
php artisan test --filter=Security
php artisan test --filter=Device
```

#### Step 2.5: حذف فایلهای قدیمی
```bash
del config\authentication.php
del config\moderation.php
```

---

### Phase 3: ادغام Limits Domain

**هدف**: ادغام `limits.php` + `monetization.php` + `pagination.php` + `polls.php` + `posts.php` → `limits.php`

#### Step 3.1: ایجاد limits.php جدید

**ساختار**:
```php
return [
    // Rate Limits (از limits.php فعلی)
    'rate_limits' => [...],           // 50 خط
    
    // Trending (از limits.php فعلی)
    'trending' => [...],              // 10 خط
    
    // Roles (از monetization.php)
    'roles' => [...],                 // 80 خط
    
    // Creator Fund (از monetization.php)
    'creator_fund' => [...],          // 6 خط
    
    // Advertisements (از monetization.php)
    'advertisements' => [...],        // 3 خط
    
    // Pagination (از pagination.php)
    'pagination' => [...],            // 20 خط
    
    // Polls (از polls.php)
    'polls' => [...],                 // 7 خط
    
    // Posts (از posts.php)
    'posts' => [...],                 // 15 خط
];
```

**خطوط**: 200 خط

#### Step 3.2: Update References

**Monetization** (8 مورد):
```php
// BEFORE: config('monetization.roles')
// AFTER:  config('limits.roles')
```

**Pagination** (34 مورد):
```php
// BEFORE: config('pagination.posts')
// AFTER:  config('limits.pagination.posts')
```

**Polls** (4 مورد):
```php
// BEFORE: config('polls.max_options')
// AFTER:  config('limits.polls.max_options')
```

**Posts** (2 مورد):
```php
// BEFORE: config('posts.edit_timeout_minutes')
// AFTER:  config('limits.posts.edit_timeout_minutes')
```

#### Step 3.3: Testing
```bash
php artisan test --filter=Subscription
php artisan test --filter=Poll
php artisan test --filter=Post
```

#### Step 3.4: حذف فایلهای قدیمی
```bash
del config\monetization.php
del config\pagination.php
del config\polls.php
del config\posts.php
```

---

### Phase 4: ادغام Content Domain

**هدف**: ادغام `validation.php` + `media.php` → `content.php`

#### Step 4.1: ایجاد content.php جدید

**ساختار**:
```php
return [
    // Validation (از validation.php)
    'validation' => [
        'user' => [...],              // 15 خط
        'content' => [...],           // 20 خط
        'password' => [...],          // 3 خط
        'date' => [...],              // 2 خط
        'search' => [...],            // 5 خط
        'trending' => [...],          // 3 خط
        'min' => [...],               // 10 خط
        'max' => [...],               // 25 خط
    ],
    
    // Media (از media.php)
    'media' => [
        'max_file_size' => [...],     // 5 خط
        'allowed_mime_types' => [...],// 8 خط
        'image_variants' => [...],    // 5 خط
        'video_qualities' => [...],   // 8 خط
        'video_dimensions' => [...],  // 5 خط
        'quality' => [...],           // 3 خط
    ],
];
```

**خطوط**: 120 خط

#### Step 4.2: Update References

**Validation** (105 مورد):
```php
// BEFORE: config('validation.user.name.max_length')
// AFTER:  config('content.validation.user.name.max_length')
```

**Media** (7 مورد):
```php
// BEFORE: config('media.max_file_size.video')
// AFTER:  config('content.media.max_file_size.video')
```

#### Step 4.3: Testing
```bash
php artisan test --filter=Validation
php artisan test --filter=Media
php artisan test --filter=Upload
```

#### Step 4.4: حذف فایلهای قدیمی
```bash
del config\validation.php
del config\media.php
```

---

### Phase 5: تغییر نام Cache

**هدف**: تغییر نام `cache_ttl.php` → `performance.php`

#### Step 5.1: تغییر نام فایل
```bash
ren config\cache_ttl.php performance.php
```

#### Step 5.2: Update References (23 مورد)
```php
// BEFORE: config('cache_ttl.ttl.timeline')
// AFTER:  config('performance.cache.timeline')
```

#### Step 5.3: بهبود ساختار
```php
return [
    'cache' => [
        'timeline' => 300,
        'trending' => 3600,
        'user' => 600,
        'post' => 1800,
        // ... 18 مورد
    ],
];
```

#### Step 5.4: Testing
```bash
php artisan test --filter=Cache
```

---

### Phase 6: Finalization

#### Step 6.1: Update CacheOptimizationService

**قبل**:
```php
'user' => config('authentication.cache.user_ttl', 600),
'post' => config('authentication.cache.post_ttl', 1800),
'timeline' => config('cache_ttl.ttl.timeline'),
'trending' => config('cache_ttl.ttl.trending')
```

**بعد**:
```php
'user' => config('performance.cache.user'),
'post' => config('performance.cache.post'),
'timeline' => config('performance.cache.timeline'),
'trending' => config('performance.cache.trending')
```

#### Step 6.2: Run All Tests
```bash
php artisan test
php test-scripts/run-all.php
```

#### Step 6.3: Update Documentation
- ✅ README.md
- ✅ ARCHITECTURE.md
- ✅ API.md

#### Step 6.4: Commit
```bash
git add .
git commit -m "refactor: consolidate config files (12→5, -22% LOC, 0 duplications)"
git push origin config-consolidation
```

---

## 📊 نتیجه نهایی

### قبل از ادغام
```
config/
├── authentication.php  (182 خط) ⚠️
├── security.php        (205 خط)
├── moderation.php      (41 خط)
├── limits.php          (73 خط)
├── monetization.php    (103 خط)
├── pagination.php      (23 خط)
├── cache_ttl.php       (24 خط)
├── validation.php      (89 خط)
├── media.php           (50 خط)
├── polls.php           (10 خط)
├── posts.php           (21 خط)
└── status.php          (51 خط)

جمع: 12 فایل، 872 خط
```

### بعد از ادغام
```
config/
├── security.php        (280 خط) ✅
├── limits.php          (200 خط) ✅
├── content.php         (120 خط) ✅
├── performance.php     (30 خط)  ✅
└── status.php          (51 خط)  ✅

جمع: 5 فایل، 681 خط
```

### بهبودها
- ✅ **تعداد فایل**: 12 → 5 (کاهش 58%)
- ✅ **خطوط کد**: 872 → 681 (کاهش 22%)
- ✅ **تکرار**: 3 → 0 (حذف 100%)
- ✅ **Dead Code**: 58 → 0 (حذف 100%)
- ✅ **نگهداری**: سخت → آسان
- ✅ **خوانایی**: متوسط → عالی

---

## ⚠️ نکات مهم

### 1. Backward Compatibility
- تمام تغییرات breaking هستند
- نیاز به update همه references
- باید در یک PR انجام شود

### 2. Testing Strategy
- هر Phase باید جداگانه test شود
- تست کامل قبل از merge
- تست integration بعد از هر Phase

### 3. Rollback Plan
```bash
# در صورت مشکل
git checkout main
git branch -D config-consolidation
```

### 4. Team Communication
- اطلاعرسانی قبل از شروع
- Code review دقیق
- Documentation update

---

## 📅 زمانبندی پیشنهادی

| Phase | مدت زمان | وابستگی |
|-------|----------|----------|
| Phase 1 | 30 دقیقه | - |
| Phase 2 | 2 ساعت | Phase 1 |
| Phase 3 | 1.5 ساعت | Phase 2 |
| Phase 4 | 1.5 ساعت | Phase 3 |
| Phase 5 | 30 دقیقه | Phase 4 |
| Phase 6 | 1 ساعت | Phase 5 |
| **جمع** | **7 ساعت** | - |

---

## ✅ Checklist

### Pre-Migration
- [ ] ایجاد branch جدید
- [ ] Backup فایلهای config
- [ ] اطلاعرسانی به تیم
- [ ] آماده کردن test environment

### Phase 2: Security
- [ ] ایجاد security.php جدید
- [ ] Update 48 reference
- [ ] Run tests
- [ ] حذف authentication.php و moderation.php

### Phase 3: Limits
- [ ] ایجاد limits.php جدید
- [ ] Update 48 reference
- [ ] Run tests
- [ ] حذف 4 فایل قدیمی

### Phase 4: Content
- [ ] ایجاد content.php جدید
- [ ] Update 112 reference
- [ ] Run tests
- [ ] حذف validation.php و media.php

### Phase 5: Performance
- [ ] تغییر نام cache_ttl.php
- [ ] Update 23 reference
- [ ] Run tests

### Phase 6: Finalization
- [ ] Update CacheOptimizationService
- [ ] Run all tests
- [ ] Update documentation
- [ ] Code review
- [ ] Merge to main

---

**تاریخ ایجاد**: 2024
**وضعیت**: آماده اجرا
**اولویت**: Medium
**تخمین زمان**: 7 ساعت

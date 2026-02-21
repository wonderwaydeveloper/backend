# Role-Based Limits System

## 📋 نمای کلی

سیستم محدودیتهای مبتنی بر نقش برای مدیریت دسترسی و محدودیتهای کاربران بر اساس نقش آنها.

## 🎯 نقشها (6 نقش)

| نقش | توضیح | اولویت |
|-----|-------|--------|
| **user** | کاربر عادی | 1 (پایینترین) |
| **verified** | کاربر تایید شده | 2 |
| **premium** | کاربر پرمیوم | 3 |
| **organization** | سازمان | 4 |
| **moderator** | مدیر | 5 |
| **admin** | ادمین | 6 (بالاترین) |

## 📊 جدول محدودیتها

| محدودیت | user | verified | premium | organization | moderator | admin |
|---------|------|----------|---------|--------------|-----------|-------|
| **media_per_post** | 4 | 4 | 10 | 10 | 10 | 20 |
| **max_file_size_kb** | 5120 | 10240 | 51200 | 102400 | 51200 | 204800 |
| **posts_per_day** | 100 | 200 | 500 | 1000 | 500 | 2000 |
| **video_length_seconds** | 140 | 140 | 600 | 600 | 600 | 1200 |
| **scheduled_posts** | 0 | 0 | 100 | 500 | 100 | 1000 |
| **rate_limit_per_minute** | 60 | 100 | 200 | 300 | 200 | 500 |
| **hd_upload** | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| **advertisements** | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |

## 🔧 استفاده

### در کنترلرها

```php
use App\Services\SubscriptionLimitService;

$limitService = app(SubscriptionLimitService::class);

// بررسی محدودیتها
$maxMedia = $limitService->getMaxMediaPerPost($user);
$maxFileSize = $limitService->getMaxFileSize($user);
$postsPerDay = $limitService->getPostsPerDayLimit($user);
$canUploadHD = $limitService->canUploadHD($user);
```

## 📁 ساختار فایلها

```
config/
├── monetization.php          # تنها منبع محدودیتهای نقشها
└── limits.php                # rate_limits و trending (بدون roles)

app/Services/
└── SubscriptionLimitService.php  # سرویس مدیریت محدودیتها

tests/Unit/
└── SubscriptionLimitServiceTest.php  # تستهای جامع (9 تست)
```

## ✅ تستها

```bash
php artisan test tests/Unit/SubscriptionLimitServiceTest.php
# نتیجه: 9 passed (43 assertions)
```

## 🔄 اولویت نقشها

اگر کاربر چند نقش داشته باشد، بالاترین نقش اعمال میشود:

**ترتیب اولویت:** admin > moderator > organization > premium > verified > user

## 📝 نکات مهم

1. ✅ **یک منبع واحد:** فقط `config/monetization.php`
2. ✅ **همه 6 نقش:** پوشش کامل
3. ✅ **تست شده:** 9 تست Unit + تستهای Feature
4. ✅ **مستندسازی شده:** کامنتهای فارسی در config

---

**آخرین بروزرسانی:** 2026-02-10  
**نسخه:** 2.0

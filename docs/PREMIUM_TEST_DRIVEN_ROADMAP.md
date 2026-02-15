# 🧪 نقشه راه یکپارچهسازی Premium با رویکرد Test-Driven

**تاریخ:** 2026-02-15  
**رویکرد:** Test-First Integration

---

## 📋 اسکریپتهای تست موجود

```
✅ test_authentication.php
✅ test_posts_system.php
✅ test_comments.php
✅ test_users_profile_01_core.php
✅ test_users_profile_02_security.php
✅ test_users_profile_03_standards.php
✅ test_follow_system.php
✅ test_search_discovery_system.php
✅ test_messaging_system.php
✅ test_notifications_system.php
✅ test_bookmarks_reposts_system.php
✅ test_hashtags_system.php
✅ test_moderation_reporting_system.php
✅ test_communities_system.php
✅ test_spaces_system.php
✅ test_lists_system.php
✅ test_polls.php
✅ test_mentions.php
✅ test_media.php
✅ test_moments.php
✅ test_realtime_system.php
✅ test_analytics_system.php
✅ test_abtest_system.php
✅ test_monetization_system.php
✅ test_performance_monitoring_system.php
✅ test_device_management.php
✅ test_integration_systems.php
✅ test_report.php
```

**مجموع:** 27 اسکریپت تست

---

## 🎯 استراتژی Test-Driven Integration

### قانون طلایی:
> **هر تغییر → اجرای تست → تأیید موفقیت → Commit**

### فرآیند:
1. **قبل از تغییر:** اجرای تستهای مرتبط → همه باید PASS باشند
2. **بعد از تغییر:** اجرای مجدد تستها → همه باید PASS باشند
3. **اگر FAIL شد:** Rollback و اصلاح

---

## 📊 نقشه تأثیرگذاری Premium بر سیستمها

### 🔴 تأثیر مستقیم (باید تغییر کنند)

| سیستم | اسکریپت تست | تغییرات مورد نیاز |
|-------|-------------|-------------------|
| **Monetization** | `test_monetization_system.php` | ✅ PremiumService اصلاح |
| **Posts** | `test_posts_system.php` | ✅ محدودیت 10/روز، Edit |
| **Media** | `test_media.php` | ✅ محدودیت 5 تصویر، 2 دقیقه ویدیو |
| **Analytics** | `test_analytics_system.php` | ✅ محدودیت آمار پیشرفته |
| **Users/Profile** | `test_users_profile_*.php` | ✅ نمایش Premium badge |

### 🟡 تأثیر غیرمستقیم (باید تست شوند)

| سیستم | اسکریپت تست | چک کردن |
|-------|-------------|---------|
| **Authentication** | `test_authentication.php` | ✅ is_premium بعد از login |
| **Comments** | `test_comments.php` | ✅ محدودیت نداشته باشد |
| **Notifications** | `test_notifications_system.php` | ✅ اعلان subscribe/cancel |
| **Integration** | `test_integration_systems.php` | ✅ یکپارچگی کلی |

### 🟢 بدون تأثیر (فقط Smoke Test)

| سیستم | اسکریپت تست |
|-------|-------------|
| Messaging | `test_messaging_system.php` |
| Bookmarks | `test_bookmarks_reposts_system.php` |
| Hashtags | `test_hashtags_system.php` |
| Communities | `test_communities_system.php` |
| Spaces | `test_spaces_system.php` |
| Lists | `test_lists_system.php` |
| Polls | `test_polls.php` |
| Mentions | `test_mentions.php` |
| Moments | `test_moments.php` |
| Realtime | `test_realtime_system.php` |
| A/B Test | `test_abtest_system.php` |
| Device | `test_device_management.php` |

---

## 🚀 فازهای اجرایی با Test-Driven

### 📍 فاز 0: Baseline Testing (30 دقیقه)

**هدف:** اطمینان از سلامت سیستم قبل از شروع

#### گام 1: اجرای تمام تستها
```bash
# اجرای تستهای حیاتی
php test_monetization_system.php
php test_posts_system.php
php test_media.php
php test_analytics_system.php
php test_users_profile_01_core.php
php test_authentication.php
php test_integration_systems.php
```

#### معیار موفقیت:
- ✅ تمام تستها PASS
- ✅ هیچ ERROR وجود ندارد
- ✅ نتایج ذخیره شد (برای مقایسه)

#### اگر FAIL:
- ❌ **توقف کامل**
- ❌ اصلاح مشکلات موجود
- ❌ تکرار Baseline Testing

---

### 📍 فاز 1: اصلاح PremiumService (45 دقیقه)

#### قبل از شروع:
```bash
✅ php test_monetization_system.php → PASS
```

#### تغییرات:
1. افزودن `User::hasFeature()`
2. اصلاح `PremiumService::subscribe()` → بهروز کردن `is_premium`
3. اصلاح `PremiumService::cancel()` → بهروز کردن `is_premium`

#### بعد از تغییر:
```bash
✅ php test_monetization_system.php → باید PASS باشد
✅ php test_users_profile_01_core.php → باید PASS باشد
✅ php test_authentication.php → باید PASS باشد
```

#### معیار موفقیت:
- ✅ `test_monetization_system.php`: 35/35 PASS
- ✅ `test_users_profile_01_core.php`: 157/157 PASS
- ✅ `test_authentication.php`: 169/169 PASS

#### اگر FAIL:
- ❌ Rollback تغییرات
- ❌ اصلاح و تکرار

---

### 📍 فاز 2: محدودیت Posts (1 ساعت)

#### قبل از شروع:
```bash
✅ php test_posts_system.php → PASS (203/203)
```

#### تغییرات:
1. `PostPolicy::create()` → محدودیت 10 پست/روز
2. `PostPolicy::update()` → فقط Premium

#### بعد از تغییر:
```bash
✅ php test_posts_system.php → باید PASS باشد
✅ php test_comments.php → باید PASS باشد (بدون تأثیر)
✅ php test_integration_systems.php → باید PASS باشد
```

#### معیار موفقیت:
- ✅ `test_posts_system.php`: 203/203 PASS
- ✅ `test_comments.php`: 150/150 PASS
- ✅ `test_integration_systems.php`: 87/87 PASS

#### اگر FAIL:
- ❌ Rollback
- ❌ اصلاح
- ❌ تکرار

---

### 📍 فاز 3: محدودیت Media (1 ساعت)

#### قبل از شروع:
```bash
✅ php test_media.php → PASS (74/74)
```

#### تغییرات:
1. `MediaController::uploadImage()` → محدودیت 5 تصویر
2. `VideoController::upload()` → محدودیت 2 دقیقه

#### بعد از تغییر:
```bash
✅ php test_media.php → باید PASS باشد
✅ php test_posts_system.php → باید PASS باشد
✅ php test_integration_systems.php → باید PASS باشد
```

#### معیار موفقیت:
- ✅ `test_media.php`: 74/74 PASS
- ✅ `test_posts_system.php`: 203/203 PASS
- ✅ `test_integration_systems.php`: 87/87 PASS

---

### 📍 فاز 4: محدودیت Analytics (45 دقیقه)

#### قبل از شروع:
```bash
✅ php test_analytics_system.php → PASS (75/75)
```

#### تغییرات:
1. `AnalyticsController::userAnalytics()` → چک Premium+

#### بعد از تغییر:
```bash
✅ php test_analytics_system.php → باید PASS باشد
✅ php test_integration_systems.php → باید PASS باشد
```

#### معیار موفقیت:
- ✅ `test_analytics_system.php`: 75/75 PASS
- ✅ `test_integration_systems.php`: 87/87 PASS

---

### 📍 فاز 5: Premium Badge (30 دقیقه)

#### قبل از شروع:
```bash
✅ php test_users_profile_01_core.php → PASS (157/157)
```

#### تغییرات:
1. `UserResource` → افزودن `premium_badge`
2. `ProfileController::show()` → نمایش badge

#### بعد از تغییر:
```bash
✅ php test_users_profile_01_core.php → باید PASS باشد
✅ php test_users_profile_03_standards.php → باید PASS باشد
✅ php test_authentication.php → باید PASS باشد
```

#### معیار موفقیت:
- ✅ همه تستها PASS

---

### 📍 فاز 6: CheckPremium Middleware (45 دقیقه)

#### قبل از شروع:
```bash
✅ php test_authentication.php → PASS (169/169)
```

#### تغییرات:
1. ایجاد `CheckPremium` Middleware
2. ثبت در Kernel
3. اضافه کردن به routes

#### بعد از تغییر:
```bash
✅ php test_authentication.php → باید PASS باشد
✅ php test_posts_system.php → باید PASS باشد
✅ php test_media.php → باید PASS باشد
✅ php test_analytics_system.php → باید PASS باشد
✅ php test_integration_systems.php → باید PASS باشد
```

#### معیار موفقیت:
- ✅ همه تستها PASS

---

### 📍 فاز 7: Final Integration Test (30 دقیقه)

#### اجرای تمام تستها:
```bash
# تستهای حیاتی
php test_monetization_system.php
php test_posts_system.php
php test_media.php
php test_analytics_system.php
php test_users_profile_01_core.php
php test_authentication.php

# تستهای یکپارچگی
php test_integration_systems.php

# Smoke tests (نمونه)
php test_comments.php
php test_notifications_system.php
php test_messaging_system.php
```

#### معیار موفقیت:
- ✅ **تمام تستها PASS**
- ✅ هیچ regression وجود ندارد
- ✅ عملکرد سیستم تغییر نکرده

---

## 📊 ماتریس تست برای هر فاز

| فاز | تست اصلی | تستهای وابسته | Smoke Tests |
|-----|---------|---------------|-------------|
| **0: Baseline** | همه | - | - |
| **1: Service** | monetization | users, auth | - |
| **2: Posts** | posts | comments, integration | - |
| **3: Media** | media | posts, integration | - |
| **4: Analytics** | analytics | integration | - |
| **5: Badge** | users | auth, standards | - |
| **6: Middleware** | auth | posts, media, analytics, integration | - |
| **7: Final** | همه حیاتی | integration | 3-5 سیستم |

---

## 🔄 فرآیند Rollback

### اگر تست FAIL شد:

#### گام 1: شناسایی
```bash
# کدام تست fail شد؟
# چند تست fail شد؟
# Error message چیست؟
```

#### گام 2: Rollback
```bash
git checkout -- [فایلهای تغییر یافته]
# یا
git reset --hard HEAD
```

#### گام 3: تأیید
```bash
# اجرای مجدد تست
php test_[system].php
# باید PASS شود
```

#### گام 4: اصلاح
```bash
# اصلاح کد
# تست محلی
# Commit
```

---

## 📝 چکلیست اجرایی

### قبل از هر فاز:
- [ ] Backup از database
- [ ] Commit تغییرات قبلی
- [ ] اجرای تستهای مرتبط → PASS
- [ ] خواندن معیارهای فاز

### حین فاز:
- [ ] تغییرات کوچک و تدریجی
- [ ] تست محلی بعد از هر تغییر
- [ ] Commit بعد از هر بخش موفق

### بعد از فاز:
- [ ] اجرای تست اصلی → PASS
- [ ] اجرای تستهای وابسته → PASS
- [ ] اجرای Smoke tests → PASS
- [ ] Commit نهایی با پیام واضح

---

## 🎯 معیار موفقیت کلی

### ✅ یکپارچگی موفق است اگر:

1. **تمام تستهای قبلی PASS باشند**
   ```
   test_monetization_system.php: 35/35 ✓
   test_posts_system.php: 203/203 ✓
   test_media.php: 74/74 ✓
   test_analytics_system.php: 75/75 ✓
   test_users_profile_01_core.php: 157/157 ✓
   test_authentication.php: 169/169 ✓
   test_integration_systems.php: 87/87 ✓
   ```

2. **هیچ regression وجود نداشته باشد**
   - تمام سیستمهای دیگر کار میکنند
   - عملکرد تغییر نکرده
   - API responses صحیح هستند

3. **ویژگیهای جدید کار کنند**
   - محدودیتها اعمال میشوند
   - Premium features فعال هستند
   - Badge نمایش داده میشود

---

## 🚀 دستور اجرا

### شروع از فاز 0:
```bash
# Baseline Testing
php test_monetization_system.php
php test_posts_system.php
php test_media.php
php test_analytics_system.php
php test_users_profile_01_core.php
php test_authentication.php
php test_integration_systems.php
```

**آیا همه PASS شدند؟**
- ✅ بله → شروع فاز 1
- ❌ خیر → اصلاح مشکلات

---

**آیا آماده شروع Baseline Testing هستید؟**

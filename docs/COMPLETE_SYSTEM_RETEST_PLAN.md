# 🧪 نقشه تست کامل سیستمها (به ترتیب ROADMAP)

**تاریخ:** 2026-02-15  
**هدف:** تست مجدد تمام 28 سیستم به ترتیب وابستگی  
**دلیل:** سیستمهای پایه در حین توسعه اصلاح شدند

---

## 🎯 چرا این نقشه؟

### مشکل:
1. ✅ سیستمهای پایه (User, Auth, Post) در ابتدا ساخته شدند
2. ✅ سیستمهای وابسته (Media, Analytics, Premium) بعداً اضافه شدند
3. ⚠️ **سیستمهای پایه در حین کار اصلاح شدند**
4. ⚠️ **سیستمهای وابسته دوباره تست نشدند**

### راهحل:
- تست مجدد **همه سیستمها** به ترتیب ROADMAP
- اطمینان از **یکپارچگی کامل**
- شناسایی **شکستگیهای احتمالی**

---

## 📊 آمار فعلی

- **کل سیستمها:** 28/31 (90.3%)
- **کل تستها:** 2,769
- **تست اسکریپتها:** 27 فایل
- **وضعیت:** نیاز به تست مجدد

---

## 🔴 فاز 1: سیستمهای پایه (حیاتی)

> این سیستمها **پایه** هستند و همه سیستمها به آنها وابسته‌اند

### 1️⃣ Authentication & Security
**اولویت:** 🔴 حیاتی  
**وابستگی:** هیچ  
**تست اسکریپت:** `test_authentication.php`

```bash
php test_authentication.php
```

**انتظار:**
- ✅ 169 تست موفق
- ✅ Login/Logout کار میکند
- ✅ Registration کار میکند
- ✅ 2FA کار میکند
- ✅ Device Management کار میکند

**در صورت خطا:**
- 🔴 **STOP** - این سیستم پایه است
- 🔧 رفع خطا قبل از ادامه
- 🔄 تست مجدد

---

### 2️⃣ Users & Profile
**اولویت:** 🔴 حیاتی  
**وابستگی:** Authentication  
**تست اسکریپت:** `test_users_profile_*.php` (6 فایل)

```bash
php test_users_profile_basic.php
php test_users_profile_advanced.php
php test_users_profile_settings.php
php test_users_profile_privacy.php
php test_users_profile_verification.php
php test_users_profile_integration.php
```

**انتظار:**
- ✅ 157 تست موفق
- ✅ Profile CRUD کار میکند
- ✅ Settings کار میکند
- ✅ Privacy کار میکند

**در صورت خطا:**
- 🔴 **STOP** - این سیستم پایه است
- 🔧 رفع خطا
- 🔄 تست مجدد

---

### 3️⃣ Posts System
**اولویت:** 🔴 حیاتی  
**وابستگی:** Users, Authentication  
**تست اسکریپت:** `test_posts_system.php`

```bash
php test_posts_system.php
```

**انتظار:**
- ✅ 203 تست موفق
- ✅ Post CRUD کار میکند
- ✅ Like/Unlike کار میکند
- ✅ Repost کار میکند
- ✅ Analytics fields موجود است

**در صورت خطا:**
- 🔴 **STOP** - این سیستم پایه است
- 🔧 رفع خطا
- 🔄 تست مجدد

---

### 4️⃣ Block/Mute System
**اولویت:** 🔴 حیاتی  
**وابستگی:** Users  
**تست اسکریپت:** `test_block_mute.php`

```bash
php test_block_mute.php
```

**انتظار:**
- ✅ Block/Unblock کار میکند
- ✅ Mute/Unmute کار میکند
- ✅ Integration با سایر سیستمها

**در صورت خطا:**
- 🔴 **STOP**
- 🔧 رفع خطا
- 🔄 تست مجدد

---

### 5️⃣ Follow System
**اولویت:** 🔴 حیاتی  
**وابستگی:** Users, Block/Mute  
**تست اسکریپت:** `test_follow_system.php`

```bash
php test_follow_system.php
```

**انتظار:**
- ✅ 141 تست موفق
- ✅ Follow/Unfollow کار میکند
- ✅ Follow Requests کار میکند
- ✅ Block/Mute integration

**در صورت خطا:**
- 🔴 **STOP**
- 🔧 رفع خطا
- 🔄 تست مجدد

---

## 🟡 فاز 2: سیستمهای وابسته سطح 1

> این سیستمها به سیستمهای پایه وابسته‌اند

### 6️⃣ Comments System
**اولویت:** 🟡 مهم  
**وابستگی:** Posts, Users, Block/Mute  
**تست اسکریپت:** `test_comments_system.php`

```bash
php test_comments_system.php
```

**انتظار:**
- ✅ 150 تست موفق
- ✅ Comment CRUD کار میکند
- ✅ Comment Likes کار میکند
- ✅ Block/Mute integration

**در صورت خطا:**
- 🟡 ادامه با احتیاط
- 📝 ثبت خطا
- 🔧 رفع بعد از تست کامل

---

### 7️⃣ Messaging System
**اولویت:** 🟡 مهم  
**وابستگی:** Users, Block/Mute  
**تست اسکریپت:** `test_messaging_system.php`

```bash
php test_messaging_system.php
```

**انتظار:**
- ✅ 125 تست موفق
- ✅ Send/Receive کار میکند
- ✅ Conversations کار میکند
- ✅ Block/Mute integration

---

### 8️⃣ Notifications System
**اولویت:** 🟡 مهم  
**وابستگی:** Users, Posts, Comments, Follow  
**تست اسکریپت:** `test_notifications_system.php`

```bash
php test_notifications_system.php
```

**انتظار:**
- ✅ 161 تست موفق
- ✅ Notification creation کار میکند
- ✅ Preferences کار میکند
- ✅ Push notifications کار میکند

---

### 9️⃣ Bookmarks & Reposts
**اولویت:** 🟡 مهم  
**وابستگی:** Posts, Users  
**تست اسکریپت:** `test_bookmarks_reposts.php`

```bash
php test_bookmarks_reposts.php
```

**انتظار:**
- ✅ 135 تست موفق
- ✅ Bookmark/Unbookmark کار میکند
- ✅ Repost کار میکند
- ✅ Quote Tweet کار میکند

---

### 🔟 Hashtags System
**اولویت:** 🟡 مهم  
**وابستگی:** Posts  
**تست اسکریپت:** `test_hashtags_system.php`

```bash
php test_hashtags_system.php
```

**انتظار:**
- ✅ 76 تست موفق
- ✅ Hashtag extraction کار میکند
- ✅ Trending کار میکند
- ✅ Search کار میکند

---

### 1️⃣1️⃣ Search & Discovery
**اولویت:** 🟡 مهم  
**وابستگی:** Users, Posts, Hashtags  
**تست اسکریپت:** `test_search_discovery.php`

```bash
php test_search_discovery.php
```

**انتظار:**
- ✅ 175 تست موفق
- ✅ Multi-type search کار میکند
- ✅ Suggestions کار میکند
- ✅ Trending کار میکند

---

### 1️⃣2️⃣ Moderation & Reporting
**اولویت:** 🟡 مهم  
**وابستگی:** Users, Posts, Comments  
**تست اسکریپت:** `test_moderation_reporting.php`

```bash
php test_moderation_reporting.php
```

**انتظار:**
- ✅ 89 تست موفق
- ✅ Report creation کار میکند
- ✅ Moderation actions کار میکند
- ✅ Auto-moderation کار میکند

---

## 🟢 فاز 3: سیستمهای وابسته سطح 2

> این سیستمها به سیستمهای سطح 1 وابسته‌اند

### 1️⃣3️⃣ Media Management
**اولویت:** 🟢 متوسط  
**وابستگی:** Posts, Comments, Messages  
**تست اسکریپت:** `test_media.php`

```bash
php test_media.php
```

**انتظار:**
- ✅ 74 تست موفق (99.4%)
- ✅ Image upload کار میکند
- ✅ Video upload کار میکند
- ✅ Thumbnail generation کار میکند

---

### 1️⃣4️⃣ Polls System
**اولویت:** 🟢 متوسط  
**وابستگی:** Posts  
**تست اسکریپت:** `test_polls_system.php`

```bash
php test_polls_system.php
```

**انتظار:**
- ✅ 84 تست موفق
- ✅ Poll creation کار میکند
- ✅ Voting کار میکند
- ✅ Results کار میکند

---

### 1️⃣5️⃣ Mentions System
**اولویت:** 🟢 متوسط  
**وابستگی:** Posts, Comments, Users  
**تست اسکریپت:** `test_mentions_system.php`

```bash
php test_mentions_system.php
```

**انتظار:**
- ✅ 57 تست موفق
- ✅ @username extraction کار میکند
- ✅ Mention notifications کار میکند
- ✅ Block/Mute integration

---

### 1️⃣6️⃣ Lists Management
**اولویت:** 🟢 متوسط  
**وابستگی:** Users, Posts  
**تست اسکریپت:** `test_lists_system.php`

```bash
php test_lists_system.php
```

**انتظار:**
- ✅ 125 تست موفق
- ✅ List CRUD کار میکند
- ✅ Member management کار میکند
- ✅ Subscribe/Unsubscribe کار میکند

---

### 1️⃣7️⃣ Communities System
**اولویت:** 🟢 متوسط  
**وابستگی:** Users, Posts  
**تست اسکریپت:** `test_communities_system.php`

```bash
php test_communities_system.php
```

**انتظار:**
- ✅ 72 تست موفق
- ✅ Community CRUD کار میکند
- ✅ Member management کار میکند
- ✅ Community Notes کار میکند

---

### 1️⃣8️⃣ Spaces (Audio Rooms)
**اولویت:** 🟢 متوسط  
**وابستگی:** Users  
**تست اسکریپت:** `test_spaces_system.php`

```bash
php test_spaces_system.php
```

**انتظار:**
- ✅ 155 تست موفق
- ✅ Space creation کار میکند
- ✅ Participant management کار میکند
- ✅ Broadcasting کار میکند

---

### 1️⃣9️⃣ Moments System
**اولویت:** 🟢 متوسط  
**وابستگی:** Posts, Users  
**تست اسکریپت:** `test_moments_system.php`

```bash
php test_moments_system.php
```

**انتظار:**
- ✅ 74 تست موفق
- ✅ Moment creation کار میکند
- ✅ Post curation کار میکند
- ✅ Privacy control کار میکند

---

### 2️⃣0️⃣ Real-time Features
**اولویت:** 🟢 متوسط  
**وابستگی:** Users, Posts, Follow  
**تست اسکریپت:** `test_realtime_features.php`

```bash
php test_realtime_features.php
```

**انتظار:**
- ✅ 64 تست موفق
- ✅ Online status کار میکند
- ✅ Live timeline کار میکند
- ✅ Broadcasting کار میکند

---

## 🔵 فاز 4: سیستمهای تحلیلی و پیشرفته

> این سیستمها مستقل هستند اما به دیتای سایر سیستمها نیاز دارند

### 2️⃣1️⃣ Analytics System
**اولویت:** 🔵 پیشرفته  
**وابستگی:** Posts, Users, Profile  
**تست اسکریپت:** `test_analytics_system.php`

```bash
php test_analytics_system.php
```

**انتظار:**
- ✅ 75 تست موفق
- ✅ User analytics کار میکند
- ✅ Post analytics کار میکند
- ✅ Conversion tracking کار میکند

---

### 2️⃣2️⃣ A/B Testing System
**اولویت:** 🔵 پیشرفته  
**وابستگی:** Users  
**تست اسکریپت:** `test_ab_testing.php`

```bash
php test_ab_testing.php
```

**انتظار:**
- ✅ 60 تست موفق
- ✅ Test creation کار میکند
- ✅ User assignment کار میکند
- ✅ Event tracking کار میکند
- ✅ Statistical analysis کار میکند

---

### 2️⃣3️⃣ Monetization System
**اولویت:** 🔵 پیشرفته  
**وابستگی:** Users, Posts  
**تست اسکریپت:** `test_monetization_system.php`

```bash
php test_monetization_system.php
```

**انتظار:**
- ✅ 35 تست موفق
- ✅ Advertisement system کار میکند
- ✅ Creator Fund کار میکند
- ✅ Premium Subscriptions کار میکند

**⚠️ توجه:**
- این سیستم نیاز به یکپارچگی دارد (PREMIUM_INTEGRATION_ROADMAP.md)

---

### 2️⃣4️⃣ Performance & Monitoring
**اولویت:** 🔵 پیشرفته  
**وابستگی:** همه سیستمها  
**تست اسکریپت:** `test_performance_monitoring.php`

```bash
php test_performance_monitoring.php
```

**انتظار:**
- ✅ 100 تست موفق
- ✅ Performance dashboard کار میکند
- ✅ System monitoring کار میکند
- ✅ Auto-scaling کار میکند

---

### 2️⃣5️⃣ Device Management
**اولویت:** 🔵 پیشرفته  
**وابستگی:** Authentication, Users  
**تست اسکریپت:** `test_device_management.php`

```bash
php test_device_management.php
```

**انتظار:**
- ✅ 114 تست موفق
- ✅ Device registration کار میکند
- ✅ Trust management کار میکند
- ✅ Security monitoring کار میکند

---

## 🧪 فاز 5: تست یکپارچگی کامل

> تست تمام سیستمها با هم

### Integration Tests
**تست اسکریپت:** `test_integration_systems.php`

```bash
php test_integration_systems.php
```

**انتظار:**
- ✅ 87 تست موفق
- ✅ تمام سیستمها با هم کار میکنند
- ✅ هیچ conflict وجود ندارد

---

## 📊 ماتریس تست

| # | سیستم | اسکریپت | تستها | وابستگی | اولویت |
|---|-------|---------|-------|---------|--------|
| 1 | Authentication | test_authentication.php | 169 | - | 🔴 |
| 2 | Users & Profile | test_users_profile_*.php | 157 | Auth | 🔴 |
| 3 | Posts | test_posts_system.php | 203 | Users | 🔴 |
| 4 | Block/Mute | test_block_mute.php | - | Users | 🔴 |
| 5 | Follow | test_follow_system.php | 141 | Users, Block | 🔴 |
| 6 | Comments | test_comments_system.php | 150 | Posts | 🟡 |
| 7 | Messaging | test_messaging_system.php | 125 | Users | 🟡 |
| 8 | Notifications | test_notifications_system.php | 161 | Users, Posts | 🟡 |
| 9 | Bookmarks | test_bookmarks_reposts.php | 135 | Posts | 🟡 |
| 10 | Hashtags | test_hashtags_system.php | 76 | Posts | 🟡 |
| 11 | Search | test_search_discovery.php | 175 | Users, Posts | 🟡 |
| 12 | Moderation | test_moderation_reporting.php | 89 | Users, Posts | 🟡 |
| 13 | Media | test_media.php | 74 | Posts | 🟢 |
| 14 | Polls | test_polls_system.php | 84 | Posts | 🟢 |
| 15 | Mentions | test_mentions_system.php | 57 | Posts, Users | 🟢 |
| 16 | Lists | test_lists_system.php | 125 | Users, Posts | 🟢 |
| 17 | Communities | test_communities_system.php | 72 | Users, Posts | 🟢 |
| 18 | Spaces | test_spaces_system.php | 155 | Users | 🟢 |
| 19 | Moments | test_moments_system.php | 74 | Posts | 🟢 |
| 20 | Real-time | test_realtime_features.php | 64 | Users, Posts | 🟢 |
| 21 | Analytics | test_analytics_system.php | 75 | Posts, Users | 🔵 |
| 22 | A/B Testing | test_ab_testing.php | 60 | Users | 🔵 |
| 23 | Monetization | test_monetization_system.php | 35 | Users, Posts | 🔵 |
| 24 | Performance | test_performance_monitoring.php | 100 | All | 🔵 |
| 25 | Device Mgmt | test_device_management.php | 114 | Auth, Users | 🔵 |
| 26 | Integration | test_integration_systems.php | 87 | All | 🟣 |

**کل:** 2,769 تست

---

## 🎯 استراتژی اجرا

### روش 1: تست سریع (2-3 ساعت)
```bash
# فقط سیستمهای پایه (فاز 1)
php test_authentication.php
php test_users_profile_basic.php
php test_posts_system.php
php test_block_mute.php
php test_follow_system.php
```

### روش 2: تست متوسط (4-6 ساعت)
```bash
# فاز 1 + فاز 2
# (سیستمهای پایه + وابسته سطح 1)
```

### روش 3: تست کامل (8-10 ساعت)
```bash
# تمام فازها (1 تا 5)
# تست تمام 2,769 تست
```

---

## 📋 چکلیست اجرا

### قبل از شروع:
- [ ] Database backup گرفته شد
- [ ] `.env` صحیح است
- [ ] `composer install` اجرا شد
- [ ] `php artisan migrate:fresh --seed` اجرا شد
- [ ] Redis در حال اجرا است
- [ ] Queue worker در حال اجرا است

### حین تست:
- [ ] هر تست را جداگانه اجرا کنید
- [ ] نتایج را ثبت کنید
- [ ] خطاها را یادداشت کنید
- [ ] در صورت خطای حیاتی، STOP کنید

### بعد از تست:
- [ ] تمام نتایج را جمع‌آوری کنید
- [ ] لیست خطاها را تهیه کنید
- [ ] اولویت‌بندی رفع خطاها
- [ ] شروع رفع خطاها

---

## 📊 فرمت گزارش

### برای هر سیستم:

```markdown
## سیستم: [نام]
- **تاریخ:** [تاریخ]
- **تست اسکریپت:** [نام فایل]
- **تستهای کل:** [تعداد]
- **موفق:** [تعداد] ✅
- **ناموفق:** [تعداد] ❌
- **درصد موفقیت:** [درصد]

### خطاها:
1. [شرح خطا]
2. [شرح خطا]

### توضیحات:
[توضیحات اضافی]
```

---

## 🚨 نکات مهم

### 1. سیستمهای پایه
- ⚠️ **هرگز** سیستم پایه را با خطا رها نکنید
- 🔴 در صورت خطا در فاز 1، **STOP** کنید
- 🔧 ابتدا خطا را رفع کنید، سپس ادامه دهید

### 2. وابستگیها
- 📊 همیشه به ترتیب وابستگی تست کنید
- 🔗 اگر سیستم A به B وابسته است، ابتدا B را تست کنید

### 3. یکپارچگی
- 🧪 تست یکپارچگی را در آخر اجرا کنید
- 🔄 اگر تست یکپارچگی fail شد، به سیستمهای مرتبط برگردید

### 4. زمان‌بندی
- ⏰ هر تست ~10-30 دقیقه زمان میبرد
- 📅 برای تست کامل 1-2 روز زمان بگذارید
- ☕ استراحت کنید!

---

## ✅ معیار موفقیت

### تست موفق:
- ✅ تمام 2,769 تست pass شوند
- ✅ هیچ خطای حیاتی وجود نداشته باشد
- ✅ تست یکپارچگی موفق باشد

### تست ناموفق:
- ❌ بیش از 5% تستها fail شوند
- ❌ سیستم پایه خطا داشته باشد
- ❌ تست یکپارچگی fail شود

---

## 🎯 بعد از تست کامل

### اگر همه موفق بودند:
1. ✅ سیستمها یکپارچه هستند
2. ✅ میتوانید به Premium Integration بروید
3. ✅ آماده Production هستید

### اگر خطا وجود داشت:
1. 📝 لیست خطاها را تهیه کنید
2. 🔴 خطاهای حیاتی را ابتدا رفع کنید
3. 🟡 خطاهای متوسط را بعد رفع کنید
4. 🔄 دوباره تست کنید

---

## 📞 آماده شروع؟

**سوال:** آیا میخواهید:
1. ✅ **روش 1:** تست سریع (فقط فاز 1 - سیستمهای پایه)
2. ✅ **روش 2:** تست متوسط (فاز 1 + 2)
3. ✅ **روش 3:** تست کامل (تمام فازها)

**توصیه:** شروع با **روش 1** برای اطمینان از سلامت سیستمهای پایه

---

**تاریخ:** 2026-02-15  
**نسخه:** 1.0  
**وضعیت:** آماده اجرا

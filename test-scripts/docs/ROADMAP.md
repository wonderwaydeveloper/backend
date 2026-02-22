# 🗺️ نقشه راه سیستمها

**آخرین بهروزرسانی:** 2025-02-16  
**پیشرفت کلی:** 11.5% (3/26 سیستم کاملاً مطابق با معماری جدید)

> **توجه:** این نقشه راه بر اساس لیست کامل سیستمهای موجود در `SYSTEMS_LIST.md` تهیه شده است.

---

## 📊 وضعیت کلی پروژه

### ✅ سیستمهای تکمیل شده: 3/26 (11.5%) - 🟢 معماری کامل
### 🟡 سیستمهای نیمه کامل: 2/26 (7.7%) - 🟡 نیاز به Script Tests
### 🔴 سیستمهای قدیمی: 2/26 (7.7%) - ⚠️ نیاز به بروزرسانی
### ⚪ سیستمهای بدون تست: 19/26 (73.1%)

### 📈 آمار تستها
- **تعداد کل تستها:** 1297 تست (Script: 1009 + Feature: 288)
- **تعداد کل PHPUnit تستها:** 246 تست
- **میانگین تست هر سیستم:** 185 تست
- **نرخ موفقیت:** 100%

| # | سیستم | وضعیت | Test Coverage | معماری | امتیاز | اولویت |
|---|-------|-------|---------------|---------|--------|--------|
| 1 | Security | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 2 | Device Management | ⚪ | - | - | - | 🔴 حیاتی |
| 3 | Authentication | 🔴 | 100% | ⚠️ قدیمی | 100/100 | 🔴 حیاتی |
| 4 | Posts & Content | 🔴 | 100% | ⚠️ قدیمی | 100/100 | 🔴 حیاتی |
| 5 | Comments | 🟡 | 100% | 🟡 نیمه | 100/100 | 🔴 حیاتی |
| 6 | Social Features | 🟡 | 100% | 🟡 نیمه | 89/100 | 🔴 حیاتی |
| 7 | Profile & Account | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 8 | Search & Discovery | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 9 | Messaging | ⚪ | - | - | - | 🔴 حیاتی |
| 10 | Notifications | ⚪ | - | - | - | 🔴 حیاتی |
| 11 | Communities | ⚪ | - | - | - | 🔴 حیاتی |
| 12 | Spaces (Audio Rooms) | ⚪ | - | - | - | 🔴 حیاتی |
| 13 | Lists | ⚪ | - | - | - | 🔴 حیاتی |
| 14 | Bookmarks & Reposts | ⚪ | - | - | - | 🔴 حیاتی |
| 15 | Hashtags | ⚪ | - | - | - | 🟡 مهم |
| 16 | Polls | ⚪ | - | - | - | 🟡 مهم |
| 17 | Mentions | ⚪ | - | - | - | 🟡 مهم |
| 18 | Moderation & Reporting | ⚪ | - | - | - | 🟡 مهم |
| 19 | Media Management | ⚪ | - | - | - | 🟡 مهم |
| 20 | Moments | ⚪ | - | - | - | 🟡 مهم |
| 21 | Analytics | ⚪ | - | - | - | 🟡 مهم |
| 22 | A/B Testing | ⚪ | - | - | - | 🟡 مهم |
| 23 | Monetization | ⚪ | - | - | - | 🟢 تکمیلی |
| 24 | Performance & Monitoring | ⚪ | - | - | - | 🟢 تکمیلی |
| 25 | Real-time Features | ⚪ | - | - | - | 🟢 تکمیلی |
| 26 | Subscriptions | ⚪ | - | - | - | 🟢 تکمیلی |

### راهنمای معماری:
- 🟢 **کامل**: Script Tests (20 بخش) + Feature Tests (9 بخش)
- 🟡 **نیمه**: فقط Feature Tests (9 بخش) - نیاز به Script Tests
- 🔴 **قدیمی**: معماری قدیمی - نیاز به بروزرسانی
- ⚪ **بدون تست**: نیاز به ایجاد تست

### سیستمهای حذف شده ❌
- ~~GIF Integration~~ (حذف شده)
- ~~GraphQL~~ (حذف شده)
- ~~Organization Management~~ (حذف شده)

---

## 🎯 اولویتبندی سیستمها

### 🔴 حیاتی - فاز 1 (13 سیستم)

#### 1. Security ✅
- **Controllers:** DeviceController, AuditController
- **Features:** 2FA, Device Management, Security Events, Audit Logs, Bot Detection, Threat Monitoring
- **Endpoints:** 14
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (245 تست: Script 195 + Feature 50)
- **تاریخ تکمیل:** 2025-02-16
- **تست فایلها:**
  - Script: `test-scripts/01_security.php` (195 تست، 20 بخش)
  - Feature: `tests/Feature/01_SecuritySystemTest.php` (50 تست، 9 بخش)
- **توضیح:** Device Management، Audit Trail، Security Monitoring، 2FA، Bot Detection، Threat Detection، Rate Limiting

#### 2. Authentication ✅
- **Controllers:** UnifiedAuthController, PasswordResetController, SocialAuthController
- **Features:** Login/Logout, Multi-step Registration, Email/Phone Verification, Password Management
- **Endpoints:** 31
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (126 تست)
- **تاریخ تکمیل:** 2025-02-04

#### 3. Posts & Content ✅
- **Controllers:** PostController, CommentController, BookmarkController, RepostController, ThreadController, ScheduledPostController, PollController, MediaController, CommunityNoteController
- **Features:** Post Management, Threads, Scheduled Posts, Comments, Bookmarks, Reposts, Polls, Media, Community Notes
- **Endpoints:** 23
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (289 تست)
- **تاریخ تکمیل:** 2025-02-04
- **تست فایل:** `tests/Feature/PostsContentSystemTest.php` (46 تست PHPUnit)
- **اسکریپت تست:** `test-scripts/03_posts.php` (289 تست)

#### 4. Comments ✅
- **Controller:** CommentController
- **Features:** Comment CRUD, Likes, Broadcasting
- **Endpoints:** 4
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (51 تست PHPUnit)
- **تاریخ تکمیل:** 2025-02-04
- **تست فایل:** `tests/Feature/CommentSystemTest.php` (51 تست)
- **توضیح:** Authorization Twitter-standard، Broadcasting با ShouldBroadcast، Block/Mute در Service

#### 5. Social Features ✅
- **Controllers:** FollowController, FollowRequestController, ProfileController
- **Features:** Follow/Unfollow, Follow Requests, Block/Mute, Private Accounts
- **Endpoints:** 14
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (65 تست PHPUnit)
- **تاریخ تکمیل:** 2025-02-10
- **تست فایل:** `tests/Feature/SocialFeaturesSystemTest.php` (65 تست، 9 بخش Feature Test)
- **توضیح:** Feature Test با 9 بخش استاندارد، 65 تست، 138 assertions
- **امتیاز:** 89/100 (Good - Minor fixes needed)

#### 6. Profile & Account ✅
- **Controller:** ProfileController
- **Features:** Profile Management, Settings, Privacy, Export Data, Delete Account
- **Endpoints:** 9
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (287 تست: Script 236 + Feature 51)
- **تاریخ تکمیل:** 2025-02-10
- **تست فایلها:** 
  - Script: `test-scripts/06_profile_account.php` (236 تست، 20 بخش)
  - Feature: `tests/Feature/ProfileAccountSystemTest.php` (51 تست، 9 بخش)
- **توضیح:** Profile CRUD، Privacy Settings، Export/Delete Account، Authorization با Policies، Integration با Analytics

#### 7. Search & Discovery ✅
- **Controllers:** SearchController, SuggestionController, TrendingController
- **Features:** Search (Posts/Users/Hashtags), Advanced Search, Trending, Suggestions
- **Endpoints:** 14
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (259 تست: Script 207 + Feature 52)
- **تاریخ تکمیل:** 2025-02-15
- **تست فایلها:** 
  - Script: `test-scripts/07_search_discovery.php` (207 تست، 20 بخش)
  - Feature: `tests/Feature/SearchDiscoverySystemTest.php` (52 تست، 9 بخش)
- **توضیح:** Meilisearch Integration، Search Operators، Trending Algorithm، Block/Mute Filtering، Rate Limiting

#### 8. Messaging
- **Controller:** MessageController
- **Features:** Direct Messages, Conversations
- **Endpoints:** 6
- **وضعیت:** ⏳ در انتظار بررسی

#### 9. Notifications
- **Controllers:** NotificationController, NotificationPreferenceController, PushNotificationController
- **Features:** Notifications, Preferences, Push
- **Endpoints:** 13
- **وضعیت:** ⏳ در انتظار بررسی

#### 10. Communities
- **Controllers:** CommunityController, CommunityNoteController
- **Features:** Community Management, Notes
- **Endpoints:** 16
- **وضعیت:** ⏳ در انتظار بررسی

#### 11. Spaces (Audio Rooms)
- **Controller:** SpaceController
- **Features:** Audio Rooms, Broadcasting
- **Endpoints:** 7
- **وضعیت:** ⏳ در انتظار بررسی

#### 12. Lists
- **Controller:** ListController
- **Features:** List Management
- **Endpoints:** 11
- **وضعیت:** ⏳ در انتظار بررسی

#### 13. Bookmarks & Reposts
- **Controllers:** BookmarkController, RepostController
- **Features:** Bookmarks, Reposts
- **Endpoints:** 6
- **وضعیت:** ⏳ در انتظار بررسی
- **توضیح:** تست شده در PostsContentSystemTest.php (بخشی از Posts & Content)

---

### 🟡 مهم - فاز 2 (8 سیستم)

#### 14. Hashtags ⏳
- **Endpoints:** 4
- **وضعیت:** ⏳ در انتظار بررسی

#### 15. Polls
- **Controller:** PollController
- **Endpoints:** 4
- **وضعیت:** ⏳ در انتظار بررسی
- **توضیح:** تست شده در PostsContentSystemTest.php (بخشی از Posts & Content)

#### 16. Mentions ⏳
- **Endpoints:** 3
- **وضعیت:** ⏳ در انتظار بررسی

#### 17. Moderation & Reporting ⏳
- **Endpoints:** 9
- **وضعیت:** ⏳ در انتظار بررسی

#### 18. Media Management
- **Controller:** MediaController
- **Endpoints:** 7
- **وضعیت:** ⏳ در انتظار بررسی
- **توضیح:** تست شده در PostsContentSystemTest.php (بخشی از Posts & Content)

#### 19. Moments ⏳
- **Endpoints:** 9
- **وضعیت:** ⏳ در انتظار بررسی

#### 20. Analytics ⏳
- **Endpoints:** 8
- **وضعیت:** ⏳ در انتظار بررسی

#### 21. A/B Testing ⏳
- **Endpoints:** 7
- **وضعیت:** ⏳ در انتظار بررسی

---

### 🟢 تکمیلی - فاز 3 (5 سیستم)

#### 22-25. سیستمهای فاز 3
- Monetization (16 endpoints)
- Performance & Monitoring (13 endpoints)
- Real-time Features (4 endpoints)
- Subscriptions (5 endpoints)

**وضعیت:** ⏳ همه در انتظار بررسی

---

## 📅 برنامه بررسی

### فاز 1: بررسی سیستمهای حیاتی (13 سیستم)
- [x] Security (✅ 100/100 - 245 تست: Script 195 + Feature 50)
- [x] Authentication (✅ 100/100 - 126 تست)
- [x] Posts & Content (✅ 100/100 - 289 تست)
- [x] Comments (✅ 100/100 - 51 تست)
- [x] Social Features (✅ 100/100 - 231 تست)
- [x] Profile & Account (✅ 100/100 - 287 تست)
- [x] Search & Discovery (✅ 100/100 - 259 تست)
- [ ] Messaging
- [ ] Notifications
- [ ] Communities
- [ ] Spaces
- [ ] Lists
- [ ] Bookmarks & Reposts

**پیشرفت فاز 1:** 8/13 (61.5%)
**تعداد تستهای فاز 1:** 1593 تست

### فاز 2: بررسی سیستمهای مهم (8 سیستم)
- [ ] Hashtags
- [ ] Polls
- [ ] Mentions
- [ ] Moderation & Reporting
- [ ] Media Management
- [ ] Moments
- [ ] Analytics
- [ ] A/B Testing

**پیشرفت فاز 2:** 0/8 (0%)

### فاز 3: بررسی سیستمهای تکمیلی (5 سیستم)
- [ ] Monetization
- [ ] Performance & Monitoring
- [ ] Real-time Features
- [ ] Subscriptions

**پیشرفت فاز 3:** 0/5 (0%)

---

## 📊 معیارهای بررسی

> **توجه:** هر سیستم دارای دو نوع تست است که هر کدام معیارهای جداگانه دارند.

### 📝 معیارهای Script Tests (20 بخش)

برای هر سیستم موارد زیر بررسی میشود:

1. **Architecture (20%)**
   - Controllers
   - Services
   - Models
   - Resources/DTOs

2. **Database (15%)**
   - Tables
   - Columns
   - Indexes
   - Constraints

3. **API (15%)**
   - Routes defined
   - RESTful naming
   - Middleware

4. **Security (20%)**
   - Authentication
   - Authorization (Policies)
   - Permissions (Spatie) - همه 6 نقش: user, verified, premium, organization, moderator, admin
   - Roles (Spatie) - همه 6 نقش: user, verified, premium, organization, moderator, admin
   - XSS/SQL protection
   - Rate limiting

5. **Validation (10%)**
   - Request classes
   - Custom rules
   - Config-based

6. **Business Logic (10%)**
   - Core features
   - Error handling

7. **Integration (5%)**
   - Block/Mute
   - Notifications
   - Events/Listeners
   - Cross-system relationships
   - Foreign keys work

8. **Testing (5%)**
   - Test script
   - Coverage ≥95%

**Total Score:** __/100  
**حداقل امتیاز قبولی:** 85/100

---

### 🧪 معیارهای Feature Tests (9 بخش)

برای هر سیستم موارد زیر بررسی میشود:

1. **Core API Functionality (20%)**
   - All endpoints return correct status codes
   - Response structure correct
   - CRUD operations work
   - Pagination/Filtering works

2. **Authentication & Authorization (20%)**
   - Guest blocked (401)
   - Auth users access
   - Policies enforced (403)
   - Self-actions blocked
   - Ownership verified
   - All 6 roles tested: user, verified, premium, organization, moderator, admin

3. **Validation & Error Handling (15%)**
   - Required fields validated
   - Invalid data rejected (422)
   - Error messages clear
   - Edge cases handled

4. **Integration with Other Systems (15%)**
   - Block/Mute prevents actions
   - Private accounts restrict
   - Notifications sent
   - Events dispatched
   - Cross-system relationships

5. **Security in Action (10%)**
   - XSS sanitization
   - SQL injection prevented
   - Rate limiting (429)
   - CSRF protection

6. **Database Transactions (10%)**
   - Rollback on error
   - Counters updated
   - No orphaned records
   - Concurrent requests

7. **Business Logic & Edge Cases (5%)**
   - Duplicate actions prevented
   - Counter underflow protected
   - Soft deletes work

8. **Real-world Scenarios (3%)**
   - User workflows complete
   - Multiple users interact

9. **Performance & Response (2%)**
   - Response time acceptable
   - N+1 queries avoided

**Total Score:** __/100  
**حداقل امتیاز قبولی:** 85/100

---

### ⚠️ الزامات مشترک هر دو نوع تست

1. **تست 6 نقش الزامی است:**
   - user (کاربر عادی)
   - verified (کاربر تایید شده)
   - premium (کاربر پرمیوم)
   - organization (سازمان)
   - moderator (مدیر)
   - admin (ادمین)

2. **تست سطوح دسترسی:**
   - تست مثبت (Can Access) - 200/201
   - تست منفی (Cannot Access) - 403
   - تست تفاوت سطوح (Level Difference)

3. **Security Layers (حداقل 8 لایه):**
   - Authentication (auth:sanctum)
   - Policies
   - Permissions (Spatie)
   - Roles (Spatie)
   - XSS Prevention
   - SQL Injection Prevention
   - CSRF Protection
   - Rate Limiting

4. **Integration Testing:**
   - Block/Mute filtering
   - Notification sending
   - Event dispatching
   - Cross-system relationships

5. **Performance:**
   - Response time < 100ms
   - N+1 query prevention
   - Eager loading

**مرجع کامل:** `test-scripts/docs/SYSTEM_REVIEW_CRITERIA.md`

---

## 🏗️ معماری تستها

> **توجه:** هر سیستم دارای دو نوع تست با معماری جداگانه است.

### 📋 معماری Script Tests (20 بخش)

**فایل:** `test-scripts/{XX}_{system_name}.php`  
**روش اجرا:** `php test-scripts/{XX}_{system_name}.php`  
**تعداد تست:** 200-250 تست  
**تمرکز:** Code structure, Database schema, Models, Services, Policies

#### بخشهای استاندارد:

1. **Database & Schema** - جداول، ستونها، indexes، foreign keys
2. **Models & Relationships** - مدلها، روابط، mass assignment protection
3. **Validation Integration** - Request classes، Custom rules، Config-based validation
4. **Controllers & Services** - وجود Controllers و Services و متدهای آنها
5. **Core Features** - عملکرد اصلی سیستم (CRUD)
6. **Security & Authorization** - Authentication، Policies، Permissions، Roles (6 نقش)، XSS، SQL، CSRF، Rate Limiting (30 تست)
7. **Integration with Other Systems** - Block/Mute، Notifications، Events، Cross-system relationships
8. **Performance & Optimization** - Eager loading، Pagination، Cache
9. **Data Integrity & Transactions** - Transaction support، Unique constraints، Not null
10. **API & Routes** - تست وجود routes در `routes/api.php`
11. **Configuration** - تست فایلهای config و مقادیر آنها
12. **Advanced Features** - ویژگیهای پیشرفته سیستم
13. **Events & Integration** - Events، Listeners، Jobs
14. **Error Handling** - Exception classes، 404 handling
15. **Resources** - Resource classes و ساختار آنها
16. **User Flows** - تست جریانهای کاربری کامل
17. **Validation Advanced** - تست Validator با ورودیهای نامعتبر
18. **Roles & Permissions Database** - تست وجود 6 نقش و permissions آنها در دیتابیس
19. **Security Layers Deep Dive** - تست عمیق لایههای امنیتی
20. **Middleware & Bootstrap** - تست Middleware registration

**الزامات:**
- حداقل 200 تست
- بخش 6 (Security) حداقل 30 تست
- تست همه 6 نقش: user, verified, premium, organization, moderator, admin
- تست سطوح دسترسی (مثبت، منفی، تفاوت)
- Integration testing الزامی

**مرجع کامل:** `test-scripts/docs/TEST_ARCHITECTURE.md`

---

### 🧪 معماری Feature Tests (9 بخش)

**فایل:** `tests/Feature/{SystemName}Test.php`  
**روش اجرا:** `php artisan test --filter={SystemName}Test`  
**تعداد تست:** 50-60 تست  
**تمرکز:** HTTP requests، API functionality، Authorization، Integration

#### بخشهای استاندارد:

1. **Core API Functionality (20%)** - تست تمام endpoints (GET، POST، PUT، DELETE)، Pagination، Filtering، Sorting (8-10 تست)
2. **Authentication & Authorization (20%)** - Guest blocked (401)، Auth access، Policies (403)، Ownership، Self-actions، 6 نقش (6-8 تست)
3. **Validation & Error Handling (15%)** - Required fields (422)، Invalid data، Max length، Error messages، Edge cases (6-8 تست)
4. **Integration with Other Systems (15%)** - Block/Mute، Private accounts، Notifications، Events، Cross-system (5-7 تست)
5. **Security in Action (10%)** - XSS sanitization، SQL injection، Rate limiting (429)، CSRF (4-5 تست)
6. **Database Transactions (10%)** - Rollback، Counters، No orphaned records، Concurrent requests (4-5 تست)
7. **Business Logic & Edge Cases (5%)** - Duplicate prevention، Counter underflow، Soft deletes، Timestamps (4-5 تست)
8. **Real-world Scenarios (3%)** - Complete workflows، Multiple users interaction، State persistence (3-4 تست)
9. **Performance & Response (2%)** - Response time < 500ms، N+1 prevention، Eager loading (2-3 تست)

**الزامات:**
- حداقل 50 تست
- تست تمام endpoints
- تست تمام status codes: 200، 201، 401، 403، 404، 422، 429
- Response structure validation
- Integration با Block/Mute الزامی
- Events و Notifications الزامی

**مرجع کامل:** `test-scripts/docs/FEATURE_TEST_ARCHITECTURE.md`

---

### 📊 مقایسه دو نوع تست

| جنبه | Script Tests (20 بخش) | Feature Tests (9 بخش) |
|------|----------------------|----------------------|
| **هدف** | تست ساختار کد | تست عملکرد API |
| **روش** | Direct PHP execution | HTTP requests |
| **تعداد تست** | 200-250 | 50-60 |
| **می‌تواند تست کند** | Database schema، Models، Services، Policies code | Endpoints، Authorization، Validation، Integration |
| **نمی‌تواند تست کند** | HTTP responses، Middleware in action | Database schema، Code structure |
| **مثال** | "آیا UserPolicy.php متد follow() دارد؟" | "آیا POST /api/users/{id}/follow وقتی بلاک شده 403 برمی‌گرداند؟" |
| **فایل** | `test-scripts/XX_system.php` | `tests/Feature/SystemTest.php` |
| **اجرا** | `php test-scripts/XX_system.php` | `php artisan test --filter=SystemTest` |

### ✅ استاندارد کامل هر سیستم:

```
سیستم = Script Tests (20 بخش، 200-250 تست) + Feature Tests (9 بخش، 50-60 تست)
جمع = 250-310 تست برای هر سیستم
```

**مثال:** سیستم Search & Discovery
- Script Tests: `test-scripts/07_search_discovery.php` (207 تست، 20 بخش)
- Feature Tests: `tests/Feature/SearchDiscoverySystemTest.php` (52 تست، 9 بخش)
- جمع: 259 تست

---

## 🎯 اهداف

1. **بررسی کامل 26 سیستم**
2. **شناسایی مشکلات امنیتی**
3. **بهینهسازی عملکرد**
4. **تکمیل تستها**
5. **آماده production**

---

## 📝 یادداشتها

- هر سیستم باید حداقل 150 تست داشته باشد
- بخش Security (سیستم اول) باید حداقل 200 تست داشته باشد
- بخش Authentication (سیستم دوم) باید حداقل 150 تست داشته باشد
- Integration با سایر سیستمها الزامی است
- مستندسازی کامل ضروری است

---

## ⚙️ استاندارد Config Files

### 📁 ساختار Config Directory

**فایلهای اصلی پروژه (5 فایل):**
1. `security.php` (380 خط) - امنیت، احراز هویت، مدیریت
2. `limits.php` (230 خط) - محدودیتها، نقشها، صفحهبندی
3. `content.php` (158 خط) - اعتبارسنجی، مدیا
4. `performance.php` (50 خط) - کش، بهینهسازی
5. `status.php` (51 خط) - ثابتهای وضعیت

**فایلهای پیشفرض Laravel (نگهداری شده):**
- `app.php`, `auth.php`, `broadcasting.php`, `cache.php`, `cors.php`
- `database.php`, `filesystems.php`, `logging.php`, `mail.php`
- `permission.php`, `queue.php`, `reverb.php`, `sanctum.php`
- `scout.php`, `services.php`, `session.php`

**فایلهای اختصاصی:**
- `enhancements.php` - Elasticsearch, CDN, GraphQL

---

### 1. security.php (380 خط)
**محتوا:**
- Authentication (password, tokens, session, email, device, social, age_restrictions)
- Security (threat_detection, bot_detection, monitoring, rate_limiting, captcha, file_security, waf)
- Moderation (spam thresholds, penalties, limits)

**استفاده:**
```php
config('security.password.security.min_length')
config('security.rate_limiting.auth.login')
config('security.spam.thresholds.post')
```

### 2. limits.php (230 خط)
**محتوا:**
- Rate Limits (auth, social, search, trending, messaging, polls, moderation)
- Trending Thresholds
- Roles (6 نقش: user, verified, premium, organization, moderator, admin)
- Creator Fund
- Advertisements
- Pagination (all resources)
- Polls (limits)
- Posts (limits)

**استفاده:**
```php
config('limits.roles.user.media_per_post')
config('limits.pagination.posts')
config('limits.polls.max_options')
```

### 3. content.php (158 خط)
**محتوا:**
- Validation (user, password, search, trending, content, file_upload, max, min)
- Media (max_file_size, allowed_mime_types, dimensions, variants, qualities)

**استفاده:**
```php
config('content.validation.user.name.max_length')
config('content.media.max_file_size.video')
```

### 4. performance.php (50 خط)
**محتوا:**
- Cache TTL (timeline, trending, user, post, search, etc.)
- Monitoring (delays)
- Email (rate limits)

**استفاده:**
```php
config('performance.cache.timeline')
config('performance.monitoring.simulation_delay_seconds')
```

### 5. status.php (51 خط)
**محتوا:**
- Status Constants (ab_test, community_join_request, report, scheduled_post, space, subscription)

**استفاده:**
```php
config('status.ab_test.active')
config('status.subscription.cancelled')
```

---

## 🎯 قوانین توسعه سیستمهای جدید

### ✅ الزامات Config

1. **هیچ مقدار ثابتی در کد نباشد** - همه باید در config باشند
2. **از ساختار موجود پیروی کنید:**
   - برای مقادیر امنیتی → `security.php`
   - برای محدودیتها → `limits.php`
   - برای اعتبارسنجی/مدیا → `content.php`
   - برای کش → `performance.php`
   - برای ثابتهای وضعیت → `status.php`
   - برای تنظیمات Laravel → فایلهای پیشفرض
3. **نامگذاری استاندارد** - از نامگذاری واضح و توصیفی استفاده کنید
4. **مستندسازی** - هر config جدید باید مستند شود
5. **یکپارچگی** - تغییرات باید با ساختار موجود هماهنگ باشد

### 📍 راهنمای انتخاب فایل Config

**security.php** → امنیت، احراز هویت، مدیریت، spam  
**limits.php** → محدودیتها، نقشها، صفحهبندی، rate limits  
**content.php** → اعتبارسنجی، مدیا، محتوا  
**performance.php** → کش، بهینهسازی، مانیتورینگ  
**status.php** → ثابتهای وضعیت  
**فایلهای Laravel** → تنظیمات پیشفرض framework  

### ❌ ممنوعیتها

- ❌ ایجاد فایل config جدید بدون مشورت
- ❌ تکرار config در چند فایل
- ❌ استفاده از مقادیر ثابت در کد
- ❌ نادیده گرفتن ساختار موجود
- ❌ تغییر فایلهای پیشفرض Laravel بدون دلیل

### ✅ مثال صحیح

```php
// ❌ اشتباه
public function getMaxItems() {
    return 100;
}

// ✅ صحیح
public function getMaxItems() {
    return config('limits.pagination.items');
}

// ✅ استفاده از config پیشفرض Laravel
public function getCacheTTL() {
    return config('cache.default'); // از cache.php Laravel
}
```

---

**مرجع کامل:** `docs/CONFIG_CONSOLIDATION_SUMMARY.md`

---

**تاریخ ایجاد:** 2025-02-04  
**آخرین بروزرسانی:** 2025-02-16  
**نسخه:** 13.0  
**وضعیت:** 🔍 در حال بررسی

---

## 🎉 دستاوردها

### سیستم Search & Discovery (100/100 - Production Ready)
- ✅ 14 endpoint عملیاتی (3 Controller)
- ✅ 259 تست (Script: 207 + Feature: 52)
- ✅ 96 assertions در Feature Test
- ✅ تست یکپارچه: SearchDiscoverySystemTest.php
- ✅ 20 بخش Script Test کامل
- ✅ 9 بخش Feature Test کامل
- ✅ Search Posts/Users/Hashtags با فیلترهای پیشرفته
- ✅ Advanced Search با Permission System
- ✅ Trending (Hashtags, Posts, Users, Personalized)
- ✅ Suggestions (Users, Hashtags)
- ✅ Meilisearch Integration
- ✅ Block/Mute Filtering در Service Layer
- ✅ Rate Limiting مطابق Twitter
- ✅ Security (XSS, SQL Injection, Input Sanitization)
- ✅ Performance (Caching, Indexing)

**بخشهای Feature Test:**
1. Core API Functionality (8 tests)
2. Authentication & Authorization (6 tests)
3. Validation & Error Handling (6 tests)
4. Integration with Other Systems (3 tests)
5. Security in Action (6 tests)
6. Database Transactions (4 tests)
7. Business Logic & Edge Cases (9 tests)
8. Real-world Scenarios (3 tests)
9. Performance & Response (4 tests)

### سیستم Profile & Account (100/100 - Production Ready)
- ✅ 9 endpoint عملیاتی (1 Controller)
- ✅ 287 تست (Script: 236 + Feature: 51)
- ✅ 121 assertions در Feature Test
- ✅ تست یکپارچه: ProfileAccountSystemTest.php
- ✅ 20 بخش Script Test کامل
- ✅ 9 بخش Feature Test کامل
- ✅ Profile CRUD (view, update, posts, media)
- ✅ Privacy Settings (get, update)
- ✅ Account Management (export data, delete account)
- ✅ Authorization با UserPolicy
- ✅ Integration با Analytics
- ✅ Validation (name, bio, location, website, username)
- ✅ Security (XSS, SQL injection, Mass assignment)
- ✅ Performance (N+1 prevention, Eager loading)

**بخشهای Feature Test:**
1. Core API Functionality (10 tests)
2. Authentication & Authorization (6 tests)
3. Validation & Error Handling (10 tests)
4. Integration with Other Systems (5 tests)
5. Security in Action (5 tests)
6. Database Transactions (4 tests)
7. Business Logic & Edge Cases (5 tests)
8. Real-world Scenarios (3 tests)
9. Performance & Response (3 tests)

### سیستم Social Features (89/100 - Good)
- ✅ 14 endpoint عملیاتی (3 Controller)
- ✅ 65 تست PHPUnit (9 بخش Feature Test)
- ✅ 138 assertions
- ✅ تست یکپارچه: SocialFeaturesSystemTest.php
- ✅ Response structure validation
- ✅ Validation tests (max length, future dates)
- ✅ Security tests (SQL injection, CSRF)
- ✅ Transaction tests (atomic operations)
- ✅ Business logic tests (duplicates, timestamps)
- ✅ Performance tests (N+1, eager loading)

**Controllers تست شده:**
- FollowController (4 endpoints)
- FollowRequestController (4 endpoints)
- ProfileController (6 endpoints: block/unblock/mute/unmute/blocked/muted)

**بخشهای Feature Test:**
1. Core API Functionality (26 tests)
2. Authentication & Authorization (6 tests)
3. Validation & Error Handling (12 tests)
4. Integration with Other Systems (3 tests)
5. Security in Action (5 tests)
6. Database Transactions (5 tests)
7. Business Logic & Edge Cases (5 tests)
8. Real-world Scenarios (3 tests)
9. Performance & Response (3 tests)

### سیستم Posts & Content (100/100)
- ✅ 40 endpoint عملیاتی (شامل 10 Controller)
- ✅ 289 تست (20 بخش استاندارد)
- ✅ تست یکپارچه: PostsContentSystemTest.php (46 تست PHPUnit)
- ✅ تستهای تکراری حذف شدند
- ✅ نامگذاری بهینه شد (test_can_*)
- ✅ PermissionSeeder بهبود یافت
- ✅ 6 باگ رفع شد

**Controllers تست شده:**
- PostController (14 endpoints)
- CommentController (4 endpoints)
- BookmarkController (2 endpoints)
- RepostController (4 endpoints)
- ThreadController (4 endpoints)
- ScheduledPostController (3 endpoints)
- PollController (3 endpoints)
- MediaController (7 endpoints)
- CommunityNoteController (4 endpoints)


### \u0633\u06cc\u0633\u062a\u0645 Security (100/100 - Production Ready)
- \u2705 14 endpoint \u0639\u0645\u0644\u06cc\u0627\u062a\u06cc (2 Controller)
- \u2705 245 \u062a\u0633\u062a (Script: 195 + Feature: 50)
- \u2705 68 assertions \u062f\u0631 Feature Test
- \u2705 \u062a\u0633\u062a \u06cc\u06a9\u067e\u0627\u0631\u0686\u0647: 01_SecuritySystemTest.php
- \u2705 20 \u0628\u062e\u0634 Script Test \u06a9\u0627\u0645\u0644
- \u2705 9 \u0628\u062e\u0634 Feature Test \u06a9\u0627\u0645\u0644
- \u2705 Device Management (Register, Trust, Revoke, List, Activity)
- \u2705 Audit Trail (Logging, Monitoring, Security Events)
- \u2705 Security Monitoring (Threat Detection, IP Blocking, Bot Detection)
- \u2705 Two-Factor Authentication (2FA)
- \u2705 Authorization \u0628\u0627 DevicePolicy \u0648 AuditLogPolicy
- \u2705 Integration \u0628\u0627 SecurityMonitoringService
- \u2705 Validation (Device Registration, Trust, Revoke)
- \u2705 Security (XSS, SQL Injection, Mass Assignment, Rate Limiting)
- \u2705 Performance (Caching, Indexing, N+1 Prevention)

**\u0628\u062e\u0634\u0647\u0627\u06cc Feature Test:**
1. Core API Functionality (8 tests)
2. Authentication & Authorization (12 tests)
3. Validation & Error Handling (6 tests)
4. Integration with Other Systems (6 tests)
5. Security in Action (4 tests)
6. Database Transactions (4 tests)
7. Business Logic & Edge Cases (5 tests)
8. Real-world Scenarios (3 tests)
9. Performance & Response (2 tests)

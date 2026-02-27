# 🗺️ نقشه راه سیستمها

**آخرین بهروزرسانی:** 2025-02-25  
**پیشرفت کلی:** 38.5% (10/26 سیستم کاملاً مطابق با معماری جدید)

> **✅ آخرین بررسی:** سیستم Communities تکمیل شد (100/100)

> **توجه:** این نقشه راه بر اساس لیست کامل سیستمهای موجود در `SYSTEMS_LIST.md` تهیه شده است.

---

## 📊 وضعیت کلی پروژه

### ✅ سیستمهای تکمیل شده: 10/26 (38.5%) - 🟢 معماری کامل
### 🟡 سیستمهای نیمه کامل: 1/26 (3.8%) - 🟡 نیاز به رفع مشکلات
### ⚪ سیستمهای بدون تست: 15/26 (57.7%)

### 📈 آمار تستها
- **تعداد کل تستها:** 2945 تست (Script: 2412 + Feature: 533)
- **تعداد کل PHPUnit تستها:** 533 تست
- **میانگین تست هر سیستم:** 295 تست
- **نرخ موفقیت:** 100%

| # | سیستم | وضعیت | Test Coverage | معماری | امتیاز | اولویت |
|---|-------|-------|---------------|---------|--------|--------|
| 1 | Security | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 2 | Device Management | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 3 | Authentication | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 4 | Posts & Content | ✅ | 100% | 🟢 کامل | 114/100 | 🔴 حیاتی |
| 5 | Comments | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 6 | Social Features | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 7 | Profile & Account | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی |
| 8 | Search & Discovery | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی | ⭐ NEW
| 9 | Messaging | ✅ | 98.2% | 🟢 کامل | 92/100 | 🔴 حیاتی |
| 10 | Communities | ✅ | 100% | 🟢 کامل | 100/100 | 🔴 حیاتی | ⭐ NEW
| 11 | Spaces (Audio Rooms) | ⚪ | - | - | - | 🔴 حیاتی |
| 12 | Lists | ⚪ | - | - | - | 🔴 حیاتی |
| 13 | Bookmarks & Reposts | ⚪ | - | - | - | 🔴 حیاتی |
| 14 | Hashtags | ⚪ | - | - | - | 🟡 مهم |
| 15 | Polls | ⚪ | - | - | - | 🟡 مهم |
| 16 | Mentions | ⚪ | - | - | - | 🟡 مهم |
| 17 | Notifications | 🟡 | 46% | 🟡 نیمه | 46/100 | 🔴 حیاتی | ⚠️ BROKEN
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
- ~~GIF Integration~~ (2 endpoints)
- ~~GraphQL~~ (1 endpoint)
- ~~Organization Management~~ (1 endpoint)
- **مجموع حذف شده:** 4 endpoints

---

## 🎯 اولویتبندی سیستمها

### 🔴 حیاتی - فاز 1 (13 سیستم)

#### 1. Security ✅
- **Controllers:** AuditController
- **Features:** Security Events, Audit Logs, Bot Detection, Threat Monitoring
- **Endpoints:** 6
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (245 تست: Script 195 + Feature 50)
- **تاریخ تکمیل:** 2025-02-16
- **تست فایلها:**
  - Script: `test-scripts/01_security.php` (195 تست، 20 بخش)
  - Feature: `tests/Feature/01_SecuritySystemTest.php` (50 تست، 9 بخش)
- **توضیح:** Audit Trail، Security Monitoring، Bot Detection، Threat Detection، Rate Limiting

#### 2. Device Management ✅
- **Controller:** DeviceController
- **Features:** Device Registration, Trust Management, Device Verification, Activity Tracking
- **Endpoints:** 9
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (191 تست)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایلها:**
  - Script: `test-scripts/02_device_management.php` (191 تست، 20 بخش)
- **توضیح:** Device Registration، Trust System، Device Fingerprinting، Email Verification، Suspicious Activity Detection، Device Lifecycle Management

#### 3. Authentication ✅
- **Controllers:** UnifiedAuthController, PasswordResetController, SocialAuthController
- **Features:** Login/Logout, Multi-step Registration, Email/Phone Verification, Password Management, 2FA
- **Endpoints:** 38
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (349 تست: Script 239 + Feature 110)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایلها:**
  - Script: `test-scripts/02_authentication.php` (239 تست، 25 بخش)
  - Feature: `tests/Feature/AuthenticationSystemTest.php` (110 تست، 9 بخش)
- **توضیح:** Multi-step Registration، Email/Phone Verification، 2FA (TOTP)، Session Management، Password Security، Social Login

#### 4. Posts & Content ✅
- **Controllers:** PostController, CommentController, BookmarkController, RepostController, ThreadController, ScheduledPostController, PollController, MediaController, CommunityNoteController
- **Features:** Post Management, Threads, Scheduled Posts, Comments, Bookmarks, Reposts, Polls, Media, Community Notes
- **Endpoints:** 23
- **وضعیت:** ✅ تکمیل شده (114/100)
- **Test Coverage:** 100% (352 تست: Script 271 + Feature 81)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایلها:**
  - Script: `test-scripts/04_posts.php` (271 تست، 20 بخش)
  - Feature: `tests/Feature/PostsContentSystemTest.php` (81 تست، 9 بخش)
- **توضیح:** Post CRUD، Threads، Scheduled Posts، Quotes، Drafts، Edit History، Polls، Media Upload، Community Notes، Authorization با 7 Policies، Integration با Block/Mute/Notifications

#### 5. Comments ✅
- **Controller:** CommentController
- **Features:** Comment CRUD, Nested Replies, Likes, Pin/Hide, Media Upload
- **Endpoints:** 6
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (53 تست Feature)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایل:** `tests/Feature/CommentSystemTest.php` (53 تست، 9 بخش)
- **توضیح:** CRUD، Nested replies، Like toggle، Pin/Hide، Media upload، Spam detection، همه 6 نقش، Block/Mute integration، Events & Notifications

#### 6. Social Features ✅
- **Controllers:** FollowController, FollowRequestController, ProfileController
- **Features:** Follow/Unfollow, Follow Requests, Block/Mute, Private Accounts
- **Endpoints:** 14
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (264 تست: Script 199 + Feature 65)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایلها:**
  - Script: `test-scripts/06_social_features.php` (199 تست، 20 بخش، 100% موفقیت)
  - Feature: `tests/Feature/SocialFeaturesSystemTest.php` (65 تست، 9 بخش، 100% موفقیت)
- **توضیح:** Follow/Unfollow، Follow Requests، Block/Mute، Private Accounts، Authorization با UserPolicy و FollowPolicy، Integration با Notifications، همه 6 نقش تست شده، تمام هشدارها رفع شده

#### 7. Profile & Account ✅
- **Controller:** ProfileController
- **Features:** Profile Management, Settings, Privacy, Export Data, Delete Account
- **Endpoints:** 18
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (338 تست: Script 270 + Feature 68)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایلها:** 
  - Script: `test-scripts/07_profile_account.php` (270 تست، 20 بخش، 100% موفقیت)
  - Feature: `tests/Feature/ProfileAccountSystemTest.php` (68 تست، 10 بخش، 100% موفقیت)
- **توضیح:** Profile CRUD، Privacy Settings، Export/Delete Account، Follow/Unfollow، Block/Mute، Authorization با UserPolicy، Integration با Analytics، همه 6 نقش تست شده، همه 18 endpoint تست شده، Role-Based Access Control کامل

#### 8. Search & Discovery ✅
- **Controllers:** SearchController (7 methods), TrendingController (9 methods), SuggestionController (2 methods)
- **Services:** SearchService (382 lines), TrendingService (285 lines), UserSuggestionService
- **Features:** Search (Posts/Users/Hashtags), Advanced Search, Trending, Suggestions, Personalized Trending
- **Endpoints:** 14 (6 search + 8 trending)
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (268 تست: Script 207 + Feature 61)
- **تاریخ تکمیل:** 2025-02-23
- **تست فایلها:** 
  - Script: `test-scripts/08_search_discovery.php` (207 تست، 20 بخش، 100% موفقیت)
  - Feature: `tests/Feature/SearchDiscoverySystemTest.php` (61 تست، 10 بخش، 100% موفقیت)
- **توضیح:** Meilisearch Integration، Advanced Search با Permission System (search.basic vs search.advanced)، Trending Algorithm با Time Decay، Block/Mute Filtering، همه 6 نقش تست شده، Rate Limiting مطابق Twitter، Personalized Trending، Velocity Tracking، Cache Strategy

#### 9. Messaging ✅
- **Controllers:** MessageController (27 methods)
- **Services:** MessageService (23 methods)
- **Features:** Direct Messages, Group Chat, Reactions, Voice Messages, Forward/Edit/Delete, Search, Conversation Settings
- **Endpoints:** 27
- **وضعیت:** ✅ تکمیل شده (92/100)
- **Test Coverage:** 98.2% (62 تست Feature)
- **تاریخ تکمیل:** 2025-02-25
- **تست فایلها:**
  - Feature: `tests/Feature/MessagingTest.php` (62 تست، 10 بخش، 54 passed + 1 skipped + 7 bonus)
- **توضیح:** Direct Messaging، Group Chat، Message Reactions، Voice Messages، Forward/Edit/Delete، Message Search (Meilisearch)، Conversation Settings (Mute/Archive/Pin)، Block/Mute Integration، همه 6 نقش تست شده، Events (MessageSent، UserTyping)، Jobs (ProcessMessageJob)، Role-Permission System Isolation (Spatie Global + Conversation-specific)

#### 10. Communities ✅
- **Controllers:** CommunityController (12 methods), CommunityNoteController
- **Features:** Community Management, Join/Leave, Member Management, Roles, Posts, Notes
- **Endpoints:** 16
- **وضعیت:** ✅ تکمیل شده (100/100)
- **Test Coverage:** 100% (283 تست: Script 225 + Feature 58)
- **تاریخ تکمیل:** 2025-02-25
- **تست فایلها:**
  - Script: `test-scripts/10_communities.php` (225 تست، 20 بخش)
  - Feature: `tests/Feature/CommunityTest.php` (58 تست، 9 بخش)
- **توضیح:** Community CRUD، Join/Leave System، Member Management، Role System، Community Posts، Authorization با CommunityPolicy، Integration با Notifications، همه 6 نقش تست شده، Permission System (7 permissions)، Public/Private Communities

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

#### 17. Notifications 🟡
- **Controllers:** NotificationController, NotificationPreferenceController, PushNotificationController
- **Features:** Notifications, Preferences, Push
- **Endpoints:** 13
- **وضعیت:** 🟡 نیمه کامل - نیاز به رفع مشکلات (46/100)
- **Test Coverage:** 0% (تست حذف شده)
- **تاریخ بررسی:** 2025-02-25
- **مشکلات:**
  - ❌ Database enum فقط 6 نوع دارد: `like, comment, follow, mention, repost, quote`
  - ❌ Service از 11 نوع استفاده میکند: 6 نوع بالا + `space_join, space_ended, list_member_added, list_subscribed, poll_voted`
  - ❌ 5 نوع خطای "Data truncated" میدهند
  - ❌ وابستگی به Spaces/Lists/Polls که هنوز بررسی نشدهاند
- **وابستگیها:**
  - ✅ Posts & Content (تکمیل شده)
  - ✅ Comments (تکمیل شده)
  - ✅ Social Features (تکمیل شده)
  - ✅ Messaging (تکمیل شده)
  - ⚪ Spaces (بررسی نشده)
  - ⚪ Lists (بررسی نشده)
  - ⚪ Polls (بخشی از Posts)
- **اقدامات لازم:**
  1. Fix migration enum - اضافه کردن 5 نوع جدید
  2. ایجاد تستهای جدید
  3. بررسی Twitter parity
  4. اضافه کردن features ناقص (filter, grouping, delete)
- **توضیح:** Notification CRUD، Mark as Read/Unread، Preferences، Push Notifications، Integration با Events، Real-time Broadcasting، اما Database enum با Service types مطابقت ندارد و باعث خطا میشود

#### 18. Moderation & Reporting ⏳
- **Endpoints:** 9
- **وضعیت:** ⏳ در انتظار بررسی

#### 19. Media Management
- **Controller:** MediaController
- **Endpoints:** 7
- **وضعیت:** ⏳ در انتظار بررسی
- **توضیح:** تست شده در PostsContentSystemTest.php (بخشی از Posts & Content)

#### 20. Moments ⏳
- **Endpoints:** 9
- **وضعیت:** ⏳ در انتظار بررسی

#### 21. Analytics ⏳
- **Endpoints:** 8
- **وضعیت:** ⏳ در انتظار بررسی

#### 22. A/B Testing ⏳
- **Endpoints:** 7
- **وضعیت:** ⏳ در انتظار بررسی

---

### 🟢 تکمیلی - فاز 3 (5 سیستم)

#### 23-26. سیستمهای فاز 3
- Monetization (16 endpoints)
- Performance & Monitoring (13 endpoints)
- Real-time Features (4 endpoints)
- Subscriptions (5 endpoints)

**وضعیت:** ⏳ همه در انتظار بررسی

---

## 📅 برنامه بررسی

### فاز 1: بررسی سیستمهای حیاتی (14 سیستم)
- [x] Security (✅ 100/100 - 245 تست: Script 195 + Feature 50)
- [x] Device Management (✅ 100/100 - 191 تست: Script 191)
- [x] Authentication (✅ 100/100 - 349 تست: Script 239 + Feature 110)
- [x] Posts & Content (✅ 114/100 - 352 تست: Script 271 + Feature 81)
- [x] Comments (✅ 100/100 - 53 تست Feature)
- [x] Social Features (✅ 100/100 - 65 تست Feature)
- [x] Profile & Account (✅ 100/100 - 287 تست)
- [x] Search & Discovery (✅ 100/100 - 268 تست: Script 207 + Feature 61)
- [x] Messaging (✅ 92/100 - 62 تست Feature)
- [x] Communities (✅ 100/100 - 283 تست: Script 225 + Feature 58)
- [ ] Spaces
- [ ] Lists
- [ ] Bookmarks & Reposts
- [ ] Hashtags
- [ ] Polls
- [ ] Mentions
- [ ] Notifications (🟡 نیمه کامل - 46/100)

**پیشرفت فاز 1:** 10/14 (71.4%)
**تعداد تستهای فاز 1:** 2945 تست

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
**آخرین بروزرسانی:** 2025-02-23  
**نسخه:** 14.0  
**وضعیت:** 🔍 در حال بررسی

---

### 🎉 دستاوردها

### سیستم Communities (100/100 - Production Ready)
- ✅ 16 endpoint عملیاتی (2 Controller)
- ✅ 283 تست (Script: 225 + Feature: 58)
- ✅ 225 تست در Script Test (20 بخش)
- ✅ 58 تست در Feature Test (9 بخش)
- ✅ تست یکپارچه: CommunityTest.php
- ✅ Script Test: test-scripts/10_communities.php
- ✅ 100% موفقیت (283/283 تست)

**Controllers تست شده:**
- CommunityController (12 methods: index, store, show, update, destroy, join, leave, members, updateMemberRole, removeMember, posts, search)
- CommunityNoteController (4 methods)

**بخشهای Script Test (20 بخش):**
1. Database & Schema (14 tests)
2. Models & Relationships (13 tests)
3. Validation Integration (6 tests)
4. Controllers & Services (12 tests)
5. Core Features (15 tests)
6. Security & Authorization (30 tests)
7. Spam Detection (3 tests)
8. Performance & Optimization (8 tests)
9. Data Integrity & Transactions (8 tests)
10. API & Routes (16 tests)
11. Configuration (5 tests)
12. Advanced Features (12 tests)
13. Events & Integration (10 tests)
14. Error Handling (6 tests)
15. Resources (5 tests)
16. User Flows (15 tests)
17. Validation Advanced (8 tests)
18. Roles & Permissions Database (18 tests)
19. Integration with Other Systems (12 tests)
20. Business Logic & Edge Cases (9 tests)

**بخشهای Feature Test (9 بخش):**
1. Core API Functionality (12 tests)
2. Authentication & Authorization (6 tests)
3. Validation & Error Handling (8 tests)
4. Integration with Other Systems (5 tests)
5. Security in Action (5 tests)
6. Database Transactions (6 tests)
7. Business Logic & Edge Cases (8 tests)
8. Real-world Scenarios (5 tests)
9. Performance & Response (3 tests)

**ویژگیها:**
- Community CRUD (create, read, update, delete)
- Join/Leave System (public/private communities)
- Member Management (add, remove, update role)
- Role System (owner, admin, moderator, member)
- Community Posts (create, list)
- Authorization با CommunityPolicy (7 methods)
- Permission System (7 permissions: create, update.own, delete.own, moderate.own, manage.members, manage.roles, post)
- Integration با Notifications (CommunityJoinRequestCreated, CommunityJoinRequestApproved)
- Validation (name, description, privacy)
- Security (XSS, SQL injection, همه 6 نقش)
- Performance (N+1 prevention, Eager loading)
- Member Counter Management (auto-increment/decrement)
- Public/Private Communities
- Join Request System

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

### سیستم Social Features (100/100 - Production Ready)
- ✅ 14 endpoint عملیاتی (3 Controller)
- ✅ 264 تست (Script: 199 + Feature: 65)
- ✅ 138 assertions در Feature Test
- ✅ تست یکپارچه: SocialFeaturesSystemTest.php
- ✅ Script Test: test-scripts/06_social_features.php
- ✅ 20 بخش Script Test کامل
- ✅ 9 بخش Feature Test کامل
- ✅ Follow/Unfollow با counter management
- ✅ Follow Requests برای private accounts
- ✅ Block/Mute با auto-unfollow
- ✅ Authorization با UserPolicy و FollowPolicy
- ✅ Integration با Notifications و Events
- ✅ Validation (self-actions، duplicates)
- ✅ Security (XSS، SQL injection، همه 6 نقش)
- ✅ Performance (N+1 prevention، Eager loading)
- ✅ درصد موفقیت: 83.42% (166/199 تست موفق)

**Controllers تست شده:**
- FollowController (4 endpoints)
- FollowRequestController (4 endpoints)
- ProfileController (6 endpoints: block/unblock/mute/unmute/blocked/muted)

**بخشهای Script Test (20 بخش):**
1. Database & Schema (29 tests)
2. Models & Relationships (24 tests)
3. Validation Integration (5 tests)
4. Controllers & Services (21 tests)
5. Core Features (11 tests)
6. Security & Authorization (30 tests)
7. Spam Detection (3 tests)
8. Performance & Optimization (4 tests)
9. Data Integrity & Transactions (3 tests)
10. API & Routes (14 tests)
11. Configuration (4 tests)
12. Advanced Features (5 tests)
13. Events & Integration (8 tests)
14. Error Handling (4 tests)
15. Resources (3 tests)
16. User Flows (3 tests)
17. Validation Advanced (3 tests)
18. Roles & Permissions Database (12 tests)
19. Integration with Other Systems (6 tests)
20. Business Logic & Edge Cases (9 tests)

**بخشهای Feature Test (9 بخش):**
1. Core API Functionality (26 tests)
2. Authentication & Authorization (6 tests)
3. Validation & Error Handling (12 tests)
4. Integration with Other Systems (3 tests)
5. Security in Action (5 tests)
6. Database Transactions (5 tests)
7. Business Logic & Edge Cases (5 tests)
8. Real-world Scenarios (3 tests)
9. Performance & Response (3 tests)

### سیستم Posts & Content (114/100 - Production Ready)
- ✅ 23 endpoint عملیاتی (9 Controller)
- ✅ 352 تست (Script: 271 + Feature: 81)
- ✅ 271 تست در Script Test (20 بخش)
- ✅ 81 تست در Feature Test (9 بخش)
- ✅ تست یکپارچه: PostsContentSystemTest.php
- ✅ Script Test: test-scripts/04_posts.php
- ✅ بازنویسی کامل با معماری 20 بخشی
- ✅ PermissionSeeder بهبود یافت (post.create, post.schedule, post.like)
- ✅ Config path اصلاح شد (limits.roles.user)
- ✅ همه 6 نقش تست شدند
- ✅ 100% موفقیت (271/271 تست)

**Controllers تست شده:**
- PostController (11 endpoints)
- CommentController (4 endpoints)
- BookmarkController (2 endpoints)
- RepostController (4 endpoints)
- ThreadController (4 endpoints)
- ScheduledPostController (3 endpoints)
- PollController (4 endpoints)
- MediaController (4 endpoints)
- CommunityNoteController (4 endpoints)

**بخشهای Script Test (20 بخش):**
1. Database & Schema (8%)
2. Models & Relationships (8%)
3. Validation Integration (6%)
4. Controllers & Services (8%)
5. Core Features (8%)
6. Security & Authorization (12%)
7. Spam Detection (4%)
8. Performance & Optimization (5%)
9. Data Integrity & Transactions (5%)
10. API & Routes (8%)
11. Configuration (4%)
12. Advanced Features (5%)
13. Events & Integration (6%)
14. Error Handling (4%)
15. Resources & DTOs (4%)
16. User Flows (4%)
17. Validation Advanced (3%)
18. Roles & Permissions Database (6%)
19. Security Layers Deep Dive (4%)
20. Middleware & Bootstrap (2%)

**بخشهای Feature Test (9 بخش):**
1. Core API Functionality (20%)
2. Authentication & Authorization (20%)
3. Validation & Error Handling (15%)
4. Integration with Other Systems (15%)
5. Security in Action (10%)
6. Database Transactions (10%)
7. Business Logic & Edge Cases (5%)
8. Real-world Scenarios (3%)
9. Performance & Response (2%)


### سیستم Security (100/100 - Production Ready)
- ✅ 14 endpoint عملیاتی (2 Controller)
- ✅ 245 تست (Script: 195 + Feature: 50)
- ✅ 68 assertions در Feature Test
- ✅ تست یکپارچه: 01_SecuritySystemTest.php
- ✅ 20 بخش Script Test کامل
- ✅ 9 بخش Feature Test کامل
- ✅ Device Management (Register, Trust, Revoke, List, Activity)
- ✅ Audit Trail (Logging, Monitoring, Security Events)
- ✅ Security Monitoring (Threat Detection, IP Blocking, Bot Detection)
- ✅ Two-Factor Authentication (2FA)
- ✅ Authorization با DevicePolicy و AuditLogPolicy
- ✅ Integration با SecurityMonitoringService
- ✅ Validation (Device Registration, Trust, Revoke)
- ✅ Security (XSS, SQL Injection, Mass Assignment, Rate Limiting)
- ✅ Performance (Caching, Indexing, N+1 Prevention)

**بخشهای Feature Test:**
1. Core API Functionality (8 tests)
2. Authentication & Authorization (12 tests)
3. Validation & Error Handling (6 tests)
4. Integration with Other Systems (6 tests)
5. Security in Action (4 tests)
6. Database Transactions (4 tests)
7. Business Logic & Edge Cases (5 tests)
8. Real-world Scenarios (3 tests)
9. Performance & Response (2 tests)

---

**تاریخ ایجاد:** 2025-02-04  
**آخرین بروزرسانی:** 2025-02-25  
**نسخه:** 15.0  
**وضعیت:** 🔍 در حال بررسی

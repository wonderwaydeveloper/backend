# لیست کامل سیستمهای بکاند

## 1. Security 🛡️

### Controllers
- `AuditController`

### Features
- **Security Events**
  - GET `/auth/security/events` - رویدادهای امنیتی

- **Audit Logs**
  - GET `/auth/audit/my-activity` - فعالیتهای من
  - GET `/auth/audit/anomalies` - تشخیص ناهنجاری
  - GET `/auth/audit/security-events` - رویدادهای امنیتی
  - GET `/auth/audit/high-risk` - فعالیتهای پرخطر
  - GET `/auth/audit/statistics` - آمار audit

---

## 2. Device Management 📱

### Controller
- `DeviceController`

### Features
- POST `/devices/register` - ثبت دستگاه
- DELETE `/devices/{token}` - حذف دستگاه
- POST `/devices/advanced/register` - ثبت پیشرفته
- GET `/devices/list` - لیست دستگاهها
- GET `/devices/{device}/activity` - فعالیت دستگاه
- POST `/devices/{device}/trust` - اعتماد به دستگاه
- DELETE `/devices/{device}/revoke` - لغو دستگاه
- POST `/devices/revoke-all` - لغو همه
- GET `/devices/security-check` - بررسی امنیتی

---

## 3. Authentication 🔐

### Controllers
- `UnifiedAuthController`
- `PasswordResetController`
- `SocialAuthController`

### Features
- **Login/Logout**
  - POST `/auth/login` - ورود با email/username/phone
  - POST `/auth/logout` - خروج از حساب
  - POST `/auth/logout-all` - خروج از تمام دستگاهها
  - GET `/auth/me` - اطلاعات کاربر جاری

- **Session Management**
  - GET `/auth/sessions` - لیست sessionهای فعال
  - DELETE `/auth/sessions/{token_id}` - حذف session خاص

- **Multi-step Registration**
  - POST `/auth/register/step1` - مرحله 1: نام و تاریخ تولد
  - POST `/auth/register/step2` - مرحله 2: تایید کد
  - POST `/auth/register/step3` - مرحله 3: username و password
  - POST `/auth/register/resend-code` - ارسال مجدد کد
  - POST `/auth/register/check-username` - بررسی username

- **Email Verification**
  - POST `/auth/email/verify` - تایید ایمیل
  - POST `/auth/email/resend` - ارسال مجدد کد
  - GET `/auth/email/status` - وضعیت تایید ایمیل

- **Phone Authentication**
  - POST `/auth/phone/login/send-code` - ارسال کد ورود
  - POST `/auth/phone/login/verify-code` - تایید کد ورود
  - POST `/auth/phone/login/resend-code` - ارسال مجدد کد

- **Password Management**
  - POST `/auth/password/forgot` - فراموشی رمز
  - POST `/auth/password/verify-code` - تایید کد بازیابی
  - POST `/auth/password/resend` - ارسال مجدد کد
  - POST `/auth/password/reset` - تنظیم رمز جدید
  - POST `/auth/password/change` - تغییر رمز

- **Two-Factor Authentication**
  - POST `/auth/2fa/enable` - فعالسازی 2FA
  - POST `/auth/2fa/verify` - تایید 2FA
  - POST `/auth/2fa/disable` - غیرفعالسازی 2FA

- **Device Verification**
  - POST `/auth/verify-device` - تایید دستگاه
  - POST `/auth/resend-device-code` - ارسال مجدد کد دستگاه

- **Age Verification**
  - POST `/auth/complete-age-verification` - تایید سن

- **Social Authentication**
  - GET `/auth/social/{provider}` - ورود با Google
  - GET `/auth/social/{provider}/callback` - callback

---

## 4. Posts & Content 📝

### Controllers
- `PostController`
- `CommentController`
- `BookmarkController`
- `RepostController`
- `ThreadController`
- `ScheduledPostController`
- `PollController`
- `MediaController`
- `CommunityNoteController`

### Features
- **Post Management**
  - POST `/posts` - ایجاد پست
  - GET `/posts` - لیست پستها
  - GET `/posts/{post}` - نمایش پست
  - PUT `/posts/{post}` - ویرایش پست
  - DELETE `/posts/{post}` - حذف پست
  - GET `/posts/{post}/edit-history` - تاریخچه ویرایش
  - POST `/posts/{post}/publish` - انتشار draft
  - GET `/timeline` - تایملاین
  - GET `/drafts` - پستهای draft

- **Post Interactions**
  - POST `/posts/{post}/like` - لایک
  - DELETE `/posts/{post}/like` - حذف لایک
  - GET `/posts/{post}/likes` - لیست لایکها
  - POST `/posts/{post}/quote` - quote post
  - GET `/posts/{post}/quotes` - لیست quoteها

- **Threads**
  - POST `/threads` - ایجاد thread
  - GET `/threads/{post}` - نمایش thread
  - POST `/threads/{post}/add` - اضافه به thread
  - GET `/threads/{post}/stats` - آمار thread

- **Scheduled Posts**
  - POST `/scheduled-posts` - زمانبندی پست
  - GET `/scheduled-posts` - لیست پستهای زمانبندی شده
  - DELETE `/scheduled-posts/{scheduledPost}` - حذف

---

## 5. Comments 💬

### Controller
- `CommentController`

### Features
- GET `/posts/{post}/comments` - لیست کامنتها
- POST `/posts/{post}/comments` - ایجاد کامنت
- DELETE `/comments/{comment}` - حذف کامنت
- POST `/comments/{comment}/like` - لایک کامنت

---

## 6. Social Features 👥

### Controllers
- `ProfileController`
- `FollowController`
- `FollowRequestController`

### Features
- **Follow System**
  - POST `/users/{user}/follow` - فالو کردن
  - POST `/users/{user}/unfollow` - آنفالو کردن
  - GET `/users/{user}/followers` - لیست فالوورها
  - GET `/users/{user}/following` - لیست فالوینگها

- **Follow Requests**
  - POST `/users/{user}/follow-request` - درخواست فالو
  - GET `/follow-requests` - لیست درخواستها
  - POST `/follow-requests/{followRequest}/accept` - قبول
  - POST `/follow-requests/{followRequest}/reject` - رد

- **Block/Mute**
  - POST `/users/{user}/block` - بلاک کردن
  - POST `/users/{user}/unblock` - حذف بلاک
  - POST `/users/{user}/mute` - میوت کردن
  - POST `/users/{user}/unmute` - حذف میوت
  - GET `/blocked` - لیست بلاک شدهها
  - GET `/muted` - لیست میوت شدهها

---

## 7. Profile & Account 👤

### Controller
- `ProfileController`

### Features
- **Profile**
  - GET `/users/{user}` - نمایش پروفایل
  - GET `/users/{user}/posts` - پستهای کاربر
  - GET `/users/{user}/media` - مدیای کاربر
  - PUT `/profile` - ویرایش پروفایل
  - PUT `/profile/privacy` - تنظیمات حریم خصوصی

- **Settings**
  - GET `/settings/privacy` - دریافت تنظیمات
  - PUT `/settings/privacy` - ویرایش تنظیمات

- **Account Management**
  - GET `/account/export-data` - دریافت دادهها
  - POST `/account/delete-account` - حذف حساب

---

## 8. Search & Discovery 🔍

### Controllers
- `SearchController`
- `SuggestionController`
- `TrendingController`

### Features
- **Search**
  - GET `/search/users` - جستجوی کاربران
  - GET `/search/posts` - جستجوی پستها
  - GET `/search/hashtags` - جستجوی هشتگها
  - GET `/search/all` - جستجوی همه
  - GET `/search/advanced` - جستجوی پیشرفته
  - GET `/search/suggestions` - پیشنهادات

- **Suggestions**
  - GET `/suggestions/users` - پیشنهاد کاربران

- **Trending**
  - GET `/trending/hashtags` - هشتگهای ترند
  - GET `/trending/posts` - پستهای ترند
  - GET `/trending/users` - کاربران ترند
  - GET `/trending/personalized` - ترند شخصیسازی شده
  - GET `/trending/velocity/{type}/{id}` - سرعت ترند
  - GET `/trending/all` - همه ترندها
  - GET `/trending/stats` - آمار ترند
  - POST `/trending/refresh` - بروزرسانی ترند

---

## 9. Messaging 💌

### Controller
- `MessageController`

### Features
- GET `/messages/conversations` - لیست مکالمات
- GET `/messages/users/{user}` - پیامهای با کاربر
- POST `/messages/users/{user}` - ارسال پیام
- POST `/messages/users/{user}/typing` - در حال تایپ
- POST `/messages/{message}/read` - خوانده شده
- GET `/messages/unread-count` - تعداد خوانده نشده

---

## 10. Communities 🏘️

### Controllers
- `CommunityController`
- `CommunityNoteController`

### Features
- **Community Management**
  - GET `/communities` - لیست کامیونیتیها
  - POST `/communities` - ایجاد کامیونیتی
  - GET `/communities/{community}` - نمایش
  - PUT `/communities/{community}` - ویرایش
  - DELETE `/communities/{community}` - حذف
  - POST `/communities/{community}/join` - عضویت
  - POST `/communities/{community}/leave` - خروج
  - GET `/communities/{community}/posts` - پستها
  - GET `/communities/{community}/members` - اعضا
  - GET `/communities/{community}/join-requests` - درخواستها
  - POST `/communities/{community}/join-requests/{request}/approve` - تایید
  - POST `/communities/{community}/join-requests/{request}/reject` - رد
  - DELETE `/communities/{community}/members/{member}` - حذف عضو
  - PUT `/communities/{community}/members/{member}/role` - تغییر نقش
  - POST `/communities/{community}/members/{member}/ban` - بن کردن
  - DELETE `/communities/{community}/bans/{member}` - حذف بن
  - POST `/communities/{community}/transfer-ownership` - انتقال مالکیت
  - POST `/communities/{community}/posts/{post}/pin` - پین پست
  - DELETE `/communities/{community}/posts/{post}/pin` - حذف پین
  - DELETE `/communities/{community}/posts/{post}` - حذف پست
  - POST `/communities/{community}/mute` - میوت کامیونیتی
  - DELETE `/communities/{community}/mute` - حذف میوت
  - GET `/communities/{community}/notification-settings` - تنظیمات نوتیفیکیشن
  - PUT `/communities/{community}/notification-settings` - ویرایش تنظیمات
  - POST `/communities/{community}/invites` - ایجاد دعوت
  - GET `/communities/{community}/invites` - لیست دعوتها
  - DELETE `/communities/{community}/invites/{invite}` - حذف دعوت
  - POST `/communities/join-with-code` - عضویت با کد

- **Community Notes**
  - GET `/posts/{post}/community-notes` - لیست نوتها
  - POST `/posts/{post}/community-notes` - ایجاد نوت
  - POST `/community-notes/{note}/vote` - رای دادن
  - GET `/community-notes/pending` - نوتهای در انتظار

---

## 11. Spaces (Audio Rooms) 🎙️

### Controller
- `SpaceController`

### Features
- GET `/spaces` - لیست اسپیسها
- POST `/spaces` - ایجاد اسپیس
- GET `/spaces/{space}` - نمایش
- POST `/spaces/{space}/join` - پیوستن
- POST `/spaces/{space}/leave` - خروج
- PUT `/spaces/{space}/participants/{participant}/role` - تغییر نقش
- POST `/spaces/{space}/end` - پایان اسپیس

---

## 12. Lists 📋

### Controller
- `ListController`

### Features
- GET `/lists` - لیست لیستها
- POST `/lists` - ایجاد لیست
- GET `/lists/discover` - کشف لیستها
- GET `/lists/{list}` - نمایش
- PUT `/lists/{list}` - ویرایش
- DELETE `/lists/{list}` - حذف
- POST `/lists/{list}/members` - اضافه عضو
- DELETE `/lists/{list}/members/{user}` - حذف عضو
- POST `/lists/{list}/subscribe` - سابسکرایب
- POST `/lists/{list}/unsubscribe` - آنسابسکرایب
- GET `/lists/{list}/posts` - پستهای لیست

---

## 13. Bookmarks & Reposts 🔖

### Controllers
- `BookmarkController`
- `RepostController`

### Features
- **Bookmarks**
  - GET `/bookmarks` - لیست بوکمارکها
  - POST `/posts/{post}/bookmark` - toggle بوکمارک

- **Reposts**
  - POST `/posts/{post}/repost` - ریپست
  - DELETE `/posts/{post}/repost` - حذف ریپست
  - GET `/posts/{post}/reposts` - لیست ریپستها
  - GET `/my-reposts` - ریپستهای من

---

## 14. Hashtags #️⃣

### Controller
- `HashtagController`

### Features
- GET `/hashtags/trending` - هشتگهای ترند
- GET `/hashtags/search` - جستجو
- GET `/hashtags/suggestions` - پیشنهادات
- GET `/hashtags/{hashtag:slug}` - نمایش هشتگ

---

## 15. Polls 📊

### Controller
- `PollController`

### Features
- POST `/polls` - ایجاد نظرسنجی
- POST `/polls/{poll}/vote/{option}` - رای دادن
- GET `/polls/{poll}/results` - نتایج

---

## 16. Mentions @

### Controller
- `MentionController`

### Features
- GET `/mentions` - لیست منشنها
- GET `/mentions/unread-count` - تعداد خوانده نشده
- POST `/mentions/{mention}/read` - خوانده شده

---

## 17. Notifications 🔔

### Controllers
- `NotificationController`
- `NotificationPreferenceController`
- `PushNotificationController`

### Features
- **Notifications**
  - GET `/notifications` - لیست نوتیفیکیشنها
  - GET `/notifications/unread` - خوانده نشدهها
  - GET `/notifications/unread-count` - تعداد
  - POST `/notifications/{notification}/read` - خوانده شده
  - POST `/notifications/mark-all-read` - همه خوانده شده

- **Preferences**
  - GET `/notifications/preferences` - تنظیمات
  - PUT `/notifications/preferences` - ویرایش
  - PUT `/notifications/preferences/{type}` - ویرایش نوع
  - PUT `/notifications/preferences/{type}/{category}` - ویرایش دسته

- **Push Notifications**
  - POST `/push/register` - ثبت دستگاه
  - DELETE `/push/unregister/{token}` - حذف دستگاه
  - POST `/push/test` - تست نوتیفیکیشن
  - GET `/push/devices` - لیست دستگاهها

---

## 18. Moderation & Reporting 🚨

### Controller
- `ModerationController`

### Features
- **User Reporting**
  - POST `/reports/post/{post}` - گزارش پست
  - POST `/reports/user/{user}` - گزارش کاربر
  - POST `/reports/comment/{comment}` - گزارش کامنت
  - GET `/reports/my-reports` - گزارشهای من

- **Admin Panel**
  - GET `/reports` - لیست گزارشها
  - GET `/reports/{report}` - نمایش گزارش
  - PATCH `/reports/{report}/status` - تغییر وضعیت
  - POST `/reports/{report}/action` - اقدام
  - GET `/reports/stats/overview` - آمار

---

## 19. Media Management 🖼️

### Controller
- `MediaController`

### Features
- GET `/media` - لیست مدیا
- GET `/media/{media}` - نمایش مدیا
- POST `/media/upload/image` - آپلود تصویر
- POST `/media/upload/video` - آپلود ویدیو
- POST `/media/upload/document` - آپلود سند
- DELETE `/media/{media}` - حذف مدیا
- GET `/media/{media}/status` - وضعیت پردازش

---

## 20. Moments ⭐

### Controller
- `MomentController`

### Features
- GET `/moments` - لیست مومنتها
- POST `/moments` - ایجاد مومنت
- GET `/moments/featured` - مومنتهای ویژه
- GET `/moments/my-moments` - مومنتهای من
- GET `/moments/{moment}` - نمایش
- PUT `/moments/{moment}` - ویرایش
- DELETE `/moments/{moment}` - حذف
- POST `/moments/{moment}/posts` - اضافه پست
- DELETE `/moments/{moment}/posts/{post}` - حذف پست

---

## 21. Analytics 📈

### Controllers
- `AnalyticsController`
- `ConversionController`

### Features
- **Analytics**
  - GET `/analytics/user` - آنالیتیکس کاربر
  - GET `/analytics/posts/{post}` - آنالیتیکس پست
  - POST `/analytics/track` - ثبت رویداد

- **Conversion Tracking**
  - POST `/conversions/track` - ثبت تبدیل
  - GET `/conversions/funnel` - قیف فروش
  - GET `/conversions/by-source` - بر اساس منبع
  - GET `/conversions/user-journey` - سفر کاربر
  - GET `/conversions/cohort-analysis` - تحلیل cohort

---

## 22. A/B Testing 🧪

### Controller
- `ABTestController`

### Features
- GET `/ab-tests` - لیست تستها
- POST `/ab-tests` - ایجاد تست
- GET `/ab-tests/{id}` - نمایش
- POST `/ab-tests/{id}/start` - شروع
- POST `/ab-tests/{id}/stop` - توقف
- POST `/ab-tests/assign` - اختصاص کاربر
- POST `/ab-tests/track` - ثبت رویداد

---

## 23. Monetization 💰

### Controllers
- `AdvertisementController`
- `CreatorFundController`
- `PremiumController`

### Features
- **Advertisements**
  - POST `/monetization/ads` - ایجاد تبلیغ
  - GET `/monetization/ads/targeted` - تبلیغات هدفمند
  - POST `/monetization/ads/{adId}/click` - ثبت کلیک
  - GET `/monetization/ads/analytics` - آنالیتیکس
  - POST `/monetization/ads/{adId}/pause` - توقف
  - POST `/monetization/ads/{adId}/resume` - ادامه

- **Creator Fund**
  - GET `/monetization/creator-fund/analytics` - آنالیتیکس
  - POST `/monetization/creator-fund/calculate-earnings` - محاسبه درآمد
  - GET `/monetization/creator-fund/earnings-history` - تاریخچه
  - POST `/monetization/creator-fund/request-payout` - درخواست پرداخت

- **Premium**
  - GET `/monetization/premium/plans` - پلنها
  - POST `/monetization/premium/subscribe` - اشتراک
  - POST `/monetization/premium/cancel` - لغو
  - GET `/monetization/premium/status` - وضعیت

---

## 24. Performance & Monitoring ⚡

### Controllers
- `PerformanceController`
- `PerformanceDashboardController`
- `FinalPerformanceController`
- `MonitoringController`
- `AutoScalingController`

### Features
- **Performance**
  - GET `/performance/dashboard` - داشبورد
  - GET `/performance/timeline/optimized` - تایملاین بهینه
  - POST `/performance/cache/warmup` - گرم کردن کش
  - DELETE `/performance/cache/clear` - پاک کردن کش

- **Optimized**
  - GET `/optimized/timeline` - تایملاین بهینه

- **Final Performance**
  - GET `/final-performance/system-status` - وضعیت سیستم

- **Monitoring**
  - GET `/monitoring/dashboard` - داشبورد
  - GET `/monitoring/cache` - مانیتور کش
  - GET `/monitoring/queue` - مانیتور صف

- **Auto-scaling**
  - GET `/auto-scaling/status` - وضعیت
  - GET `/auto-scaling/metrics` - متریکها
  - POST `/auto-scaling/force-scale` - اجبار scale
  - GET `/auto-scaling/predict` - پیشبینی

---

## 25. Real-time Features ⚡

### Controllers
- `OnlineStatusController`
- `TimelineController`

### Features
- POST `/realtime/status` - بروزرسانی وضعیت
- GET `/realtime/online-users` - کاربران آنلاین
- GET `/realtime/timeline` - تایملاین زنده
- GET `/realtime/posts/{post}` - بروزرسانی پست

---

## 26. Subscriptions 💳

### Controller
- `SubscriptionController`

### Features
- GET `/subscription/plans` - پلنهای اشتراک
- GET `/subscription/current` - اشتراک فعلی
- POST `/subscription/subscribe` - اشتراک
- POST `/subscription/cancel` - لغو
- GET `/subscription/history` - تاریخچه

---

## آمار کلی

- **تعداد کل Controllers**: 43
- **تعداد کل Endpoints**: 278
- **تعداد سیستمهای اصلی**: 26
- **نوع Authentication**: Sanctum (Token-based)
- **Real-time**: WebSocket/Broadcasting
- **Database**: MySQL
- **Cache**: Redis
- **Queue**: Redis
- **Search**: MeiliSearch
- **File Storage**: Local/S3

---

## سیستمهای حذف شده ❌

- ~~GIF Integration~~ (حذف شده)
- ~~GraphQL~~ (حذف شده)
- ~~Organization Management~~ (حذف شده)

---

## نکات امنیتی

- ✅ Rate Limiting روی تمام endpoints
- ✅ CSRF Protection
- ✅ XSS Prevention
- ✅ SQL Injection Prevention
- ✅ Two-Factor Authentication
- ✅ Device Verification
- ✅ Audit Logging
- ✅ Security Monitoring
- ✅ Spam Detection
- ✅ Content Moderation

---

تاریخ بروزرسانی: 2025-02-23
نسخه: 8.0.0

# Changelog

تمام تغییرات مهم این پروژه در این فایل مستند میشود.

فرمت بر اساس [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) است و این پروژه از [Semantic Versioning](https://semver.org/spec/v2.0.0.html) پیروی میکند.

## [Unreleased]

### Added
- بهبود سیستم جستجو با Elasticsearch
- پشتیبانی از GraphQL subscriptions
- سیستم recommendation پیشرفته
- API versioning

### Changed
- بهینهسازی performance queries
- بروزرسانی dependencies

### Fixed
- رفع مشکل memory leak در WebSocket
- بهبود error handling

## [3.2.0] - 2024-01-15

### Added
- **Community Notes**: سیستم یادداشتهای جامعه برای تأیید اطلاعات
- **Advanced Analytics**: آنالیتیکس پیشرفته برای کاربران و محتوا
- **A/B Testing Framework**: سیستم تست A/B برای بهینهسازی
- **Conversion Tracking**: ردیابی تبدیل و تحلیل funnel
- **Auto-scaling**: مقیاسپذیری خودکار بر اساس بار سیستم

### Changed
- بهبود performance تایم لاین تا 40%
- بهینهسازی queries پایگاه داده
- بروزرسانی Laravel به نسخه 12.x
- بهبود UI/UX پنل مدیریت

### Fixed
- رفع مشکل duplicate notifications
- بهبود stability WebSocket connections
- رفع memory leaks در queue workers
- بهبود error handling در media upload

### Security
- پیادهسازی Advanced Threat Protection
- بهبود WAF rules
- اضافه شدن Behavioral Analytics
- تقویت JWT security

## [3.1.0] - 2024-01-01

### Added
- **Spaces**: اتاقهای صوتی برای گفتگوی زنده
- **Lists**: ایجاد و مدیریت لیستهای کاربران
- **Moments**: مجموعهسازی پستها در قالب moments
- **Enhanced Threads**: بهبود سیستم thread ها
- **Poll System**: نظرسنجی در پستها
- **Webhook Support**: پشتیبانی از webhooks برای developers

### Changed
- بازطراحی کامل API responses
- بهبود real-time messaging performance
- بهینهسازی image processing
- بروزرسانی notification system

### Fixed
- رفع مشکل timezone در scheduled posts
- بهبود video upload stability
- رفع مشکلات pagination در timeline
- بهبود search accuracy

### Deprecated
- API v1 endpoints (حذف در v4.0.0)
- Legacy authentication methods

## [3.0.0] - 2023-12-01

### Added
- **Monetization System**: سیستم کامل درآمدزایی
  - Advertisement platform
  - Creator Fund
  - Premium subscriptions
- **Live Streaming**: پخش زنده ویدیو
- **Advanced Security**: سیستم امنیتی پیشرفته
  - Multi-factor authentication
  - Advanced rate limiting
  - Intrusion detection system
- **Performance Monitoring**: مانیتورینگ کامل عملکرد
- **GraphQL API**: پشتیبانی کامل از GraphQL

### Changed
- **BREAKING**: تغییر ساختار API responses
- **BREAKING**: بروزرسانی authentication system
- مهاجرت به Laravel 11
- بازطراحی database schema
- بهبود caching strategy

### Removed
- **BREAKING**: حذف API v1
- حذف legacy endpoints
- حذف deprecated features

### Security
- پیادهسازی Zero Trust Architecture
- اضافه شدن End-to-End Encryption برای پیامها
- بهبود password security policies
- پیادهسازی GDPR compliance

## [2.5.0] - 2023-11-15

### Added
- **Video Upload**: آپلود و پردازش ویدیو
- **GIF Support**: پشتیبانی از GIF در پستها
- **Advanced Search**: جستجوی پیشرفته با فیلترها
- **Trending System**: سیستم محتوای ترند
- **User Suggestions**: پیشنهاد کاربران هوشمند

### Changed
- بهبود media processing pipeline
- بهینهسازی timeline algorithm
- بروزرسانی UI components
- بهبود mobile responsiveness

### Fixed
- رفع مشکل duplicate posts در timeline
- بهبود image compression quality
- رفع مشکلات real-time notifications
- بهبود error messages

## [2.4.0] - 2023-11-01

### Added
- **Parental Controls**: کنترل والدین برای کاربران زیر 18 سال
- **Content Moderation**: سیستم مدیریت محتوا
- **Report System**: گزارش محتوای نامناسب
- **Push Notifications**: اعلانات push برای موبایل
- **Mention System**: منشن کردن کاربران

### Changed
- بهبود notification preferences
- بهینهسازی database queries
- بروزرسانی security headers
- بهبود API documentation

### Fixed
- رفع مشکل character encoding
- بهبود file upload validation
- رفع مشکلات timezone
- بهبود error logging

## [2.3.0] - 2023-10-15

### Added
- **Real-time Messaging**: پیامرسانی آنی
- **Online Status**: نمایش وضعیت آنلاین کاربران
- **Message Reactions**: واکنش به پیامها
- **Typing Indicators**: نشانگر تایپ کردن
- **Message Search**: جستجو در پیامها

### Changed
- بهبود WebSocket performance
- بهینهسازی message delivery
- بروزرسانی Redis configuration
- بهبود mobile app integration

### Fixed
- رفع مشکل message ordering
- بهبود connection stability
- رفع memory leaks در WebSocket
- بهبود message encryption

## [2.2.0] - 2023-10-01

### Added
- **Quote Posts**: نقل قول از پستها
- **Post Editing**: ویرایش پستها
- **Edit History**: تاریخچه ویرایشات
- **Scheduled Posts**: زمانبندی انتشار پستها
- **Draft System**: ذخیره پیشنویس

### Changed
- بهبود post creation flow
- بهینهسازی image storage
- بروزرسانی text editor
- بهبود mobile experience

### Fixed
- رفع مشکل post duplication
- بهبود image upload reliability
- رفع مشکلات formatting
- بهبود validation messages

## [2.1.0] - 2023-09-15

### Added
- **Bookmarks**: نشانهگذاری پستها
- **Repost System**: بازنشر پستها
- **Advanced Timeline**: تایم لاین پیشرفته
- **Hashtag Trending**: هشتگهای ترند
- **User Analytics**: آنالیتیکس کاربران

### Changed
- بهبود timeline algorithm
- بهینهسازی hashtag processing
- بروزرسانی caching strategy
- بهبود API performance

### Fixed
- رفع مشکل hashtag detection
- بهبود timeline loading speed
- رفع مشکلات pagination
- بهبود search functionality

## [2.0.0] - 2023-09-01

### Added
- **Follow System**: سیستم فالو کردن کاربران
- **Private Accounts**: حسابهای خصوصی
- **Follow Requests**: درخواست فالو
- **User Profiles**: پروفایل کاربران
- **Profile Customization**: شخصیسازی پروفایل

### Changed
- **BREAKING**: تغییر ساختار user model
- **BREAKING**: بروزرسانی API endpoints
- بهبود user experience
- بازطراحی database relationships
- بهینهسازی queries

### Removed
- **BREAKING**: حذف legacy user fields
- حذف deprecated endpoints

### Security
- بهبود privacy controls
- اضافه شدن account visibility settings
- تقویت user data protection

## [1.5.0] - 2023-08-15

### Added
- **Comments System**: سیستم کامنت
- **Nested Comments**: کامنتهای تودرتو
- **Comment Likes**: لایک کامنتها
- **Comment Moderation**: مدیریت کامنتها
- **Comment Notifications**: اعلانات کامنت

### Changed
- بهبود notification system
- بهینهسازی comment loading
- بروزرسانی UI components
- بهبود mobile responsiveness

### Fixed
- رفع مشکل comment ordering
- بهبود comment validation
- رفع مشکلات real-time updates
- بهبود performance

## [1.4.0] - 2023-08-01

### Added
- **Like System**: سیستم لایک پستها
- **Like Notifications**: اعلانات لایک
- **Like Analytics**: آنالیتیکس لایکها
- **Unlike Functionality**: حذف لایک
- **Like History**: تاریخچه لایکها

### Changed
- بهبود interaction tracking
- بهینهسازی like counting
- بروزرسانی notification system
- بهبود API responses

### Fixed
- رفع مشکل double likes
- بهبود like counter accuracy
- رفع مشکلات real-time updates
- بهبود database consistency

## [1.3.0] - 2023-07-15

### Added
- **Media Upload**: آپلود تصاویر
- **Image Processing**: پردازش تصاویر
- **Thumbnail Generation**: تولید thumbnail
- **Image Optimization**: بهینهسازی تصاویر
- **CDN Integration**: یکپارچگی با CDN

### Changed
- بهبود file handling
- بهینهسازی storage usage
- بروزرسانی media pipeline
- بهبود upload performance

### Fixed
- رفع مشکل file corruption
- بهبود upload reliability
- رفع مشکلات image quality
- بهبود error handling

## [1.2.0] - 2023-07-01

### Added
- **Hashtag System**: سیستم هشتگ
- **Hashtag Detection**: تشخیص خودکار هشتگ
- **Hashtag Search**: جستجو بر اساس هشتگ
- **Trending Hashtags**: هشتگهای پرطرفدار
- **Hashtag Analytics**: آنالیتیکس هشتگها

### Changed
- بهبود content parsing
- بهینهسازی search functionality
- بروزرسانی indexing system
- بهبود performance

### Fixed
- رفع مشکل hashtag parsing
- بهبود search accuracy
- رفع مشکلات Unicode
- بهبود database indexing

## [1.1.0] - 2023-06-15

### Added
- **Post Creation**: ایجاد پست
- **Post Display**: نمایش پستها
- **Post Timeline**: تایم لاین پستها
- **Post Validation**: اعتبارسنجی پست
- **Post Formatting**: فرمت بندی محتوا

### Changed
- بهبود content management
- بهینهسازی timeline loading
- بروزرسانی validation rules
- بهبود user interface

### Fixed
- رفع مشکل content encoding
- بهبود timeline performance
- رفع مشکلات validation
- بهبود error messages

## [1.0.0] - 2023-06-01

### Added
- **User Registration**: ثبتنام کاربران
- **User Authentication**: احراز هویت
- **JWT Integration**: یکپارچگی JWT
- **Password Security**: امنیت رمز عبور
- **Email Verification**: تأیید ایمیل
- **Basic API Structure**: ساختار اولیه API
- **Database Schema**: طراحی پایگاه داده
- **Security Middleware**: میدلور امنیتی
- **Rate Limiting**: محدودیت نرخ درخواست
- **Error Handling**: مدیریت خطا
- **Logging System**: سیستم لاگگیری
- **Testing Framework**: چارچوب تست
- **Documentation**: مستندات اولیه

### Security
- پیادهسازی HTTPS
- اضافه شدن CSRF protection
- پیادهسازی XSS prevention
- تنظیم security headers
- پیادهسازی input validation

---

## نوع تغییرات

- **Added**: ویژگیهای جدید
- **Changed**: تغییرات در ویژگیهای موجود
- **Deprecated**: ویژگیهایی که به زودی حذف میشوند
- **Removed**: ویژگیهای حذف شده
- **Fixed**: رفع باگها
- **Security**: بهبودهای امنیتی

## لینکها

- [Unreleased]: https://github.com/wonderway/backend/compare/v3.2.0...HEAD
- [3.2.0]: https://github.com/wonderway/backend/compare/v3.1.0...v3.2.0
- [3.1.0]: https://github.com/wonderway/backend/compare/v3.0.0...v3.1.0
- [3.0.0]: https://github.com/wonderway/backend/compare/v2.5.0...v3.0.0
- [2.5.0]: https://github.com/wonderway/backend/compare/v2.4.0...v2.5.0
- [2.4.0]: https://github.com/wonderway/backend/compare/v2.3.0...v2.4.0
- [2.3.0]: https://github.com/wonderway/backend/compare/v2.2.0...v2.3.0
- [2.2.0]: https://github.com/wonderway/backend/compare/v2.1.0...v2.2.0
- [2.1.0]: https://github.com/wonderway/backend/compare/v2.0.0...v2.1.0
- [2.0.0]: https://github.com/wonderway/backend/compare/v1.5.0...v2.0.0
- [1.5.0]: https://github.com/wonderway/backend/compare/v1.4.0...v1.5.0
- [1.4.0]: https://github.com/wonderway/backend/compare/v1.3.0...v1.4.0
- [1.3.0]: https://github.com/wonderway/backend/compare/v1.2.0...v1.3.0
- [1.2.0]: https://github.com/wonderway/backend/compare/v1.1.0...v1.2.0
- [1.1.0]: https://github.com/wonderway/backend/compare/v1.0.0...v1.1.0
- [1.0.0]: https://github.com/wonderway/backend/releases/tag/v1.0.0
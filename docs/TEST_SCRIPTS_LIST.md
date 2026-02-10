# 📋 لیست اسکریپتهای تست به تفکیک سیستم

## ✅ سیستمهای تکمیل شده

### 1. Authentication & Security System
**تعداد فایل تست:** 1 فایل  
**تعداد تست کل:** 169 تست

| # | فایل | تعداد تست | توضیحات |
|---|------|-----------|---------|
| 1 | `test_authentication.php` | 169 | تست کامل سیستم (20 بخش) |

**بخشهای تست:**
- Core Services (12 tests)
- Controllers & Routes (8 tests)
- AuthService Methods (9 tests)
- Request Classes & Validation (8 tests)
- Middleware & Security (8 tests)
- Models & Database (8 tests)
- DTOs & Contracts (6 tests)
- Configuration & Services (8 tests)
- Events & Notifications (6 tests)
- Policies & Authorization (8 tests)
- Email Templates (6 tests)
- Security Features (10 tests)
- User Flows (8 tests)
- Error Handling (6 tests)
- Service Registration (6 tests)
- API Routes (8 tests)
- **Validation Rules Functional (10 tests)**
- **Password Security Functional (12 tests)**
- **Rate Limiting Functional (10 tests)**
- **2FA Flow Functional (12 tests)**

**اجرا:**
```bash
php test_authentication.php
```

---

### 2. Posts & Content System
**تعداد فایل تست:** 1 فایل  
**تعداد تست کل:** ~200 تست

| # | فایل | تعداد تست | توضیحات |
|---|------|-----------|---------|
| 1 | `test_posts_system.php` | ~200 | تست جامع (20 بخش) |

**اجرا:**
```bash
php test_posts_system.php
```

---

### 3. Users & Profile System
**تعداد فایل تست:** 3 فایل  
**تعداد تست کل:** 59+ تست

| # | فایل | تعداد تست | توضیحات |
|---|------|-----------|---------|
| 1 | `test_users_profile_01_core.php` | 59 | تست Core Functionality |
| 2 | `test_users_profile_02_security.php` | ~30 | تست Security Features |
| 3 | `test_users_profile_03_standards.php` | ~40 | تست Twitter Standards |

**اجرا:**
```bash
php test_users_profile_01_core.php
php test_users_profile_02_security.php
php test_users_profile_03_standards.php
```

---

### 4. Report System
**تعداد فایل تست:** 1 فایل  
**تعداد تست کل:** 23 تست

| # | فایل | تعداد تست | توضیحات |
|---|------|-----------|---------|
| 1 | `test_report.php` | 23 | تست کامل Report System |

**اجرا:**
```bash
php test_report.php
```

---

### 5. Integration Tests
**تعداد فایل تست:** 1 فایل  
**تعداد تست کل:** 30 تست

| # | فایل | تعداد تست | توضیحات |
|---|------|-----------|---------|
| 1 | `test_final_integration.php` | 30 | تست یکپارچگی سیستمها |

**اجرا:**
```bash
php test_final_integration.php
```

---

### 6. Twitter Compliance
**تعداد فایل تست:** 1 فایل  
**تعداد تست کل:** ~50 تست

| # | فایل | تعداد تست | توضیحات |
|---|------|-----------|---------|
| 1 | `test_twitter_compliance.php` | ~50 | تست استانداردهای Twitter |

**اجرا:**
```bash
php test_twitter_compliance.php
```

---

## 📊 خلاصه آمار

| سیستم | فایلهای تست | تعداد تست | وضعیت |
|-------|-------------|-----------|--------|
| Authentication | 1 | 169 | ✅ |
| Posts & Content | 1 | ~200 | ✅ |
| Users & Profile | 3 | 59+ | ✅ |
| Block/Mute | - | 22 | ✅ |
| Report | 1 | 23 | ✅ |
| Integration | 1 | 30 | ✅ |
| Twitter Compliance | 1 | ~50 | ✅ |
| **جمع کل** | **8** | **~450** | **✅** |

---

## 🚀 اجرای همه تستها

```bash
# Authentication (169 tests)
php test_authentication.php

# Posts
php test_posts_system.php

# Users & Profile
php test_users_profile_01_core.php
php test_users_profile_02_security.php
php test_users_profile_03_standards.php

# Report
php test_report.php

# Integration
php test_final_integration.php

# Twitter Compliance
php test_twitter_compliance.php
```

---

## ⏳ سیستمهای بدون تست

### Comments System
- **وضعیت:** در حال توسعه
- **فایل تست:** هنوز ایجاد نشده
- **تست مورد نیاز:** ~40-50 تست

### Social Features (Follow System)
- **وضعیت:** بخشی در Users تست شده
- **فایل تست جداگانه:** نیاز به ایجاد
- **تست مورد نیاز:** ~30-40 تست

### Search & Discovery
- **وضعیت:** هنوز شروع نشده
- **فایل تست:** نیاز به ایجاد
- **تست مورد نیاز:** ~50-60 تست

### Messaging
- **وضعیت:** هنوز شروع نشده
- **فایل تست:** نیاز به ایجاد
- **تست مورد نیاز:** ~40-50 تست

### Notifications
- **وضعیت:** هنوز شروع نشده
- **فایل تست:** نیاز به ایجاد
- **تست مورد نیاز:** ~50-60 تست

---

**تاریخ بروزرسانی:** 2026-02-10  
**نسخه:** 3.0  
**تغییرات:** 
- ادغام کامل تستهای Authentication (169 تست)
- حذف فایلهای آرشیو
- بروزرسانی آمار کلی

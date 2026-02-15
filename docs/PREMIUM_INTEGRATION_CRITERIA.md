# 📐 معیارهای اصلاحات و هماهنگی سیستم Premium

**تاریخ:** 2026-02-15  
**نسخه:** 1.0

---

## 🎯 اصول کلی

### 1. یکپارچگی (Integration)
- هر تغییر باید با **تمام سیستمهای موجود** هماهنگ باشد
- نباید سیستمی **break** شود
- باید **backward compatible** باشد

### 2. تست‌پذیری (Testability)
- هر ویژگی باید **قابل تست** باشد
- باید **Unit Test** و **Feature Test** داشته باشد

### 3. امنیت (Security)
- نباید **bypass** شود
- باید در **Policy** و **Middleware** چک شود

### 4. عملکرد (Performance)
- نباید **query اضافی** ایجاد کند
- باید **cache** شود

---

## 📋 چکلیست بررسی قبل از هر فاز

### ✅ قبل از شروع
- [ ] تمام فایلهای مرتبط شناسایی شدند
- [ ] وابستگیها مشخص شدند
- [ ] تست‌های موجود شناسایی شدند
- [ ] نقاط شکست احتمالی پیدا شدند

### ✅ حین کار
- [ ] کد با استانداردهای Laravel مطابقت دارد
- [ ] Type hints استفاده شده
- [ ] Exception handling وجود دارد
- [ ] Transaction برای عملیات دیتابیس

### ✅ بعد از اتمام
- [ ] تست نوشته شد
- [ ] تست اجرا شد و موفق بود
- [ ] Documentation بهروز شد
- [ ] Migration اجرا شد (در صورت نیاز)

---

## 🔍 معیارهای بررسی دقیق

### 1️⃣ بررسی Database

#### چک کردن:
```bash
# جداول مرتبط
- users (is_premium, subscription_plan)
- premium_subscriptions (همه فیلدها)

# Relations
- User::premiumSubscriptions()
- User::activePremiumSubscription()
- PremiumSubscription::user()

# Indexes
- premium_subscriptions: (user_id, status)
- premium_subscriptions: (ends_at, status)
```

#### معیار موفقیت:
- ✅ تمام relations کار میکنند
- ✅ Indexes موجود هستند
- ✅ Foreign keys صحیح هستند

---

### 2️⃣ بررسی Models

#### چک کردن User Model:
```php
// Relations
✅ premiumSubscriptions() → hasMany
✅ activePremiumSubscription() → hasOne با شرط

// Methods
✅ isPremium() → bool
✅ hasFeature(string) → bool (باید اضافه شود)

// Attributes
✅ is_premium → boolean cast
✅ subscription_plan → string
```

#### چک کردن PremiumSubscription Model:
```php
// Relations
✅ user() → belongsTo

// Methods
✅ isActive() → bool
✅ isExpired() → bool
✅ cancel() → void

// Casts
✅ features → array
✅ starts_at, ends_at → datetime
```

#### معیار موفقیت:
- ✅ تمام methods تعریف شدهاند
- ✅ Relations دوطرفه هستند
- ✅ Casts صحیح هستند

---

### 3️⃣ بررسی Services

#### چک کردن PremiumService:
```php
✅ subscribe(User, array) → PremiumSubscription
   - ایجاد subscription
   - بهروز کردن User.is_premium
   - بهروز کردن User.subscription_plan
   - ارسال ایمیل (اختیاری)
   - Transaction

✅ cancel(PremiumSubscription) → void
   - لغو subscription
   - بهروز کردن User.is_premium = false
   - بهروز کردن User.subscription_plan = 'basic'
   - Transaction

✅ getStatus(User) → ?PremiumSubscription
   - فقط active subscriptions
   - چک کردن ends_at

✅ getPlans() → array
   - لیست پلنها با features
```

#### معیار موفقیت:
- ✅ تمام methods کامل هستند
- ✅ Transaction استفاده میشود
- ✅ Error handling وجود دارد

---

### 4️⃣ بررسی Controllers

#### چک کردن PremiumController:
```php
✅ getPlans() → JsonResponse
   - بدون auth

✅ subscribe(Request) → JsonResponse
   - Validation
   - Authorization (Policy)
   - Service call
   - Resource return

✅ cancel(PremiumSubscription) → JsonResponse
   - Authorization (Policy)
   - Service call

✅ getStatus() → JsonResponse
   - Authorization (Policy)
   - Service call
   - Resource return
```

#### معیار موفقیت:
- ✅ Authorization در همه جا
- ✅ Validation صحیح
- ✅ Resource برای response

---

### 5️⃣ بررسی Policies

#### چک کردن PremiumSubscriptionPolicy:
```php
✅ viewAny(User) → bool
✅ create(User) → bool
✅ cancel(User, PremiumSubscription) → bool
   - فقط صاحب subscription
```

#### معیار موفقیت:
- ✅ تمام actions تعریف شدهاند
- ✅ Authorization logic صحیح است

---

### 6️⃣ بررسی Routes

#### چک کردن api.php:
```php
✅ GET  /api/monetization/premium/plans
✅ POST /api/monetization/premium/subscribe
✅ POST /api/monetization/premium/cancel
✅ GET  /api/monetization/premium/status

// Middleware
✅ auth:sanctum
✅ security:api
```

#### معیار موفقیت:
- ✅ تمام routes تعریف شدهاند
- ✅ Middleware صحیح است
- ✅ Naming convention رعایت شده

---

### 7️⃣ بررسی Requests

#### چک کردن PremiumSubscriptionRequest:
```php
✅ rules() → array
   - plan: required|in:basic,premium,enterprise
   - billing_cycle: required|in:monthly,yearly
   - payment_method: required|string
   - transaction_id: required|string
   - price: required|numeric

✅ authorize() → bool
```

#### معیار موفقیت:
- ✅ Validation rules کامل
- ✅ Authorization logic صحیح

---

### 8️⃣ بررسی Resources

#### چک کردن PremiumResource:
```php
✅ toArray() → array
   - id
   - plan
   - price
   - billing_cycle
   - status
   - starts_at (ISO8601)
   - ends_at (ISO8601)
   - features
   - is_active
```

#### معیار موفقیت:
- ✅ تمام فیلدها موجود
- ✅ Date formatting صحیح
- ✅ Computed fields (is_active)

---

### 9️⃣ بررسی Middleware

#### چک کردن CheckPremium (باید ایجاد شود):
```php
✅ handle(Request, Closure, ?string) → Response
   - چک isPremium()
   - چک hasFeature() (اختیاری)
   - Return 403 در صورت عدم دسترسی
```

#### معیار موفقیت:
- ✅ Middleware ثبت شده در Kernel
- ✅ Error message واضح
- ✅ HTTP status code صحیح (403)

---

### 🔟 بررسی یکپارچگی با سیستمهای دیگر

#### سیستمهای تأثیرگذار:

##### 1. Post System
```php
// PostPolicy::create()
✅ چک محدودیت 10 پست/روز برای Free
✅ Premium: نامحدود

// PostPolicy::update()
✅ فقط Premium میتواند ویرایش کند
```

##### 2. Media System
```php
// MediaController::uploadImage()
✅ Free: حداکثر 5 تصویر/پست
✅ Premium: نامحدود

// VideoController::upload()
✅ Free: حداکثر 2 دقیقه
✅ Premium: 60 دقیقه
✅ Premium+: 180 دقیقه
```

##### 3. Analytics System
```php
// AnalyticsController::userAnalytics()
✅ Free: آمار پایه
✅ Premium+: آمار پیشرفته
```

##### 4. Advertisement System
```php
// PostController::timeline()
✅ Free: نمایش تبلیغات
✅ Premium: بدون تبلیغات (ad_free)
```

##### 5. API Access
```php
// Middleware: CheckApiAccess
✅ Enterprise: دسترسی به API
✅ سایرین: 403
```

#### معیار موفقیت:
- ✅ تمام سیستمها Premium را چک میکنند
- ✅ محدودیتها اعمال میشوند
- ✅ ویژگیها فعال هستند

---

## 🧪 معیارهای تست

### Unit Tests
```php
✅ PremiumServiceTest
   - test_subscribe_creates_subscription()
   - test_subscribe_updates_user_is_premium()
   - test_cancel_updates_user_is_premium()
   - test_get_status_returns_active_subscription()

✅ UserTest
   - test_is_premium_returns_true_with_active_subscription()
   - test_is_premium_returns_false_without_subscription()
   - test_has_feature_returns_true_for_valid_feature()
   - test_has_feature_returns_false_for_invalid_feature()

✅ PremiumSubscriptionTest
   - test_is_active_returns_true_for_active()
   - test_is_expired_returns_true_for_expired()
   - test_cancel_updates_status()
```

### Feature Tests
```php
✅ PremiumSubscriptionTest
   - test_user_can_subscribe_to_premium()
   - test_user_can_cancel_subscription()
   - test_user_can_view_subscription_status()
   - test_free_user_cannot_access_premium_features()
   - test_premium_user_can_access_premium_features()

✅ PostTest
   - test_free_user_limited_to_10_posts_per_day()
   - test_premium_user_unlimited_posts()
   - test_free_user_cannot_edit_posts()
   - test_premium_user_can_edit_posts()

✅ MediaTest
   - test_free_user_limited_to_5_images()
   - test_premium_user_unlimited_images()
   - test_free_user_limited_to_2_minute_videos()
   - test_premium_user_can_upload_60_minute_videos()
```

### Integration Tests
```php
✅ PremiumIntegrationTest
   - test_subscribe_flow_end_to_end()
   - test_cancel_flow_end_to_end()
   - test_expired_subscription_removes_premium_access()
   - test_premium_features_work_across_systems()
```

#### معیار موفقیت:
- ✅ تمام تستها pass میشوند
- ✅ Coverage > 80%
- ✅ Edge cases پوشش داده شدهاند

---

## 📊 معیارهای عملکرد

### Database Queries
```php
✅ isPremium() → حداکثر 1 query (با eager loading: 0)
✅ hasFeature() → حداکثر 1 query (با eager loading: 0)
✅ Timeline → حداکثر +1 query برای چک Premium
```

### Caching
```php
✅ User premium status → Cache 1 ساعت
✅ Subscription features → Cache 1 ساعت
✅ Plans list → Cache 24 ساعت
```

### Response Time
```php
✅ Subscribe endpoint → < 500ms
✅ Cancel endpoint → < 300ms
✅ Status endpoint → < 200ms
✅ Premium check → < 10ms
```

#### معیار موفقیت:
- ✅ N+1 query وجود ندارد
- ✅ Cache استفاده میشود
- ✅ Response time در محدوده است

---

## 🔒 معیارهای امنیتی

### Authorization
```php
✅ فقط صاحب subscription میتواند cancel کند
✅ Premium features فقط برای Premium users
✅ Policy در همه endpoints
```

### Validation
```php
✅ Plan validation (in:basic,premium,enterprise)
✅ Price validation (numeric, min:0)
✅ Transaction ID validation (unique)
```

### Rate Limiting
```php
✅ Subscribe: 5 requests/hour
✅ Cancel: 10 requests/hour
✅ Status: 60 requests/minute
```

#### معیار موفقیت:
- ✅ Authorization bypass نمیشود
- ✅ Validation کامل است
- ✅ Rate limiting فعال است

---

## 📝 چکلیست نهایی قبل از Production

### Code Quality
- [ ] PSR-12 رعایت شده
- [ ] Type hints استفاده شده
- [ ] DocBlocks نوشته شده
- [ ] No hardcoded values

### Testing
- [ ] Unit tests: 100% pass
- [ ] Feature tests: 100% pass
- [ ] Integration tests: 100% pass
- [ ] Coverage > 80%

### Documentation
- [ ] API documentation بهروز شد
- [ ] README بهروز شد
- [ ] CHANGELOG بهروز شد
- [ ] Migration guide نوشته شد

### Database
- [ ] Migrations اجرا شد
- [ ] Seeders اجرا شد
- [ ] Indexes ایجاد شد
- [ ] Backup گرفته شد

### Security
- [ ] Authorization تست شد
- [ ] Validation تست شد
- [ ] Rate limiting تست شد
- [ ] Security audit انجام شد

### Performance
- [ ] Query optimization انجام شد
- [ ] Caching پیادهسازی شد
- [ ] Load testing انجام شد
- [ ] Response time < target

### Integration
- [ ] تمام سیستمها تست شدند
- [ ] Backward compatibility تأیید شد
- [ ] Breaking changes مستند شد
- [ ] Migration path مشخص شد

---

## 🎯 معیار موفقیت کلی

### ✅ سیستم Premium کامل است اگر:

1. **عملیاتی**
   - Subscribe کار میکند
   - Cancel کار میکند
   - Status نمایش داده میشود
   - Features فعال هستند

2. **یکپارچه**
   - با Post System یکپارچه است
   - با Media System یکپارچه است
   - با Analytics یکپارچه است
   - با Advertisement یکپارچه است

3. **امن**
   - Authorization کار میکند
   - Validation کامل است
   - Rate limiting فعال است

4. **تست شده**
   - تمام تستها pass میشوند
   - Coverage > 80%
   - Edge cases پوشش داده شدهاند

5. **مستند**
   - API documentation کامل است
   - Code documentation کامل است
   - User guide موجود است

---

## 🚀 گام بعدی

**قبل از شروع فاز 1:**
1. این معیارها را مطالعه کنید
2. تمام فایلهای مرتبط را شناسایی کنید
3. تستهای موجود را اجرا کنید
4. Backup از database بگیرید

**آیا آماده شروع هستید؟**

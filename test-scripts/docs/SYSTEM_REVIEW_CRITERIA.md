# 📋 معیارهای بررسی سیستم

## 🎯 معیارهای Script Tests (20 بخش - 100 امتیاز)

### 1️⃣ Architecture & Code (20%)
- Controllers exist
- Services exist
- Models & Relationships
- DTOs/Resources (if needed)
- Repositories (if needed)

### 2️⃣ Database & Schema (15%)
- Tables exist
- Required columns
- Indexes (user_id, created_at, etc.)
- Foreign keys
- Constraints (NOT NULL, DEFAULT)

### 3️⃣ API & Routes (15%)
- All endpoints defined
- HTTP methods correct
- Route naming (RESTful)
- Middleware applied
- Route grouping

### 4️⃣ Security (20%)
- Authentication (auth:sanctum)
- Authorization (Policies)
- Permissions (Spatie) - تست همه 6 نقش: user, verified, premium, organization, moderator, admin
- Roles (Spatie) - تست همه 6 نقش: user, verified, premium, organization, moderator, admin
- XSS Protection
- SQL Injection Protection
- Mass Assignment Protection
- Rate Limiting
- CSRF Protection

### 5️⃣ Validation (10%)
- Request classes
- Custom rules (config-based)
- No hardcoded values
- Error messages

### 6️⃣ Business Logic (10%)
- Core features work
- Edge cases handled
- Error handling
- Transactions

### 7️⃣ Integration (5%)
- Block/Mute integrated
- Notifications integrated
- Events/Listeners
- Jobs/Queues (if needed)
- Cross-system relationships work
- Foreign keys to other systems
- Shared services integration

### 8️⃣ Testing (5%)
- Test script exists
- Coverage ≥95%
- All tests pass

---

## 🧪 معیارهای Feature Tests (9 بخش)

### 1️⃣ Core API Functionality (20%)
- All endpoints return correct HTTP status codes
- Response structure matches API documentation
- CRUD operations work correctly
- Pagination works (if applicable)
- Filtering/Sorting works (if applicable)

### 2️⃣ Authentication & Authorization (20%)
- Guest users blocked (401)
- Authenticated users can access
- Authorization policies enforced (403)
- Self-actions blocked (follow self, block self)
- Ownership verified (only owner can delete)
- Role-based access tested for all 6 roles: user, verified, premium, organization, moderator, admin

### 3️⃣ Validation & Error Handling (15%)
- Required fields validated
- Invalid data rejected (422)
- Error messages clear
- Edge cases handled (empty strings, null, etc.)

### 4️⃣ Integration with Other Systems (15%)
- Block/Mute prevents actions
- Private accounts restrict access
- Notifications sent correctly
- Events dispatched
- Cross-system relationships work

### 5️⃣ Security in Action (10%)
- XSS sanitization works
- SQL injection prevented
- Rate limiting enforced (429)
- CSRF protection active

### 6️⃣ Database Transactions (10%)
- Rollback on error
- Counters updated correctly
- No orphaned records
- Concurrent requests handled

### 7️⃣ Business Logic & Edge Cases (5%)
- Duplicate actions prevented
- Counter underflow protected
- Soft deletes work
- Timestamps updated

### 8️⃣ Real-world Scenarios (3%)
- User workflows complete successfully
- Multiple users interact correctly
- State changes persist

### 9️⃣ Performance & Response (2%)
- Response time acceptable
- N+1 queries avoided
- Eager loading works

---

## 📊 معیار تکمیل

| Score | Status | Action |
|-------|--------|--------|
| 95-100% | ✅ Complete | Production ready |
| 85-94% | 🟡 Good | Minor fixes needed |
| 70-84% | 🟠 Moderate | Improvements required |
| <70% | 🔴 Poor | Major work needed |

---

## ✅ چکلیست تکمیل سیستم

### Script Tests - Minimum Requirements (Must Have)
- [ ] Controllers با تمام methods
- [ ] Database schema کامل
- [ ] API routes تعریف شده
- [ ] Authentication middleware
- [ ] Authorization policies
- [ ] Basic validation
- [ ] XSS/SQL protection
- [ ] Test script با ≥95% pass

### Script Tests - Standard Requirements (Should Have)
- [ ] Services برای business logic
- [ ] Custom validation rules
- [ ] Resources برای API response
- [ ] Events & Listeners
- [ ] Rate limiting
- [ ] Proper error handling
- [ ] Database indexes
- [ ] Integration با Block/Mute
- [ ] Permissions & Roles configured - همه 6 نقش: user, verified, premium, organization, moderator, admin

### Script Tests - Advanced Requirements (Nice to Have)
- [ ] DTOs
- [ ] Repositories
- [ ] Jobs & Queues
- [ ] Cache management
- [ ] Advanced security (WAF, etc.)
- [ ] Performance optimization
- [ ] Comprehensive documentation

### Feature Tests - Minimum Requirements (Must Have)
- [ ] All endpoints tested with HTTP requests
- [ ] Authentication tested (401 for guests)
- [ ] Authorization tested (403 for unauthorized)
- [ ] All 6 roles tested: user, verified, premium, organization, moderator, admin
- [ ] Validation tested (422 for invalid data)
- [ ] Success responses tested (200/201)
- [ ] Integration with Block/Mute tested
- [ ] All tests pass

### Feature Tests - Standard Requirements (Should Have)
- [ ] Edge cases tested
- [ ] Error handling tested
- [ ] Transaction rollback tested
- [ ] Events dispatched verified
- [ ] Rate limiting tested (429)
- [ ] Cross-system integration tested
- [ ] Real-world scenarios tested

### Feature Tests - Advanced Requirements (Nice to Have)
- [ ] Performance tested (response time)
- [ ] N+1 query prevention verified
- [ ] Concurrent requests tested
- [ ] Complex workflows tested

---

## 📝 Template بررسی Script Tests

```markdown
# Script Test Review: [SYSTEM_NAME]

## 1. Architecture (20%)
- [ ] Controllers
- [ ] Services
- [ ] Models
- [ ] Resources/DTOs
Score: __/20

## 2. Database (15%)
- [ ] Tables
- [ ] Columns
- [ ] Indexes
- [ ] Constraints
Score: __/15

## 3. API (15%)
- [ ] Routes defined
- [ ] RESTful naming
- [ ] Middleware
Score: __/15

## 4. Security (20%)
- [ ] Authentication
- [ ] Authorization (Policies)
- [ ] Permissions (Spatie) - همه 6 نقش: user, verified, premium, organization, moderator, admin
- [ ] Roles (Spatie) - همه 6 نقش: user, verified, premium, organization, moderator, admin
- [ ] XSS/SQL protection
- [ ] Rate limiting
Score: __/20

## 5. Validation (10%)
- [ ] Request classes
- [ ] Custom rules
- [ ] Config-based
Score: __/10

## 6. Business Logic (10%)
- [ ] Core features
- [ ] Error handling
Score: __/10

## 7. Integration (5%)
- [ ] Block/Mute
- [ ] Notifications
- [ ] Events/Listeners
- [ ] Cross-system relationships
- [ ] Foreign keys work
Score: __/5

## 8. Testing (5%)
- [ ] Test script
- [ ] Coverage ≥95%
Score: __/5

**Total Score**: __/100
**Status**: [Complete/Good/Moderate/Poor]
```

## 📝 Template بررسی Feature Tests

```markdown
# Feature Test Review: [SYSTEM_NAME]

## 1. Core API Functionality (20%)
- [ ] All endpoints return correct status codes
- [ ] Response structure correct
- [ ] CRUD operations work
- [ ] Pagination/Filtering works
Score: __/20

## 2. Authentication & Authorization (20%)
- [ ] Guest blocked (401)
- [ ] Auth users access
- [ ] Policies enforced (403)
- [ ] Self-actions blocked
- [ ] Ownership verified
- [ ] All 6 roles tested: user, verified, premium, organization, moderator, admin
Score: __/20

## 3. Validation & Error Handling (15%)
- [ ] Required fields validated
- [ ] Invalid data rejected (422)
- [ ] Error messages clear
- [ ] Edge cases handled
Score: __/15

## 4. Integration with Other Systems (15%)
- [ ] Block/Mute prevents actions
- [ ] Private accounts restrict
- [ ] Notifications sent
- [ ] Events dispatched
- [ ] Cross-system relationships
Score: __/15

## 5. Security in Action (10%)
- [ ] XSS sanitization
- [ ] SQL injection prevented
- [ ] Rate limiting (429)
- [ ] CSRF protection
Score: __/10

## 6. Database Transactions (10%)
- [ ] Rollback on error
- [ ] Counters updated
- [ ] No orphaned records
- [ ] Concurrent requests
Score: __/10

## 7. Business Logic & Edge Cases (5%)
- [ ] Duplicate actions prevented
- [ ] Counter underflow protected
- [ ] Soft deletes work
Score: __/5

## 8. Real-world Scenarios (3%)
- [ ] User workflows complete
- [ ] Multiple users interact
Score: __/3

## 9. Performance & Response (2%)
- [ ] Response time acceptable
- [ ] N+1 queries avoided
Score: __/2

**Total Score**: __/100
**Status**: [Complete/Good/Moderate/Poor]
```

---

## 🎯 الزامات کلی

### Script Tests
1. **Coverage**: ≥95% of code structure
2. **Sections**: 20 بخش استاندارد
3. **Focus**: Database, Models, Services, Controllers, Routes, Policies
4. **Method**: Direct PHP execution

### Feature Tests
1. **Coverage**: All API endpoints
2. **Sections**: 9 بخش استاندارد
3. **Focus**: HTTP requests, Authorization, Validation, Integration
4. **Method**: Laravel HTTP testing

### Both Tests
1. **Security**: حداقل 8 لایه (Authentication, Policies, Permissions, Roles, XSS, SQL, CSRF, Rate Limiting)
2. **Performance**: Response time < 100ms
3. **Documentation**: مستندات کامل
4. **Integration**: تست یکپارچگی

---

## 📊 تفاوت Script Tests vs Feature Tests

| Aspect | Script Tests (20 sections) | Feature Tests (9 sections) |
|--------|---------------------------|---------------------------|
| **Purpose** | Test code structure | Test API functionality |
| **Method** | Direct PHP execution | HTTP requests |
| **Can Test** | Database schema, Models, Services, Policies code | Endpoints, Authorization, Validation, Integration |
| **Cannot Test** | HTTP responses, Middleware in action | Database schema, Code structure |
| **Focus** | Internal implementation | External behavior |
| **Example** | "Does UserPolicy.php have follow() method?" | "Does POST /api/users/{id}/follow return 403 when blocked?" |

---

**تاریخ ایجاد:** 2026-02-10  
**آخرین بروزرسانی:** 2026-02-10  
**نسخه:** 2.0


---

## ⚠️ نکته مهم: تست نقش‌ها (Roles)

**الزامی:** در تمام تست‌ها (Script Tests و Feature Tests)، باید همه 6 نقش سیستم تست شوند:

1. **user** - کاربر عادی
2. **verified** - کاربر تایید شده
3. **premium** - کاربر پرمیوم
4. **organization** - سازمان
5. **moderator** - مدیر
6. **admin** - ادمین

### در Script Tests (بخش 6 و 18):
```php
// بخش 6: Security & Authorization
test("Role user has permission", fn() => Role::findByName('user')->hasPermissionTo('permission.name'));
test("Role verified has permission", fn() => Role::findByName('verified')->hasPermissionTo('permission.name'));
test("Role premium has permission", fn() => Role::findByName('premium')->hasPermissionTo('permission.name'));
test("Role organization has permission", fn() => Role::findByName('organization')->hasPermissionTo('permission.name'));
test("Role moderator has permission", fn() => Role::findByName('moderator')->hasPermissionTo('permission.name'));
test("Role admin has permission", fn() => Role::findByName('admin')->hasPermissionTo('permission.name'));

// بخش 18: Roles & Permissions Database
test("Role user exists", fn() => Role::where('name', 'user')->exists());
test("Role verified exists", fn() => Role::where('name', 'verified')->exists());
test("Role premium exists", fn() => Role::where('name', 'premium')->exists());
test("Role organization exists", fn() => Role::where('name', 'organization')->exists());
test("Role moderator exists", fn() => Role::where('name', 'moderator')->exists());
test("Role admin exists", fn() => Role::where('name', 'admin')->exists());
```

### در Feature Tests (بخش 2 و 10):
```php
// بخش 2: Authentication & Authorization
public function test_user_role_can_access()
public function test_verified_role_can_access()
public function test_premium_role_can_access()
public function test_organization_role_can_access()
public function test_moderator_role_can_access()
public function test_admin_role_can_access()

// بخش 10: Role-Based Access Control (اختیاری اما توصیه می‌شود)
public function test_all_roles_permissions()
```

**هیچ تستی نباید کمتر از 6 نقش را بررسی کند.**

---

**آخرین بروزرسانی:** 2026-02-10  
**نسخه:** 2.1


## 🔐 الزام تست سطوح دسترسی (Access Levels)

**بسیار مهم:** علاوه بر تست وجود 6 نقش، باید سطوح دسترسی هر نقش به دقت بررسی شود.

### 3 نوع تست الزامی:

#### 1️⃣ تست مثبت (Can Access) - 200/201
نقش باید بتواند به endpoint هایی که permission دارد دسترسی پیدا کند.

```php
// Script Test
test("Role verified has search.advanced", fn() => Role::findByName('verified')->hasPermissionTo('search.advanced'));

// Feature Test
public function test_verified_role_can_advanced_search()
{
    $verified = User::factory()->create();
    $verified->assignRole('verified');
    $response = $this->actingAs($verified)->getJson('/api/search/advanced?q=test');
    $response->assertOk(); // 200
}
```

#### 2️⃣ تست منفی (Cannot Access) - 403
نقش نباید بتواند به endpoint هایی که permission ندارد دسترسی پیدا کند.

```php
// Script Test
test("Role user does NOT have search.advanced", fn() => !Role::findByName('user')->hasPermissionTo('search.advanced'));

// Feature Test
public function test_user_role_cannot_advanced_search()
{
    $user = User::factory()->create();
    $user->assignRole('user');
    $response = $this->actingAs($user)->getJson('/api/search/advanced?q=test');
    $response->assertForbidden(); // 403
}
```

#### 3️⃣ تست تفاوت سطوح (Level Difference)
نقش های پایین تر نباید بتوانند کارهای نقش های بالاتر را انجام دهند.

```php
// مثال: user نمی تواند advanced search کند، اما verified می تواند
test("User cannot but Verified can", function() {
    $userRole = Role::findByName('user');
    $verifiedRole = Role::findByName('verified');
    
    return !$userRole->hasPermissionTo('search.advanced') 
        && $verifiedRole->hasPermissionTo('search.advanced');
});
```

### جدول سطوح دسترسی (مثال):

| Permission | user | verified | premium | organization | moderator | admin |
|------------|------|----------|---------|--------------|-----------|-------|
| basic      | ✅   | ✅       | ✅      | ✅           | ✅        | ✅    |
| advanced   | ❌   | ✅       | ✅      | ✅           | ✅        | ✅    |
| moderate   | ❌   | ❌       | ❌      | ❌           | ✅        | ✅    |
| admin      | ❌   | ❌       | ❌      | ❌           | ❌        | ✅    |

### چکلیست تست سطوح دسترسی:

برای هر permission در سیستم:
- [ ] تست مثبت برای نقش هایی که permission دارند (200)
- [ ] تست منفی برای نقش هایی که permission ندارند (403)
- [ ] تست تفاوت بین نقش پایین تر و بالاتر
- [ ] همه 6 نقش بررسی شده اند

---

**آخرین بروزرسانی:** 2026-02-10  
**نسخه:** 2.2

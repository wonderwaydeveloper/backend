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
- Permissions (Spatie)
- Roles (Spatie)
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
- [ ] Permissions & Roles configured

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
- [ ] Permissions (Spatie)
- [ ] Roles (Spatie)
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

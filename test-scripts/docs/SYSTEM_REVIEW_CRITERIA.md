# 📋 معیارهای بررسی سیستم

## 🎯 معیارهای استاندارد بررسی (100 امتیاز)

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

### 8️⃣ Testing (5%)
- Test script exists
- Coverage ≥95%
- All tests pass

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

### Minimum Requirements (Must Have)
- [ ] Controllers با تمام methods
- [ ] Database schema کامل
- [ ] API routes تعریف شده
- [ ] Authentication middleware
- [ ] Authorization policies
- [ ] Basic validation
- [ ] XSS/SQL protection
- [ ] Test script با ≥95% pass

### Standard Requirements (Should Have)
- [ ] Services برای business logic
- [ ] Custom validation rules
- [ ] Resources برای API response
- [ ] Events & Listeners
- [ ] Rate limiting
- [ ] Proper error handling
- [ ] Database indexes
- [ ] Integration با Block/Mute

### Advanced Requirements (Nice to Have)
- [ ] DTOs
- [ ] Repositories
- [ ] Jobs & Queues
- [ ] Cache management
- [ ] Advanced security (WAF, etc.)
- [ ] Performance optimization
- [ ] Comprehensive documentation

---

## 📝 Template بررسی سیستم

```markdown
# System Review: [SYSTEM_NAME]

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
- [ ] Authorization
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
Score: __/5

## 8. Testing (5%)
- [ ] Test script
- [ ] Coverage ≥95%
Score: __/5

**Total Score**: __/100
**Status**: [Complete/Good/Moderate/Poor]
```

---

## 🎯 الزامات کلی

1. **Tests**: ≥95% coverage
2. **Security**: حداقل 8 لایه
3. **Performance**: Response time < 100ms
4. **Documentation**: مستندات کامل
5. **Integration**: تست یکپارچگی

---

**تاریخ ایجاد:** 2026-02-10  
**نسخه:** 1.0

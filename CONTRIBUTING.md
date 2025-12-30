# راهنمای مشارکت در WonderWay

از علاقه شما به مشارکت در پروژه WonderWay متشکریم! این راهنما شامل اطلاعات لازم برای مشارکت مؤثر در پروژه است.

## فهرست مطالب

- [کد رفتار](#کد-رفتار)
- [چگونه مشارکت کنیم](#چگونه-مشارکت-کنیم)
- [گزارش باگ](#گزارش-باگ)
- [درخواست ویژگی](#درخواست-ویژگی)
- [Pull Request](#pull-request)
- [استانداردهای کدنویسی](#استانداردهای-کدنویسی)
- [تست](#تست)
- [مستندات](#مستندات)
- [محیط توسعه](#محیط-توسعه)

---

## کد رفتار

### تعهد ما

ما متعهد به ایجاد محیطی باز، خوشایند، متنوع، فراگیر و سالم برای همه هستیم.

### استانداردهای ما

نمونههایی از رفتار مثبت:

- **احترام متقابل**: احترام به نظرات و تجربیات مختلف
- **پذیرش انتقاد**: پذیرش انتقاد سازنده با روح باز
- **تمرکز بر جامعه**: تمرکز بر آنچه برای جامعه بهترین است
- **همدلی**: نشان دادن همدلی با سایر اعضای جامعه

رفتارهای غیرقابل قبول:

- استفاده از زبان یا تصاویر جنسی
- حملات شخصی یا سیاسی
- آزار عمومی یا خصوصی
- انتشار اطلاعات خصوصی دیگران بدون اجازه

### اجرای قوانین

موارد نقض کد رفتار را به آدرس conduct@wonderway.com گزارش دهید.

---

## چگونه مشارکت کنیم

### انواع مشارکت

- **گزارش باگ**: یافتن و گزارش مشکلات
- **درخواست ویژگی**: پیشنهاد قابلیتهای جدید
- **کد نویسی**: پیادهسازی ویژگیها و رفع باگها
- **مستندات**: بهبود و تکمیل مستندات
- **تست**: نوشتن و بهبود تستها
- **بررسی کد**: Code review و بازخورد
- **ترجمه**: ترجمه به زبانهای مختلف

### شروع کار

1. **Fork کردن repository**
2. **Clone کردن fork شده**
3. **ایجاد branch جدید**
4. **انجام تغییرات**
5. **Commit و Push**
6. **ایجاد Pull Request**

---

## گزارش باگ

### قبل از گزارش

- جستجو در [Issues موجود](https://github.com/wonderway/backend/issues)
- بررسی [مستندات](https://docs.wonderway.com)
- تست در آخرین نسخه

### Template گزارش باگ

```markdown
## شرح باگ
توضیح واضح و مختصر از باگ

## مراحل بازتولید
1. برو به '...'
2. کلیک روی '...'
3. اسکرول به '...'
4. مشاهده خطا

## رفتار مورد انتظار
توضیح آنچه انتظار داشتید اتفاق بیفتد

## رفتار واقعی
توضیح آنچه واقعاً اتفاق افتاد

## اسکرین‌شات
در صورت امکان، اسکرین‌شات اضافه کنید

## محیط
- OS: [e.g. Ubuntu 20.04]
- PHP Version: [e.g. 8.2]
- Laravel Version: [e.g. 12.0]
- Browser: [e.g. Chrome 120]

## اطلاعات اضافی
هر اطلاعات اضافی که ممکن است مفید باشد
```

### اولویت‌بندی باگها

- **Critical**: باگهایی که سیستم را از کار می‌اندازند
- **High**: باگهای مهم که عملکرد اصلی را تحت تأثیر قرار می‌دهند
- **Medium**: باگهای متوسط که تجربه کاربری را کاهش می‌دهند
- **Low**: باگهای جزئی یا مشکلات cosmetic

---

## درخواست ویژگی

### Template درخواست ویژگی

```markdown
## مشکل مرتبط
آیا درخواست شما مربوط به مشکلی است؟ لطفاً توضیح دهید.

## راه‌حل پیشنهادی
توضیح واضح و مختصر از آنچه می‌خواهید اتفاق بیفتد

## جایگزین‌های در نظر گرفته شده
توضیح راه‌حل‌های جایگزینی که در نظر گرفته‌اید

## اطلاعات اضافی
هر اطلاعات اضافی یا اسکرین‌شات درباره درخواست ویژگی
```

### ارزیابی درخواست‌ها

درخواست‌ها بر اساس موارد زیر ارزیابی می‌شوند:

- **تأثیر**: چقدر کاربران را تحت تأثیر قرار می‌دهد
- **تلاش**: میزان کار مورد نیاز برای پیادهسازی
- **هماهنگی**: سازگاری با اهداف پروژه
- **نگهداری**: تأثیر بر نگهداری آینده

---

## Pull Request

### فرآیند Pull Request

#### 1. آماده‌سازی

```bash
# Fork repository در GitHub
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend

# اضافه کردن upstream remote
git remote add upstream https://github.com/wonderway/wonderway-backend.git

# ایجاد branch جدید
git checkout -b feature/new-feature
```

#### 2. توسعه

```bash
# انجام تغییرات
# ...

# اجرای تست‌ها
composer test

# بررسی کد استایل
composer cs-check

# رفع مشکلات کد استایل
composer cs-fix
```

#### 3. Commit

```bash
# Staging تغییرات
git add .

# Commit با پیام مناسب
git commit -m "feat: add new feature description"

# Push به fork
git push origin feature/new-feature
```

#### 4. ایجاد Pull Request

- برو به GitHub repository
- کلیک روی "New Pull Request"
- انتخاب branch مناسب
- پر کردن template

### Template Pull Request

```markdown
## نوع تغییر
- [ ] Bug fix (تغییری که باگ را رفع می‌کند)
- [ ] New feature (تغییری که قابلیت جدید اضافه می‌کند)
- [ ] Breaking change (تغییری که سازگاری را می‌شکند)
- [ ] Documentation update (بروزرسانی مستندات)

## شرح
توضیح واضح از تغییرات انجام شده

## مسئله مرتبط
Fixes #(issue number)

## تست
توضیح تست‌هایی که انجام داده‌اید

## Checklist
- [ ] کد از استانداردهای پروژه پیروی می‌کند
- [ ] خود-بررسی کد انجام شده
- [ ] تغییرات کامنت‌گذاری شده (در صورت نیاز)
- [ ] مستندات مربوطه بروزرسانی شده
- [ ] تست‌های جدید اضافه شده
- [ ] تمام تست‌ها پاس می‌شوند
```

### بررسی Pull Request

#### معیارهای بررسی

- **عملکرد**: آیا کد کار می‌کند؟
- **کیفیت**: آیا کد تمیز و قابل فهم است؟
- **تست**: آیا تست‌های کافی وجود دارد؟
- **مستندات**: آیا مستندات بروزرسانی شده؟
- **امنیت**: آیا مشکل امنیتی وجود دارد؟

#### فرآیند بررسی

1. **Automated Checks**: CI/CD pipeline
2. **Code Review**: بررسی توسط maintainers
3. **Testing**: تست در محیط staging
4. **Approval**: تأیید نهایی
5. **Merge**: ادغام با branch اصلی

---

## استانداردهای کدنویسی

### PHP Standards

#### PSR Compliance

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * User service for handling user operations
 */
class UserService
{
    /**
     * Get active users
     *
     * @param int $limit
     * @return Collection
     */
    public function getActiveUsers(int $limit = 10): Collection
    {
        return User::where('is_active', true)
            ->limit($limit)
            ->get();
    }
}
```

#### Naming Conventions

```php
// ✅ درست
class UserService {}
interface UserRepositoryInterface {}
trait Cacheable {}

// متغیرها
$userName = 'john_doe';
$isActive = true;
$userCount = 100;

// متدها
public function getUserById(int $id): ?User {}
public function createUser(array $data): User {}

// ❌ غلط
class userservice {}
interface user_repository {}
$UserName = 'john_doe';
public function get_user_by_id($id) {}
```

#### Documentation

```php
/**
 * Create a new user account
 *
 * @param array $userData User registration data
 * @param bool $sendWelcomeEmail Whether to send welcome email
 * @return User The created user instance
 * @throws ValidationException When validation fails
 * @throws UserExistsException When user already exists
 */
public function createUser(array $userData, bool $sendWelcomeEmail = true): User
{
    // Implementation
}
```

### Database Standards

#### Migration Naming

```php
// ✅ درست
2024_01_01_000001_create_users_table.php
2024_01_01_000002_add_avatar_to_users_table.php
2024_01_01_000003_create_posts_table.php

// ❌ غلط
create_users.php
add_column.php
```

#### Model Relationships

```php
class User extends Model
{
    // ✅ درست - نام‌گذاری واضح
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
    
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id');
    }
    
    // ❌ غلط - نام‌گذاری مبهم
    public function items() {}
    public function relations() {}
}
```

### API Standards

#### Response Format

```php
// ✅ درست - فرمت استاندارد
return response()->json([
    'success' => true,
    'data' => $users,
    'message' => 'Users retrieved successfully',
    'meta' => [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage
    ]
]);

// ❌ غلط - فرمت نامنظم
return response()->json($users);
```

#### Error Handling

```php
// ✅ درست
try {
    $user = $this->userService->createUser($data);
    return response()->json([
        'success' => true,
        'data' => $user,
        'message' => 'User created successfully'
    ], 201);
} catch (ValidationException $e) {
    return response()->json([
        'success' => false,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
            'details' => $e->errors()
        ]
    ], 422);
}
```

---

## تست

### انواع تست

#### Unit Tests

```php
class UserServiceTest extends TestCase
{
    public function test_can_create_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123'
        ];
        
        $user = $this->userService->createUser($userData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }
}
```

#### Feature Tests

```php
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);
        
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(200)
                ->assertJsonStructure(['user', 'token']);
    }
}
```

### Test Coverage

```bash
# اجرای تست با coverage
php artisan test --coverage

# حداقل coverage مورد قبول: 80%
```

### Test Guidelines

- **AAA Pattern**: Arrange, Act, Assert
- **نام‌گذاری واضح**: `test_user_can_create_post`
- **یک assertion در هر تست**
- **استفاده از Factory ها**
- **Mock کردن external services**

---

## مستندات

### انواع مستندات

#### Code Documentation

```php
/**
 * Calculate user engagement score
 *
 * This method calculates a user's engagement score based on their
 * activity metrics including posts, likes, comments, and followers.
 *
 * @param User $user The user to calculate score for
 * @param int $days Number of days to look back (default: 30)
 * @return float Engagement score between 0 and 100
 * 
 * @example
 * $score = $this->calculateEngagementScore($user, 7);
 * // Returns: 85.5
 */
public function calculateEngagementScore(User $user, int $days = 30): float
{
    // Implementation
}
```

#### API Documentation

```php
/**
 * @OA\Post(
 *     path="/api/posts",
 *     summary="Create a new post",
 *     tags={"Posts"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"content"},
 *             @OA\Property(property="content", type="string", example="Hello World!"),
 *             @OA\Property(property="image", type="string", format="binary")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Post created successfully"
 *     )
 * )
 */
public function store(Request $request) {}
```

#### README Updates

هنگام اضافه کردن ویژگی جدید:

1. بروزرسانی فهرست ویژگیها
2. اضافه کردن مثال استفاده
3. بروزرسانی دستورات نصب
4. اضافه کردن تصاویر (در صورت نیاز)

---

## محیط توسعه

### نصب محیط توسعه

```bash
# کلون repository
git clone https://github.com/wonderway/wonderway-backend.git
cd wonderway-backend

# نصب dependencies
composer install
npm install

# کپی فایل محیط
cp .env.example .env

# تولید کلید اپلیکیشن
php artisan key:generate

# راهاندازی دیتابیس
php artisan migrate
php artisan db:seed
```

### ابزارهای توسعه

#### Code Quality Tools

```bash
# PHP CS Fixer
composer cs-fix

# PHPStan
composer analyse

# PHP Insights
composer insights
```

#### Git Hooks

```bash
# نصب pre-commit hooks
cp .githooks/pre-commit .git/hooks/
chmod +x .git/hooks/pre-commit
```

#### IDE Configuration

##### VS Code Extensions

- PHP Intelephense
- Laravel Extension Pack
- GitLens
- PHP CS Fixer
- Thunder Client

##### PhpStorm Plugins

- Laravel Plugin
- PHP Annotations
- .env files support
- Swagger Plugin

### Debugging

#### Laravel Telescope

```bash
# نصب Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

#### Xdebug Configuration

```ini
; php.ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

---

## Release Process

### Semantic Versioning

- **MAJOR**: تغییرات breaking
- **MINOR**: ویژگیهای جدید (backward compatible)
- **PATCH**: رفع باگ (backward compatible)

### Release Checklist

- [ ] تمام تست‌ها پاس می‌شوند
- [ ] مستندات بروزرسانی شده
- [ ] CHANGELOG بروزرسانی شده
- [ ] Security audit انجام شده
- [ ] Performance testing انجام شده
- [ ] Migration scripts تست شده

---

## سوالات متداول

### چگونه شروع کنم؟

1. مطالعه مستندات
2. نصب محیط توسعه
3. یافتن issue مناسب برای شروع
4. پرسیدن سوال در Discord

### چه نوع مشارکتی مناسب من است؟

- **تازه‌کار**: Documentation, Testing, Bug Reports
- **متوسط**: Bug Fixes, Small Features
- **پیشرفته**: Major Features, Architecture Changes

### چقدر طول می‌کشد تا PR من بررسی شود؟

- **Bug fixes**: 1-3 روز
- **Small features**: 3-7 روز
- **Major features**: 1-2 هفته

### چگونه با تیم در ارتباط باشم؟

- **GitHub Issues**: برای باگ و feature request
- **Discord**: برای گفتگوی عمومی
- **Email**: برای مسائل حساس

---

## منابع

### مستندات

- [Laravel Documentation](https://laravel.com/docs)
- [PHP Standards](https://www.php-fig.org/psr/)
- [API Documentation](https://docs.wonderway.com)

### ابزارها

- [GitHub Desktop](https://desktop.github.com/)
- [Postman](https://www.postman.com/)
- [TablePlus](https://tableplus.com/)

### آموزش

- [Git Tutorial](https://git-scm.com/docs/gittutorial)
- [Laravel Bootcamp](https://bootcamp.laravel.com/)
- [PHP The Right Way](https://phptherightway.com/)

---

## تشکر

از تمام مشارکت‌کنندگان پروژه WonderWay تشکر می‌کنیم:

- [لیست مشارکت‌کنندگان](https://github.com/wonderway/backend/contributors)

مشارکت شما، هر چقدر هم کوچک، ارزشمند است! 🙏
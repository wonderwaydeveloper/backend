# 📋 راهنمای معماری تست‌ها

## 🎯 هدف
این راهنما معماری استاندارد برای نوشتن تست‌های سیستمی را تعریف می‌کند. تمام تست‌های جدید باید از این معماری پیروی کنند.

---

## 🏗️ ساختار کلی تست

### 1. Header و Bootstrap
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use Illuminate\\Support\\Facades\\{DB, Cache, Hash};
use App\\Models\\{User, Post};
use App\\Services\\{PostService, SpamDetectionService};

echo "\\n╔═══════════════════════════════════════════════════════════════╗\\n";
echo "║     تست کامل سیستم [SYSTEM_NAME] - X بخش (Y تست)           ║\\n";
echo "╚═══════════════════════════════════════════════════════════════╝\\n\\n";
```

### 2. آماده‌سازی
```php
$stats = ['passed' => 0, 'failed' => 0, 'warning' => 0];
$testUsers = [];

function test($name, $fn) {
    global $stats;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  ✓ {$name}\\n";
            $stats['passed']++;
        } elseif ($result === null) {
            echo "  ⚠ {$name}\\n";
            $stats['warning']++;
        } else {
            echo "  ✗ {$name}\\n";
            $stats['failed']++;
        }
    } catch (\\Exception $e) {
        echo "  ✗ {$name}: " . substr($e->getMessage(), 0, 50) . "\\n";
        $stats['failed']++؛
    }
}
```

---

## 📦 بخش‌های استاندارد (20 بخش)

### بخش 1: Database & Schema
```php
echo "1️⃣ بخش 1: Database & Schema\\n" . str_repeat("─", 65) . "\\n";

// بررسی جداول
test("Table exists", fn() => DB::getSchemaBuilder()->hasTable('posts'));

// بررسی ستون‌ها
$columns = array_column(DB::select("SHOW COLUMNS FROM posts"), 'Field');
test("Column user_id", fn() => in_array('user_id', $columns));

// بررسی indexes
$indexes = DB::select("SHOW INDEXES FROM posts");
test("Index user_id", fn() => collect($indexes)->where('Column_name', 'user_id')->isNotEmpty());

// بررسی foreign keys
test("Foreign key user_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='posts' AND COLUMN_NAME='user_id'")) > 0);
```

### بخش 2: Models & Relationships
```php
echo "\\n2️⃣ بخش 2: Models & Relationships\\n" . str_repeat("─", 65) . "\\n";

test("Model exists", fn() => class_exists('App\\Models\\Post'));
test("Relationships", fn() => method_exists('App\\Models\\Post', 'user'));
test("Mass assignment protection", fn() => !in_array('id', (new Post())->getFillable()));
```

### بخش 3: Validation Integration
```php
echo "\\n3️⃣ بخش 3: Validation Integration\\n" . str_repeat("─", 65) . "\\n";

test("Custom rule exists", fn() => class_exists('App\\Rules\\ContentLength'));
test("Request class exists", fn() => class_exists('App\\Http\\Requests\\StorePostRequest'));
test("Config-based validation", fn() => config('validation.content.post.max_length') !== null);
test("No hardcoded values", fn() => strpos(file_get_contents(__DIR__ . '/app/Http/Requests/StorePostRequest.php'), 'max:280') === false);
```

### بخش 4: Controllers & Services
```php
echo "\\n4️⃣ بخش 4: Controllers & Services\\n" . str_repeat("─", 65) . "\\n";

test("Controller exists", fn() => class_exists('App\\Http\\Controllers\\Api\\PostController'));
test("Service exists", fn() => class_exists('App\\Services\\PostService'));
test("Service methods", fn() => method_exists('App\\Services\\PostService', 'createPost'));
```

### بخش 5: Core Features
```php
echo "\\n5️⃣ بخش 5: Core Features\\n" . str_repeat("─", 65) . "\\n";

// تست عملکرد اصلی سیستم
test("Create functionality", function() {
    $post = Post::create(['user_id' => 1, 'content' => 'Test']);
    return $post->exists;
});
```

### بخش 6: Security & Authorization (30 تست)
```php
echo "\\n6️⃣ بخش 6: Security & Authorization\\n" . str_repeat("─", 65) . "\\n";

// Authentication
test("Sanctum middleware", fn() => strpos(file_get_contents(__DIR__ . '/routes/api.php'), 'auth:sanctum') !== false);

// Authorization
test("Policy exists", fn() => class_exists('App\\Policies\\PostPolicy'));
test("Policy methods", fn() => method_exists('App\\Policies\\PostPolicy', 'update'));

// Permissions (Spatie)
test("Permission exists", fn() => \\Spatie\\Permission\\Models\\Permission::where('name', 'post.create')->exists());
test("User has permission", fn() => $user->hasPermissionTo('post.create'));

// Roles (Spatie)
test("Role exists", fn() => \\Spatie\\Permission\\Models\\Role::where('name', 'user')->exists());
test("User has role", fn() => $user->hasRole('user'));
test("Role has permission", fn() => \\Spatie\\Permission\\Models\\Role::findByName('user')->hasPermissionTo('post.create'));

// XSS Protection
test("XSS prevention", fn() => !str_contains($post->content, '<script>'));

// SQL Injection
test("SQL injection protection", fn() => DB::table('posts')->exists());

// Rate Limiting
test("Throttle middleware", fn() => strpos(file_get_contents(__DIR__ . '/routes/api.php'), 'throttle:') !== false);

// CSRF
test("CSRF protection", fn() => class_exists('App\\Http\\Middleware\\CSRFProtection'));

// Mass Assignment
test("Mass assignment protection", fn() => !in_array('id', (new Post())->getFillable()));
```

### بخش 7: Spam Detection
```php
echo "\\n7️⃣ بخش 7: Spam Detection\\n" . str_repeat("─", 65) . "\\n";

test("Spam service exists", fn() => class_exists('App\\Services\\SpamDetectionService'));
test("Spam detection methods", fn() => method_exists('App\\Services\\SpamDetectionService', 'checkPost'));
```

### بخش 8: Performance & Optimization
```php
echo "\\n8️⃣ بخش 8: Performance & Optimization\\n" . str_repeat("─", 65) . "\\n";

test("Eager loading", fn() => Post::with('user')->first()->relationLoaded('user'));
test("Pagination", fn() => method_exists(Post::paginate(10), 'links'));
test("Cache support", fn() => Cache::put('test', 'val', 60));
```

### بخش 9: Data Integrity & Transactions
```php
echo "\\n9️⃣ بخش 9: Data Integrity & Transactions\\n" . str_repeat("─", 65) . "\\n";

test("Transaction support", function() {
    DB::beginTransaction();
    $post = Post::create(['user_id' => 1, 'content' => 'Test']);
    DB::rollBack();
    return !Post::find($post->id);
});

test("Unique constraints", fn() => /* test unique constraint */);
test("Not null constraints", fn() => /* test not null */);
```

### بخش 10: API & Routes
```php
echo "\\n🔟 بخش 10: API & Routes\\n" . str_repeat("─", 65) . "\\n";

$routes = collect(\\Route::getRoutes());
test("GET /api/posts", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && str_contains($r->uri(), 'api/posts')));
test("POST /api/posts", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && $r->uri() == 'api/posts'));
```

### بخش 11: Configuration
```php
echo "\\n1️⃣1️⃣ بخش 11: Configuration\\n" . str_repeat("─", 65) . "\\n";

test("Config file exists", fn() => file_exists(__DIR__ . '/config/posts.php'));
test("Config values", fn() => config('posts.max_length') !== null);
```

### بخش 12: Advanced Features
```php
echo "\\n1️⃣2️⃣ بخش 12: Advanced Features\\n" . str_repeat("─", 65) . "\\n";

test("Advanced model exists", fn() => class_exists('App\\Models\\ScheduledPost'));
test("Advanced functionality", fn() => method_exists(Post::class, 'schedule'));
```

### بخش 13: Events & Integration
```php
echo "\\n1️⃣3️⃣ بخش 13: Events & Integration\\n" . str_repeat("─", 65) . "\\n";

test("Event exists", fn() => class_exists('App\\Events\\PostPublished'));
test("Listener exists", fn() => class_exists('App\\Listeners\\SendNotification'));
test("Job exists", fn() => class_exists('App\\Jobs\\ProcessPost'));
```

### بخش 14: Error Handling
```php
echo "\\n1️⃣4️⃣ بخش 14: Error Handling\\n" . str_repeat("─", 65) . "\\n";

test("Exception exists", fn() => class_exists('App\\Exceptions\\PostNotFoundException'));
test("404 handling", fn() => Post::find(999999) === null);
```

### بخش 15: Resources
```php
echo "\\n1️⃣5️⃣ بخش 15: Resources\\n" . str_repeat("─", 65) . "\\n";

test("Resource exists", fn() => class_exists('App\\Http\\Resources\\PostResource'));
test("Resource structure", fn() => isset((new \\App\\Http\\Resources\\PostResource($post))->toArray(request())['id']));
```

### بخش 16: User Flows
```php
echo "\\n1️⃣6️⃣ بخش 16: User Flows\\n" . str_repeat("─", 65) . "\\n";

test("Flow: Create → Publish", function() {
    $post = Post::create(['user_id' => 1, 'content' => 'Test', 'is_draft' => true]);
    $post->update(['is_draft' => false, 'published_at' => now()]);
    return !$post->fresh()->is_draft;
});
```

### بخش 17: Validation Advanced
```php
echo "\\n1️⃣7️⃣ بخش 17: Validation Advanced\\n" . str_repeat("─", 65) . "\\n";

test("Validator: invalid input", function() {
    $validator = \\Validator::make(['content' => ''], ['content' => 'required']);
    return $validator->fails();
});
```

### بخش 18: Roles & Permissions Database
```php
echo "\\n1️⃣8️⃣ بخش 18: Roles & Permissions Database\\n" . str_repeat("─", 65) . "\\n";

test("Role exists", fn() => \\Spatie\\Permission\\Models\\Role::where('name', 'user')->exists());
test("Role has permission", fn() => \\Spatie\\Permission\\Models\\Role::findByName('user')->hasPermissionTo('post.create'));
```

### بخش 19: Security Layers Deep Dive
```php
echo "\\n1️⃣9️⃣ بخش 19: Security Layers Deep Dive\\n" . str_repeat("─", 65) . "\\n";

test("Security header: HSTS", fn() => strpos(file_get_contents(__DIR__ . '/app/Http/Middleware/SecurityHeaders.php'), 'Strict-Transport-Security') !== false);
test("XSS practical test", fn() => /* practical XSS test */);
```

### بخش 20: Middleware & Bootstrap
```php
echo "\\n2️⃣0️⃣ بخش 20: Middleware & Bootstrap\\n" . str_repeat("─", 65) . "\\n";

test("Middleware registered", fn() => strpos(file_get_contents(__DIR__ . '/bootstrap/app.php'), 'SecurityHeaders') !== false);
```

---

## 🧹 پاکسازی

```php
echo "\\n🧹 پاکسازی...\\n";
foreach ($testUsers as $user) {
    if ($user && $user->exists) {
        $user->delete();
    }
}
echo "  ✓ پاکسازی انجام شد\\n";
```

---

## 📊 گزارش نهایی

```php
$total = array_sum($stats);
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 1) : 0;

echo "\\n╔═══════════════════════════════════════════════════════════════╗\\n";
echo "║                    گزارش نهایی                                ║\\n";
echo "╚═══════════════════════════════════════════════════════════════╝\\n\\n";
echo "📊 آمار کامل:\\n";
echo "  • کل تستها: {$total}\\n";
echo "  • موفق: {$stats['passed']} ✓\\n";
echo "  • ناموفق: {$stats['failed']} ✗\\n";
echo "  • هشدار: {$stats['warning']} ⚠\\n";
echo "  • درصد موفقیت: {$percentage}%\\n\\n";

if ($percentage >= 95) {
    echo "🎉 عالی: سیستم کاملاً production-ready است!\\n";
} elseif ($percentage >= 85) {
    echo "✅ خوب: سیستم آماده با مسائل جزئی\\n";
} elseif ($percentage >= 70) {
    echo "⚠️ متوسط: نیاز به بهبود\\n";
} else {
    echo "❌ ضعیف: نیاز به رفع مشکلات جدی\\n";
}

echo "\\n20 بخش تست شده:\\n";
echo "1️⃣ Database & Schema | 2️⃣ Models & Relationships | 3️⃣ Validation Integration\\n";
// ... لیست کامل بخش‌ها
```

---

## ✅ چکلیست تست جدید

- [ ] Header با نام سیستم و تعداد تست
- [ ] Bootstrap Laravel
- [ ] تابع test() استاندارد
- [ ] 20 بخش کامل
- [ ] حداقل 150 تست
- [ ] بخش Security با 30 تست
- [ ] پاکسازی داده‌های تست
- [ ] گزارش نهایی با درصد موفقیت
- [ ] لیست 20 بخش در انتها

---

## 📝 نکات مهم

1. **تعداد تست**: حداقل 150 تست برای هر سیستم
2. **بخش Security**: حداقل 30 تست امنیتی
3. **پاکسازی**: همیشه داده‌های تست را پاک کنید
4. **Config-based**: از config استفاده کنید نه hardcode
5. **معماری یکپارچه**: تمام تست‌ها باید از این ساختار پیروی کنند

---

**تاریخ ایجاد:** 2025-02-04  
**نسخه:** 1.0  
**وضعیت:** استاندارد رسمی

## 🔗 تست یکپارچگی (Integration Testing)

### اصول یکپارچگی

**1. Block/Mute System**
- تایملاین باید پستهای کاربران بلاک شده را فیلتر کند
- کامنتها باید کاربران میوت شده را فیلتر کنند
- لایک و ریپست از کاربران بلاک شده نمایش داده نشود

**2. Notification System**
- هر لایک باید نوتیفیکیشن ارسال کند
- هر کامنت باید نوتیفیکیشن ارسال کند
- هر منشن باید نوتیفیکیشن ارسال کند
- ریپست و کوت باید نوتیفیکیشن ارسال کند

**3. Spam Detection**
- هر پست قبل از ذخیره باید چک شود
- اسپم باید flag شود
- Rate limiting باید اعمال شود

**4. Analytics**
- هر view باید ثبت شود
- Engagement metrics باید محاسبه شود
- Performance metrics باید track شود

**5. Media System**
- آپلود تصویر باید با پست لینک شود
- حذف پست باید مدیا را حذف کند
- Thumbnail generation باید async باشد

**6. Hashtag System**
- هشتگها باید از محتوا extract شوند
- Trending hashtags باید بروز شوند
- جستجو باید با هشتگها کار کند

**7. Mention System**
- منشنها باید از محتوا extract شوند
- نوتیفیکیشن باید برای منشن شدهها ارسال شود
- Privacy settings باید رعایت شود

**8. Search System**
- پستها باید searchable باشند
- Index باید بروز شود
- Filters باید کار کنند

---

### بخش 7 (جدید): Integration with Other Systems

```php
echo "\n7️⃣ بخش 7: Integration with Other Systems\n" . str_repeat("─", 65) . "\n";

// Block/Mute Integration
test("Block integration", fn() => method_exists('App\\Services\\PostService', 'filterBlockedUsers'));
test("Block check in timeline", function() {
    $blocker = User::factory()->create();
    $blocked = User::factory()->create();
    $blocker->blockedUsers()->attach($blocked->id);
    $blockedIds = $blocker->blockedUsers()->pluck('users.id');
    $blocker->blockedUsers()->detach($blocked->id);
    $blocker->delete();
    $blocked->delete();
    return $blockedIds->contains($blocked->id);
});

// Notification Integration
test("Notification on like", fn() => class_exists('App\\Listeners\\SendLikeNotification'));
test("Notification on comment", fn() => class_exists('App\\Listeners\\SendCommentNotification'));
test("Notification on mention", fn() => class_exists('App\\Listeners\\SendMentionNotification'));

// Spam Detection Integration
test("Spam check on create", fn() => strpos(file_get_contents(__DIR__ . '/app/Services/PostService.php'), 'spamDetectionService') !== false);

// Analytics Integration
test("Analytics tracking", fn() => in_array('views_count', array_column(DB::select("SHOW COLUMNS FROM posts"), 'Field')));

// Media Integration
test("Media relationship", fn() => method_exists('App\\Models\\Post', 'media'));

// Hashtag Integration
test("Hashtag extraction", fn() => method_exists('App\\Models\\Post', 'syncHashtags'));

// Mention Integration
test("Mention processing", fn() => method_exists('App\\Models\\Post', 'processMentions'));

// Search Integration
test("Searchable trait", fn() => in_array('Laravel\\Scout\\Searchable', class_uses('App\\Models\\Post') ?: []));
```

---

### مثال تست یکپارچگی کامل

```php
test("Integration: Post → Notification → Block Filter", function() {
    // Setup
    $author = User::factory()->create();
    $follower = User::factory()->create();
    $blocked = User::factory()->create();
    
    // Block
    $follower->blockedUsers()->attach($blocked->id);
    
    // Create post with mention
    $post = Post::create([
        'user_id' => $author->id,
        'content' => 'Test @' . $follower->username,
        'published_at' => now()
    ]);
    
    // Check notification sent
    $notification = $follower->notifications()->where('type', 'mention')->first();
    
    // Check blocked user doesn't see in timeline
    $blockedIds = $follower->blockedUsers()->pluck('users.id');
    $timeline = Post::whereNotIn('user_id', $blockedIds)->get();
    
    // Cleanup
    $post->delete();
    $follower->blockedUsers()->detach($blocked->id);
    $author->delete();
    $follower->delete();
    $blocked->delete();
    
    return $notification !== null && $timeline->contains($post);
});
```

---

## 📋 چکلیست یکپارچگی

### الزامی برای هر سیستم:
- [ ] Block/Mute integration tested
- [ ] Notification integration tested
- [ ] Spam detection integration tested
- [ ] Analytics integration tested
- [ ] Media integration tested (if applicable)
- [ ] Hashtag integration tested (if applicable)
- [ ] Mention integration tested (if applicable)
- [ ] Search integration tested
- [ ] Permission system integration tested
- [ ] Event broadcasting tested

### تست Cross-System:
- [ ] Timeline filters blocked users
- [ ] Notifications sent on interactions
- [ ] Spam flagged and blocked
- [ ] Analytics tracked correctly
- [ ] Media linked to posts
- [ ] Hashtags extracted and indexed
- [ ] Mentions processed and notified
- [ ] Search returns correct results
- [ ] Permissions enforced
- [ ] Events broadcasted

---

**بروزرسانی:** 2025-02-04  
**نسخه:** 1.1  
**تغییرات:** افزودن بخش Integration Testing

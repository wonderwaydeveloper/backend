# 🚀 نقشه راه ارتقاء سیستم Comments به استاندارد Twitter/X

**پروژه:** Clevlance Backend  
**سیستم:** Comments  
**نسخه فعلی:** 1.0 (91.3/100)  
**هدف:** Twitter/X Standard (100/100)  
**تاریخ:** 2025-02-23

---

## 📊 وضعیت فعلی

### نمرات:
- **Architecture:** 19.0/20 (95%) ✅
- **Database:** 14.1/15 (94%) ⚠️
- **API:** 13.4/15 (89%) ⚠️
- **Security:** 19.2/20 (96%) ⚠️ CRITICAL
- **Business Logic:** 9.4/10 (94%) ⚠️
- **Testing:** 4.3/5 (86%) ⚠️

### Twitter Compatibility: 50% (8/16 features)

---

## 🎯 اهداف

1. ✅ رفع 2 مشکل امنیتی CRITICAL
2. ✅ افزودن 8 فیچر Twitter ناقص
3. ✅ تست 6 نقش (user, verified, premium, organization, moderator, admin)
4. ✅ رسیدن به 100% Twitter Compatibility
5. ✅ حفظ معماری موجود
6. ✅ رعایت SYSTEM_REVIEW_CRITERIA.md

---

## ⏱️ زمان‌بندی کلی

| Phase | موضوع | زمان | اولویت |
|-------|-------|------|--------|
| Phase 1 | Security Fixes | 30 دقیقه | 🔴 CRITICAL |
| Phase 2 | Database Schema | 45 دقیقه | 🟡 HIGH |
| Phase 3 | Nested Replies | 1 ساعت | 🟡 HIGH |
| Phase 4 | Edit Comment | 45 دقیقه | 🟡 HIGH |
| Phase 5 | Pin/Hide Features | 1 ساعت | 🟡 HIGH |
| Phase 6 | View Count & Analytics | 45 دقیقه | 🟢 MEDIUM |
| Phase 7 | Soft Delete | 30 دقیقه | 🟢 MEDIUM |
| Phase 8 | Testing 6 Roles | 1.5 ساعت | 🟡 HIGH |
| Phase 9 | Documentation | 30 دقیقه | 🟢 MEDIUM |

**زمان کل:** ~7 ساعت

---

## 📋 Phase 1: Security Fixes (30 دقیقه) 🔴 CRITICAL

### هدف:
رفع 2 مشکل امنیتی CRITICAL که در بررسی یافت شد.

### مشکلات:

#### 1.1 Mass Assignment Vulnerability
**خطر:** کاربر میتواند `user_id` را تغییر دهد و کامنت را به نام دیگری ثبت کند.

**فایل:** `app/Models/Comment.php`

**کد فعلی (خطرناک):**
```php
protected $fillable = [
    'user_id',  // ❌ VULNERABILITY
    'post_id',
    'content',
];
```

**کد جدید (امن):**
```php
protected $fillable = [
    'content',  // فقط content
];

protected $guarded = [
    'id',
    'user_id',
    'post_id', 
    'likes_count',
    'view_count',
    'replies_count',
    'is_pinned',
    'is_hidden',
];
```

**تست:**
```php
// tests/Feature/CommentSystemTest.php
public function test_cannot_mass_assign_user_id()
{
    $response = $this->actingAs($this->user)
        ->postJson("/api/posts/{$this->post->id}/comments", [
            'content' => 'Test',
            'user_id' => 999999,  // تلاش برای تغییر user_id
        ]);

    $comment = Comment::latest()->first();
    $this->assertEquals($this->user->id, $comment->user_id);
    $this->assertNotEquals(999999, $comment->user_id);
}
```

---

#### 1.2 Spam Check Timing
**خطر:** اگر محتوا spam باشد، ابتدا در DB ذخیره میشود و سپس rollback میشود.

**فایل:** `app/Services/CommentService.php`

**کد فعلی (خطرناک):**
```php
// Line 48-60
DB::beginTransaction();
try {
    $comment = $post->comments()->create([...]);  // ❌ ذخیره اول
    
    // Spam check AFTER save
    $spamCheck = $this->spamDetection->checkComment($comment);
    if ($spamCheck['is_spam'] && $spamCheck['score'] >= 80) {
        DB::rollBack();
        throw new \Exception('Content detected as spam');
    }
    
    DB::commit();
    return $comment;
}
```

**کد جدید (امن):**
```php
// Spam check BEFORE save
$spamCheck = $this->spamDetection->checkContent($content);
if ($spamCheck['is_spam'] && $spamCheck['score'] >= 80) {
    throw new \Exception('Content detected as spam');
}

DB::beginTransaction();
try {
    $comment = $post->comments()->create([
        'user_id' => $user->id,
        'content' => $content,
    ]);
    
    // Process mentions
    $mentionedUsers = $comment->processMentions($content);
    
    DB::commit();
    return $comment;
}
```

**تغییرات در SpamDetectionService:**
```php
// app/Services/SpamDetectionService.php

// متد جدید برای چک محتوا قبل از ذخیره
public function checkContent(string $content): array
{
    $score = 0;
    $reasons = [];
    
    // Content analysis
    if ($this->containsSpamKeywords($content)) {
        $score += 40;
        $reasons[] = 'spam_keywords';
    }
    
    if ($this->hasExcessiveLinks($content)) {
        $score += 30;
        $reasons[] = 'excessive_links';
    }
    
    if ($this->isRepeatedContent($content)) {
        $score += 30;
        $reasons[] = 'repeated_content';
    }
    
    return [
        'is_spam' => $score >= 80,
        'score' => $score,
        'reasons' => $reasons,
    ];
}

// متد موجود برای چک بعد از ذخیره (برای آنالیز کامل)
public function checkComment(Comment $comment): array
{
    // شامل user behavior analysis
    // این متد فقط برای لاگ و آنالیز استفاده میشود
}
```

**تست:**
```php
public function test_spam_rejected_before_database_save()
{
    $spamContent = str_repeat('BUY NOW!!! ', 50);
    
    $initialCount = Comment::count();
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/posts/{$this->post->id}/comments", [
            'content' => $spamContent
        ]);

    $response->assertStatus(422);
    $this->assertEquals($initialCount, Comment::count());
}
```

---

#### 1.3 Rate Limiting on Like Endpoint
**خطر:** بدون rate limiting مناسب، امکان abuse وجود دارد.

**فایل:** `routes/api.php`

**کد فعلی:**
```php
Route::post('/comments/{comment}/like', [CommentController::class, 'like'])
    ->middleware('permission:post.like');
```

**کد جدید:**
```php
Route::post('/comments/{comment}/like', [CommentController::class, 'like'])
    ->middleware([
        'permission:post.like',
        'throttle:20,1'  // 20 likes per minute
    ]);
```

**تست:**
```php
public function test_like_rate_limiting()
{
    $comment = Comment::factory()->create(['post_id' => $this->post->id]);
    
    // Try 21 likes in 1 minute
    for ($i = 0; $i < 21; $i++) {
        $response = $this->actingAs($this->user)
            ->postJson("/api/comments/{$comment->id}/like");
        
        if ($i < 20) {
            $response->assertOk();
        } else {
            $response->assertStatus(429);  // Too Many Requests
        }
    }
}
```

---

### Checklist Phase 1:

- [ ] 1.1 رفع Mass Assignment در Comment Model
- [ ] 1.2 افزودن متد checkContent به SpamDetectionService
- [ ] 1.3 جابجایی spam check قبل از DB save
- [ ] 1.4 افزودن rate limiting به like endpoint
- [ ] 1.5 نوشتن 3 تست امنیتی
- [ ] 1.6 اجرای تستها و تایید موفقیت
- [ ] 1.7 Commit: "fix(comments): resolve critical security vulnerabilities"

**زمان:** 30 دقیقه  
**اولویت:** 🔴 CRITICAL  
**نتیجه:** Security Score: 19.2/20 → 20/20

---


## 📋 Phase 2: Database Schema (45 دقیقه) 🟡 HIGH

### هدف:
افزودن ستونهای جدید برای پشتیبانی از فیچرهای Twitter.

### ستونهای جدید:

| ستون | نوع | پیشفرض | توضیح |
|------|-----|--------|-------|
| parent_id | bigint, nullable, FK | null | برای nested replies |
| is_pinned | boolean | false | پین شده توسط نویسنده پست |
| is_hidden | boolean | false | مخفی شده توسط نویسنده پست |
| view_count | int | 0 | تعداد بازدید |
| replies_count | int | 0 | تعداد پاسخها |
| edited_at | timestamp, nullable | null | زمان آخرین ویرایش |
| deleted_at | timestamp, nullable | null | SoftDeletes |

---

### 2.1 ایجاد Migration

**فایل:** `database/migrations/2025_02_23_000001_add_twitter_features_to_comments_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            // Nested Replies
            $table->foreignId('parent_id')
                ->nullable()
                ->after('post_id')
                ->constrained('comments')
                ->cascadeOnDelete();
            
            // Pin & Hide
            $table->boolean('is_pinned')->default(false)->after('content');
            $table->boolean('is_hidden')->default(false)->after('is_pinned');
            
            // Counters
            $table->unsignedInteger('view_count')->default(0)->after('likes_count');
            $table->unsignedInteger('replies_count')->default(0)->after('view_count');
            
            // Edit tracking
            $table->timestamp('edited_at')->nullable()->after('updated_at');
            
            // Soft Deletes
            $table->softDeletes()->after('edited_at');
            
            // Indexes
            $table->index('parent_id', 'comments_parent_index');
            $table->index(['is_pinned', 'is_hidden'], 'comments_visibility_index');
            $table->index('deleted_at', 'comments_deleted_index');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex('comments_parent_index');
            $table->dropIndex('comments_visibility_index');
            $table->dropIndex('comments_deleted_index');
            
            $table->dropColumn([
                'parent_id',
                'is_pinned',
                'is_hidden',
                'view_count',
                'replies_count',
                'edited_at',
                'deleted_at',
            ]);
        });
    }
};
```

---

### 2.2 بروزرسانی Model

**فایل:** `app/Models/Comment.php`

```php
<?php

namespace App\Models;

use App\Traits\Mentionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, Mentionable, SoftDeletes;

    protected $fillable = [
        'content',
    ];

    protected $guarded = [
        'id',
        'user_id',
        'post_id',
        'parent_id',
        'likes_count',
        'view_count',
        'replies_count',
        'is_pinned',
        'is_hidden',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'view_count' => 'integer',
        'replies_count' => 'integer',
        'is_pinned' => 'boolean',
        'is_hidden' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Existing relationships
    public function user()
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'username', 'avatar']);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    // NEW: Nested replies relationships
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('is_hidden', false)
            ->orderBy('created_at', 'asc');
    }

    public function allReplies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Existing methods
    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    // NEW: Helper methods
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    public function isHidden(): bool
    {
        return $this->is_hidden;
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function canBeEdited(): bool
    {
        if (!$this->created_at) return false;
        
        $editTimeout = config('limits.posts.edit_timeout_minutes', 60);
        return $this->created_at->diffInMinutes(now()) < $editTimeout;
    }

    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    // Query Scopes
    public function scopeWithUser($query)
    {
        return $query->with(['user:id,name,username,avatar']);
    }

    public function scopeWithCounts($query)
    {
        return $query->withCount(['likes', 'replies']);
    }

    public function scopeForPost($query, $postId)
    {
        return $query->where('post_id', $postId)
            ->whereNull('parent_id')  // فقط کامنتهای اصلی
            ->where('is_hidden', false)
            ->withUser()
            ->withCounts()
            ->latest();
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    // Mutator for content (existing)
    public function setContentAttribute($value)
    {
        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);
        $value = strip_tags($value);
        $value = trim($value);
        
        if (empty($value)) {
            throw new \InvalidArgumentException('Content cannot be empty');
        }
        
        $maxLength = config('content.validation.content.comment.max_length', 280);
        if (strlen($value) > $maxLength) {
            throw new \InvalidArgumentException("Content exceeds {$maxLength} characters");
        }
        
        $this->attributes['content'] = $value;
    }
}
```

---

### 2.3 بروزرسانی Factory

**فایل:** `database/factories/CommentFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\{Comment, Post, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'parent_id' => null,
            'content' => fake()->sentence(),
            'likes_count' => 0,
            'view_count' => fake()->numberBetween(0, 1000),
            'replies_count' => 0,
            'is_pinned' => false,
            'is_hidden' => false,
            'edited_at' => null,
        ];
    }

    public function reply(?Comment $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Comment::factory(),
            'post_id' => $parent?->post_id ?? Post::factory(),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn (array $attributes) => [
            'edited_at' => now()->subMinutes(fake()->numberBetween(1, 59)),
        ]);
    }
}
```

---

### 2.4 تست Migration

**فایل:** `tests/Feature/CommentDatabaseTest.php`

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommentDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_comments_table_has_all_columns()
    {
        $columns = [
            'id', 'user_id', 'post_id', 'parent_id', 'content',
            'likes_count', 'view_count', 'replies_count',
            'is_pinned', 'is_hidden',
            'created_at', 'updated_at', 'edited_at', 'deleted_at'
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('comments', $column),
                "Column {$column} does not exist"
            );
        }
    }

    public function test_comments_table_has_indexes()
    {
        $indexes = Schema::getIndexes('comments');
        $indexNames = array_column($indexes, 'name');

        $this->assertContains('comments_parent_index', $indexNames);
        $this->assertContains('comments_visibility_index', $indexNames);
        $this->assertContains('comments_deleted_index', $indexNames);
    }

    public function test_parent_id_foreign_key_exists()
    {
        $foreignKeys = Schema::getForeignKeys('comments');
        $foreignKeyColumns = array_column($foreignKeys, 'columns');

        $this->assertContains(['parent_id'], $foreignKeyColumns);
    }
}
```

---

### Checklist Phase 2:

- [ ] 2.1 ایجاد migration با 7 ستون جدید
- [ ] 2.2 افزودن 3 index جدید
- [ ] 2.3 بروزرسانی Model با SoftDeletes
- [ ] 2.4 افزودن relationships جدید (parent, replies)
- [ ] 2.5 افزودن helper methods (isPinned, isHidden, canBeEdited)
- [ ] 2.6 بروزرسانی scopes (visible, pinned)
- [ ] 2.7 بروزرسانی Factory
- [ ] 2.8 نوشتن تستهای database
- [ ] 2.9 اجرای migration: `php artisan migrate`
- [ ] 2.10 اجرای تستها و تایید
- [ ] 2.11 Commit: "feat(comments): add twitter features to database schema"

**زمان:** 45 دقیقه  
**اولویت:** 🟡 HIGH  
**نتیجه:** Database Score: 14.1/15 → 15/15

---


## 📋 Phase 3: Nested Replies (1 ساعت) 🟡 HIGH

### هدف:
پیادهسازی قابلیت پاسخ به کامنت (مانند Twitter threads).

### معماری:
```
Post
├── Comment 1 (parent_id: null)
│   ├── Reply 1.1 (parent_id: comment1.id)
│   ├── Reply 1.2 (parent_id: comment1.id)
│   └── Reply 1.3 (parent_id: comment1.id)
│       └── Reply 1.3.1 (parent_id: reply1.3.id)
└── Comment 2 (parent_id: null)
    └── Reply 2.1 (parent_id: comment2.id)
```

---

### 3.1 بروزرسانی CommentService

**فایل:** `app/Services/CommentService.php`

```php
<?php

namespace App\Services;

use App\Models\{Comment, Post, User};
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function __construct(
        private SpamDetectionService $spamDetection,
        private NotificationService $notificationService
    ) {}

    // Existing method - بروزرسانی شده
    public function createComment(
        Post $post, 
        User $user, 
        string $content, 
        $mediaFile = null,
        ?int $parentId = null  // NEW parameter
    ): Comment {
        // Check if post is draft
        if ($post->is_draft) {
            throw new \Exception('Cannot comment on draft posts');
        }

        // NEW: Validate parent comment
        if ($parentId) {
            $parentComment = Comment::find($parentId);
            
            if (!$parentComment) {
                throw new \Exception('Parent comment not found');
            }
            
            if ($parentComment->post_id !== $post->id) {
                throw new \Exception('Parent comment does not belong to this post');
            }
            
            if ($parentComment->is_hidden) {
                throw new \Exception('Cannot reply to hidden comment');
            }
        }

        // Check reply settings
        if ($post->reply_settings === 'none' && $post->user_id !== $user->id) {
            throw new \Exception('Replies are disabled for this post');
        }

        // Check if user is blocked or muted
        if ($post->user && ($post->user->hasBlocked($user->id) || $post->user->hasMuted($user->id))) {
            throw new \Exception('You cannot comment on this post');
        }

        // Spam check BEFORE save
        $spamCheck = $this->spamDetection->checkContent($content);
        if ($spamCheck['is_spam'] && $spamCheck['score'] >= 80) {
            throw new \Exception('Content detected as spam');
        }

        // Sanitize content
        $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
        $content = strip_tags($content);
        $content = trim($content);
        
        if (empty($content)) {
            throw new \Exception('Content cannot be empty');
        }
        
        if (strlen($content) > config('content.validation.content.comment.max_length')) {
            throw new \Exception('Content too long');
        }

        DB::beginTransaction();
        try {
            // Create comment
            $comment = $post->comments()->create([
                'user_id' => $user->id,
                'content' => $content,
                'parent_id' => $parentId,  // NEW
            ]);
            
            // Handle media
            if ($mediaFile) {
                $media = app(MediaService::class)->uploadImage($mediaFile, $user);
                app(MediaService::class)->attachToModel($media, $comment);
            }

            // NEW: Increment parent replies_count
            if ($parentId) {
                Comment::where('id', $parentId)->increment('replies_count');
            }

            // Process mentions
            $mentionedUsers = $comment->processMentions($content);
            foreach ($mentionedUsers as $mentionedUser) {
                $mentionedUser->notify(new \App\Notifications\MentionNotification($user, $comment));
            }

            DB::commit();

            return $comment->load('media');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // NEW: Get replies for a comment
    public function getCommentReplies(Comment $comment, int $perPage = 20)
    {
        return $comment->replies()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'replies')
            ->paginate($perPage);
    }

    // Existing method - بروزرسانی شده
    public function deleteComment(Comment $comment, User $user): void
    {
        // Authorization check
        if ($comment->user_id !== $user->id && !$user->hasPermissionTo('comment.delete.any')) {
            throw new \Exception('Unauthorized');
        }

        DB::beginTransaction();
        try {
            // NEW: Decrement parent replies_count
            if ($comment->parent_id) {
                Comment::where('id', $comment->parent_id)
                    ->where('replies_count', '>', 0)
                    ->decrement('replies_count');
            }

            // Soft delete (not hard delete)
            $comment->delete();
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Existing method
    public function toggleLike(Comment $comment, User $user): array
    {
        DB::beginTransaction();
        try {
            $existingLike = $comment->likes()->where('user_id', $user->id)->first();

            if ($existingLike) {
                $existingLike->delete();
                if ($comment->likes_count > 0) {
                    $comment->decrement('likes_count');
                }
                $liked = false;
            } else {
                $comment->likes()->create(['user_id' => $user->id]);
                $comment->increment('likes_count');
                $liked = true;
            }

            DB::commit();

            return [
                'liked' => $liked,
                'likes_count' => $comment->fresh()->likes_count
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

---

### 3.2 بروزرسانی CommentController

**فایل:** `app/Http/Controllers/Api/CommentController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Events\{CommentCreated, PostInteraction};
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCommentRequest;
use App\Models\{Comment, Post};
use App\Services\CommentService;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}

    // Existing method
    public function index(Post $post)
    {
        $comments = $post->comments()
            ->whereNull('parent_id')  // فقط کامنتهای اصلی
            ->where('is_hidden', false)
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'replies')
            ->latest()
            ->paginate(config('limits.pagination.comments'));

        return response()->json($comments);
    }

    // NEW: Get replies for a comment
    public function replies(Comment $comment)
    {
        $replies = $this->commentService->getCommentReplies(
            $comment,
            config('limits.pagination.comments', 20)
        );

        return response()->json($replies);
    }

    // Existing method - بروزرسانی شده
    public function store(CreateCommentRequest $request, Post $post)
    {
        $this->authorize('create', Comment::class);

        try {
            $mediaFile = $request->hasFile('media') ? $request->file('media') : null;
            
            $comment = $this->commentService->createComment(
                $post,
                $request->user(),
                $request->input('content'),
                $mediaFile,
                $request->input('parent_id')  // NEW
            );

            // Process mentions
            $comment->processMentions($comment->content);

            // Fire event
            event(new CommentCreated($comment, $request->user()));

            // Broadcast real-time
            broadcast(new PostInteraction($post, 'comment', $request->user(), [
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'parent_id' => $comment->parent_id,  // NEW
                    'user' => $comment->user->only(['id', 'name', 'username', 'avatar']),
                ],
            ]));

            $comment->load('user:id,name,username,avatar', 'media');

            return response()->json($comment, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Existing methods...
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        try {
            $this->commentService->deleteComment($comment, auth()->user());
            return response()->json(['message' => 'Comment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function like(Comment $comment)
    {
        if (!auth()->user()->can('comment.like')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->commentService->toggleLike($comment, auth()->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
```

---

### 3.3 بروزرسانی Routes

**فایل:** `routes/api.php`

```php
// Public Comments Route
Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

Route::middleware(['auth:sanctum', 'security:api'])->group(function () {
    
    // Comments Routes
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware(['permission:comment.create', 'check.reply.permission', 'role.ratelimit', 'throttle:60,1']);
    
    // NEW: Get replies
    Route::get('/comments/{comment}/replies', [CommentController::class, 'replies']);
    
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    
    Route::post('/comments/{comment}/like', [CommentController::class, 'like'])
        ->middleware(['permission:post.like', 'throttle:20,1']);
});
```

---

### 3.4 بروزرسانی CreateCommentRequest

**فایل:** `app/Http/Requests/CreateCommentRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = $this->user();
        $maxFileSizeKB = 5120;
        
        if ($user) {
            $maxFileSize = app(\App\Services\SubscriptionLimitService::class)->getMaxFileSize($user);
            $maxFileSizeKB = $maxFileSize;
        }
        
        return [
            'content' => ['required', new ContentLength('comment')],
            'media' => "nullable|file|mimes:jpeg,jpg,png,gif,webp|max:{$maxFileSizeKB}",
            'gif_url' => 'nullable|url|max:' . config('content.validation.max.token'),
            'parent_id' => 'nullable|integer|exists:comments,id',  // NEW
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
            'parent_id.exists' => 'Parent comment not found',  // NEW
            // ... existing messages
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }
}
```

---

### 3.5 بروزرسانی CommentResource

**فایل:** `app/Http/Resources/CommentResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'parent_id' => $this->parent_id,  // NEW
            'is_reply' => $this->isReply(),  // NEW
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'likes_count' => $this->likes_count,
            'replies_count' => $this->replies_count,  // NEW
            'view_count' => $this->view_count,  // NEW
            'is_liked' => $this->when(auth()->check(), fn () => $this->isLikedBy(auth()->id())),
            'is_pinned' => $this->is_pinned,  // NEW
            'is_hidden' => $this->is_hidden,  // NEW
            'is_edited' => $this->isEdited(),  // NEW
            'can_edit' => $this->when(
                auth()->check() && auth()->id() === $this->user_id,
                fn () => $this->canBeEdited()
            ),
            'user' => new UserResource($this->whenLoaded('user')),
            'parent' => new CommentResource($this->whenLoaded('parent')),  // NEW
            'created_at' => $this->created_at,
            'edited_at' => $this->edited_at,  // NEW
        ];
    }
}
```

---

### 3.6 تستها

**فایل:** `tests/Feature/NestedRepliesTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NestedRepliesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_create_reply_to_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'This is a reply',
                'parent_id' => $comment->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('parent_id', $comment->id);
        
        $this->assertDatabaseHas('comments', [
            'parent_id' => $comment->id,
            'content' => 'This is a reply',
        ]);
    }

    public function test_reply_increments_parent_replies_count()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'replies_count' => 0,
        ]);

        $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Reply',
                'parent_id' => $comment->id,
            ]);

        $this->assertEquals(1, $comment->fresh()->replies_count);
    }

    public function test_can_get_comment_replies()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);
        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id,
            'parent_id' => $comment->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/comments/{$comment->id}/replies");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_reply_to_nonexistent_comment()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Reply',
                'parent_id' => 999999,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_reply_to_hidden_comment()
    {
        $comment = Comment::factory()->hidden()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Reply',
                'parent_id' => $comment->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_delete_reply_decrements_parent_count()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'replies_count' => 1,
        ]);
        
        $reply = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $comment->id,
            'user_id' => $this->user->id,
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/comments/{$reply->id}");

        $this->assertEquals(0, $comment->fresh()->replies_count);
    }
}
```

---

### Checklist Phase 3:

- [ ] 3.1 بروزرسانی createComment با parent_id
- [ ] 3.2 افزودن getCommentReplies به Service
- [ ] 3.3 بروزرسانی deleteComment برای decrement
- [ ] 3.4 افزودن replies method به Controller
- [ ] 3.5 افزودن route جدید GET /comments/{comment}/replies
- [ ] 3.6 بروزرسانی CreateCommentRequest
- [ ] 3.7 بروزرسانی CommentResource
- [ ] 3.8 نوشتن 6 تست nested replies
- [ ] 3.9 اجرای تستها و تایید
- [ ] 3.10 Commit: "feat(comments): implement nested replies"

**زمان:** 1 ساعت  
**اولویت:** 🟡 HIGH  
**نتیجه:** API Score: 13.4/15 → 14.5/15

---


## 📋 Phase 4: Edit Comment (45 دقیقه) 🟡 HIGH

### هدف:
امکان ویرایش کامنت تا 1 ساعت بعد از ایجاد (مانند Twitter).

---

### 4.1 ایجاد UpdateCommentRequest

**فایل:** `app/Http/Requests/UpdateCommentRequest.php`

```php
<?php

namespace App\Http\Requests;

use App\Rules\ContentLength;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $comment = $this->route('comment');
        
        // فقط صاحب کامنت یا admin
        return $this->user()->id === $comment->user_id 
            || $this->user()->hasPermissionTo('comment.edit.any');
    }

    public function rules(): array
    {
        return [
            'content' => ['required', new ContentLength('comment')],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }
}
```

---

### 4.2 افزودن متد update به CommentService

**فایل:** `app/Services/CommentService.php`

```php
// Add this method to CommentService class

public function updateComment(Comment $comment, User $user, string $content): Comment
{
    // Check if can be edited (within 1 hour)
    if (!$comment->canBeEdited()) {
        throw new \Exception('Comment can no longer be edited');
    }

    // Authorization
    if ($comment->user_id !== $user->id && !$user->hasPermissionTo('comment.edit.any')) {
        throw new \Exception('Unauthorized');
    }

    // Spam check
    $spamCheck = $this->spamDetection->checkContent($content);
    if ($spamCheck['is_spam'] && $spamCheck['score'] >= 80) {
        throw new \Exception('Content detected as spam');
    }

    // Sanitize
    $content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $content);
    $content = strip_tags($content);
    $content = trim($content);
    
    if (empty($content)) {
        throw new \Exception('Content cannot be empty');
    }

    DB::beginTransaction();
    try {
        $comment->update([
            'content' => $content,
            'edited_at' => now(),
        ]);

        // Process mentions
        $comment->processMentions($content);

        DB::commit();

        return $comment->fresh();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

---

### 4.3 افزودن متد update به CommentController

**فایل:** `app/Http/Controllers/Api/CommentController.php`

```php
use App\Http\Requests\UpdateCommentRequest;

// Add this method to CommentController class

public function update(UpdateCommentRequest $request, Comment $comment)
{
    try {
        $updatedComment = $this->commentService->updateComment(
            $comment,
            $request->user(),
            $request->input('content')
        );

        $updatedComment->load('user:id,name,username,avatar', 'media');

        return response()->json($updatedComment);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}
```

---

### 4.4 افزودن Route

**فایل:** `routes/api.php`

```php
Route::middleware(['auth:sanctum', 'security:api'])->group(function () {
    
    // NEW: Update comment
    Route::put('/comments/{comment}', [CommentController::class, 'update'])
        ->middleware('permission:comment.edit.own');
    
    // ... existing routes
});
```

---

### 4.5 افزودن Permissions

**فایل:** `database/seeders/PermissionSeeder.php`

```php
// Add to permissions array
Permission::firstOrCreate(['name' => 'comment.edit.own', 'guard_name' => 'sanctum']);
Permission::firstOrCreate(['name' => 'comment.edit.any', 'guard_name' => 'sanctum']);

// Add to user role
$userRole->givePermissionTo('comment.edit.own');

// Add to moderator role
$moderatorRole->givePermissionTo('comment.edit.own', 'comment.edit.any');

// Add to admin role
$adminRole->givePermissionTo('comment.edit.own', 'comment.edit.any');
```

---

### 4.6 تستها

**فایل:** `tests/Feature/EditCommentTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_edit_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated content'
            ]);

        $response->assertOk();
        $this->assertEquals('Updated content', $comment->fresh()->content);
        $this->assertNotNull($comment->fresh()->edited_at);
    }

    public function test_cannot_edit_others_comment()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Hacked'
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_edit_after_timeout()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subHours(2),  // 2 hours ago
        ]);

        $response = $this->actingAs($user)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Too late'
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Comment can no longer be edited']);
    }

    public function test_edit_updates_edited_at_timestamp()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertNull($comment->edited_at);

        $this->actingAs($user)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated'
            ]);

        $this->assertNotNull($comment->fresh()->edited_at);
    }
}
```

---

### Checklist Phase 4:

- [ ] 4.1 ایجاد UpdateCommentRequest
- [ ] 4.2 افزودن updateComment به Service
- [ ] 4.3 افزودن update method به Controller
- [ ] 4.4 افزودن route PUT /comments/{comment}
- [ ] 4.5 افزودن 2 permission جدید
- [ ] 4.6 بروزرسانی PermissionSeeder
- [ ] 4.7 نوشتن 4 تست edit
- [ ] 4.8 اجرای تستها و تایید
- [ ] 4.9 Commit: "feat(comments): implement edit functionality"

**زمان:** 45 دقیقه  
**اولویت:** 🟡 HIGH  
**نتیجه:** Business Logic Score: 9.4/10 → 10/10

---

## 📋 Phase 5: Pin & Hide Features (1 ساعت) 🟡 HIGH

### هدف:
- Pin: نویسنده پست میتواند 1 کامنت را پین کند
- Hide: نویسنده پست میتواند کامنت را مخفی کند

---

### 5.1 افزودن متدها به CommentService

**فایل:** `app/Services/CommentService.php`

```php
// Add these methods to CommentService class

public function pinComment(Comment $comment, User $user): Comment
{
    // فقط نویسنده پست میتواند پین کند
    if ($comment->post->user_id !== $user->id) {
        throw new \Exception('Only post author can pin comments');
    }

    // چک کردن اینکه آیا کامنت دیگری پین شده
    $pinnedComment = Comment::where('post_id', $comment->post_id)
        ->where('is_pinned', true)
        ->first();

    DB::beginTransaction();
    try {
        // Unpin previous pinned comment
        if ($pinnedComment && $pinnedComment->id !== $comment->id) {
            $pinnedComment->update(['is_pinned' => false]);
        }

        // Pin this comment
        $comment->update(['is_pinned' => true]);

        DB::commit();

        return $comment->fresh();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

public function unpinComment(Comment $comment, User $user): Comment
{
    if ($comment->post->user_id !== $user->id) {
        throw new \Exception('Only post author can unpin comments');
    }

    $comment->update(['is_pinned' => false]);

    return $comment->fresh();
}

public function hideComment(Comment $comment, User $user): Comment
{
    // فقط نویسنده پست میتواند مخفی کند
    if ($comment->post->user_id !== $user->id && !$user->hasPermissionTo('comment.hide')) {
        throw new \Exception('Only post author can hide comments');
    }

    $comment->update(['is_hidden' => true]);

    return $comment->fresh();
}

public function unhideComment(Comment $comment, User $user): Comment
{
    if ($comment->post->user_id !== $user->id && !$user->hasPermissionTo('comment.hide')) {
        throw new \Exception('Only post author can unhide comments');
    }

    $comment->update(['is_hidden' => false]);

    return $comment->fresh();
}
```

---

### 5.2 افزودن متدها به CommentController

**فایل:** `app/Http/Controllers/Api/CommentController.php`

```php
// Add these methods to CommentController class

public function pin(Comment $comment)
{
    try {
        $pinnedComment = $this->commentService->pinComment($comment, auth()->user());
        return response()->json($pinnedComment);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}

public function unpin(Comment $comment)
{
    try {
        $unpinnedComment = $this->commentService->unpinComment($comment, auth()->user());
        return response()->json($unpinnedComment);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}

public function hide(Comment $comment)
{
    try {
        $hiddenComment = $this->commentService->hideComment($comment, auth()->user());
        return response()->json($hiddenComment);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}

public function unhide(Comment $comment)
{
    try {
        $unhiddenComment = $this->commentService->unhideComment($comment, auth()->user());
        return response()->json($unhiddenComment);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}
```

---

### 5.3 افزودن Routes

**فایل:** `routes/api.php`

```php
Route::middleware(['auth:sanctum', 'security:api'])->group(function () {
    
    // Pin/Unpin
    Route::post('/comments/{comment}/pin', [CommentController::class, 'pin'])
        ->middleware('permission:comment.pin');
    Route::delete('/comments/{comment}/pin', [CommentController::class, 'unpin'])
        ->middleware('permission:comment.pin');
    
    // Hide/Unhide
    Route::post('/comments/{comment}/hide', [CommentController::class, 'hide'])
        ->middleware('permission:comment.hide');
    Route::post('/comments/{comment}/unhide', [CommentController::class, 'unhide'])
        ->middleware('permission:comment.hide');
    
    // ... existing routes
});
```

---

### 5.4 افزودن Permissions

**فایل:** `database/seeders/PermissionSeeder.php`

```php
// Add to permissions
Permission::firstOrCreate(['name' => 'comment.pin', 'guard_name' => 'sanctum']);
Permission::firstOrCreate(['name' => 'comment.hide', 'guard_name' => 'sanctum']);

// همه نقشها میتوانند pin/hide کنند (فقط روی پستهای خودشان)
$userRole->givePermissionTo('comment.pin', 'comment.hide');
$verifiedRole->givePermissionTo('comment.pin', 'comment.hide');
$premiumRole->givePermissionTo('comment.pin', 'comment.hide');
$organizationRole->givePermissionTo('comment.pin', 'comment.hide');
$moderatorRole->givePermissionTo('comment.pin', 'comment.hide');
$adminRole->givePermissionTo('comment.pin', 'comment.hide');
```

---

### 5.5 بروزرسانی CommentPolicy

**فایل:** `app/Policies/CommentPolicy.php`

```php
// Add these methods to CommentPolicy class

public function pin(User $user, Comment $comment): bool
{
    // فقط نویسنده پست
    return $user->id === $comment->post->user_id && $user->can('comment.pin');
}

public function hide(User $user, Comment $comment): bool
{
    // نویسنده پست یا moderator/admin
    return ($user->id === $comment->post->user_id || $user->hasPermissionTo('comment.hide')) 
        && $user->can('comment.hide');
}
```

---

### 5.6 تستها

**فایل:** `tests/Feature/PinHideCommentTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinHideCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_author_can_pin_comment()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($author)
            ->postJson("/api/comments/{$comment->id}/pin");

        $response->assertOk();
        $this->assertTrue($comment->fresh()->is_pinned);
    }

    public function test_non_author_cannot_pin_comment()
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($other)
            ->postJson("/api/comments/{$comment->id}/pin");

        $response->assertStatus(422);
        $this->assertFalse($comment->fresh()->is_pinned);
    }

    public function test_only_one_comment_can_be_pinned()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment1 = Comment::factory()->create(['post_id' => $post->id]);
        $comment2 = Comment::factory()->create(['post_id' => $post->id]);

        // Pin first
        $this->actingAs($author)->postJson("/api/comments/{$comment1->id}/pin");
        $this->assertTrue($comment1->fresh()->is_pinned);

        // Pin second (should unpin first)
        $this->actingAs($author)->postJson("/api/comments/{$comment2->id}/pin");
        $this->assertFalse($comment1->fresh()->is_pinned);
        $this->assertTrue($comment2->fresh()->is_pinned);
    }

    public function test_post_author_can_hide_comment()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($author)
            ->postJson("/api/comments/{$comment->id}/hide");

        $response->assertOk();
        $this->assertTrue($comment->fresh()->is_hidden);
    }

    public function test_hidden_comments_not_in_list()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->hidden()->create(['post_id' => $post->id]);

        $response = $this->actingAs($author)
            ->getJson("/api/posts/{$post->id}/comments");

        $response->assertOk()
            ->assertJsonMissing(['id' => $comment->id]);
    }

    public function test_can_unhide_comment()
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->hidden()->create(['post_id' => $post->id]);

        $response = $this->actingAs($author)
            ->postJson("/api/comments/{$comment->id}/unhide");

        $response->assertOk();
        $this->assertFalse($comment->fresh()->is_hidden);
    }
}
```

---

### Checklist Phase 5:

- [ ] 5.1 افزودن 4 متد به Service (pin, unpin, hide, unhide)
- [ ] 5.2 افزودن 4 متد به Controller
- [ ] 5.3 افزودن 4 route جدید
- [ ] 5.4 افزودن 2 permission جدید
- [ ] 5.5 بروزرسانی CommentPolicy
- [ ] 5.6 نوشتن 6 تست pin/hide
- [ ] 5.7 اجرای تستها و تایید
- [ ] 5.8 Commit: "feat(comments): implement pin and hide features"

**زمان:** 1 ساعت  
**اولویت:** 🟡 HIGH  
**نتیجه:** API Score: 14.5/15 → 15/15

---


## 📋 Phase 6: View Count & Analytics (45 دقیقه) 🟢 MEDIUM

### هدف:
ثبت و نمایش تعداد بازدید کامنتها + Integration با Analytics.

---

### 6.1 ایجاد Event

**فایل:** `app/Events/CommentViewed.php`

```php
<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Comment $comment,
        public ?int $userId = null
    ) {}
}
```

---

### 6.2 ایجاد Listener

**فایل:** `app/Listeners/TrackCommentView.php`

```php
<?php

namespace App\Listeners;

use App\Events\CommentViewed;
use Illuminate\Contracts\Queue\ShouldQueue;

class TrackCommentView implements ShouldQueue
{
    public function handle(CommentViewed $event): void
    {
        // Increment view count
        $event->comment->increment('view_count');

        // Track in analytics (if user is logged in)
        if ($event->userId) {
            app(\App\Services\AnalyticsService::class)->trackCommentView(
                $event->comment,
                $event->userId
            );
        }
    }
}
```

---

### 6.3 ثبت Listener

**فایل:** `app/Providers/EventServiceProvider.php`

```php
use App\Events\CommentViewed;
use App\Listeners\TrackCommentView;

protected $listen = [
    // ... existing events
    
    CommentViewed::class => [
        TrackCommentView::class,
    ],
];
```

---

### 6.4 بروزرسانی CommentController

**فایل:** `app/Http/Controllers/Api/CommentController.php`

```php
use App\Events\CommentViewed;

// بروزرسانی متد index
public function index(Post $post)
{
    $comments = $post->comments()
        ->whereNull('parent_id')
        ->where('is_hidden', false)
        ->with('user:id,name,username,avatar')
        ->withCount('likes', 'replies')
        ->latest()
        ->paginate(config('limits.pagination.comments'));

    // Track views for each comment
    foreach ($comments as $comment) {
        event(new CommentViewed($comment, auth()->id()));
    }

    return response()->json($comments);
}

// بروزرسانی متد replies
public function replies(Comment $comment)
{
    $replies = $this->commentService->getCommentReplies(
        $comment,
        config('limits.pagination.comments', 20)
    );

    // Track views
    foreach ($replies as $reply) {
        event(new CommentViewed($reply, auth()->id()));
    }

    return response()->json($replies);
}
```

---

### 6.5 افزودن متد به AnalyticsService

**فایل:** `app/Services/AnalyticsService.php`

```php
// Add this method to AnalyticsService class

public function trackCommentView(Comment $comment, int $userId): void
{
    AnalyticsEvent::create([
        'user_id' => $userId,
        'event_type' => 'comment_view',
        'event_data' => [
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
            'comment_author_id' => $comment->user_id,
        ],
    ]);
}

public function getCommentAnalytics(Comment $comment): array
{
    return [
        'views' => $comment->view_count,
        'likes' => $comment->likes_count,
        'replies' => $comment->replies_count,
        'engagement_rate' => $this->calculateCommentEngagement($comment),
    ];
}

private function calculateCommentEngagement(Comment $comment): float
{
    if ($comment->view_count === 0) return 0;
    
    $engagements = $comment->likes_count + $comment->replies_count;
    return round(($engagements / $comment->view_count) * 100, 2);
}
```

---

### 6.6 تستها

**فایل:** `tests/Feature/CommentViewCountTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentViewCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_comments_increments_view_count()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'view_count' => 0,
        ]);

        $this->actingAs($user)
            ->getJson("/api/posts/{$post->id}/comments");

        $this->assertGreaterThan(0, $comment->fresh()->view_count);
    }

    public function test_view_count_tracked_in_analytics()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->actingAs($user)
            ->getJson("/api/posts/{$post->id}/comments");

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_type' => 'comment_view',
        ]);
    }
}
```

---

### Checklist Phase 6:

- [ ] 6.1 ایجاد CommentViewed Event
- [ ] 6.2 ایجاد TrackCommentView Listener
- [ ] 6.3 ثبت Listener در EventServiceProvider
- [ ] 6.4 بروزرسانی Controller برای track views
- [ ] 6.5 افزودن متدهای analytics
- [ ] 6.6 نوشتن 2 تست view count
- [ ] 6.7 اجرای تستها و تایید
- [ ] 6.8 Commit: "feat(comments): implement view count and analytics"

**زمان:** 45 دقیقه  
**اولویت:** 🟢 MEDIUM  
**نتیجه:** Integration Score: 4.6/5 → 5/5

---

## 📋 Phase 7: Soft Delete (30 دقیقه) 🟢 MEDIUM

### هدف:
حذف نرم کامنتها برای امکان بازیابی (مانند Twitter).

---

### 7.1 بروزرسانی CommentObserver

**فایل:** `app/Observers/CommentObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Comment;
use App\Events\{CommentCreated, CommentDeleted};

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $comment->post()->increment('comments_count');
        event(new CommentCreated($comment, $comment->user));
    }

    public function deleted(Comment $comment): void
    {
        if ($comment->post && $comment->post->comments_count > 0) {
            $comment->post()->decrement('comments_count');
        }
        event(new CommentDeleted($comment));
    }

    // NEW: Handle restore
    public function restored(Comment $comment): void
    {
        $comment->post()->increment('comments_count');
    }

    // NEW: Handle force delete
    public function forceDeleted(Comment $comment): void
    {
        // Delete all replies
        $comment->allReplies()->forceDelete();
        
        // Delete all likes
        $comment->likes()->delete();
        
        // Delete all media
        $comment->media()->delete();
    }
}
```

---

### 7.2 افزودن متدهای restore به Service

**فایل:** `app/Services/CommentService.php`

```php
// Add these methods to CommentService class

public function restoreComment(int $commentId, User $user): Comment
{
    $comment = Comment::withTrashed()->findOrFail($commentId);

    // Authorization
    if ($comment->user_id !== $user->id && !$user->hasPermissionTo('comment.restore')) {
        throw new \Exception('Unauthorized');
    }

    $comment->restore();

    return $comment->fresh();
}

public function forceDeleteComment(Comment $comment, User $user): void
{
    // فقط admin میتواند force delete کند
    if (!$user->hasPermissionTo('comment.force.delete')) {
        throw new \Exception('Unauthorized');
    }

    $comment->forceDelete();
}
```

---

### 7.3 افزودن متدها به Controller

**فایل:** `app/Http/Controllers/Api/CommentController.php`

```php
// Add these methods to CommentController class

public function restore(int $id)
{
    try {
        $comment = $this->commentService->restoreComment($id, auth()->user());
        return response()->json($comment);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}

public function forceDelete(Comment $comment)
{
    $this->authorize('forceDelete', $comment);

    try {
        $this->commentService->forceDeleteComment($comment, auth()->user());
        return response()->json(['message' => 'Comment permanently deleted']);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}
```

---

### 7.4 افزودن Routes

**فایل:** `routes/api.php`

```php
Route::middleware(['auth:sanctum', 'security:api'])->group(function () {
    
    // Restore deleted comment
    Route::post('/comments/{id}/restore', [CommentController::class, 'restore'])
        ->middleware('permission:comment.restore');
    
    // Force delete (admin only)
    Route::delete('/comments/{comment}/force', [CommentController::class, 'forceDelete'])
        ->middleware('role:admin');
    
    // ... existing routes
});
```

---

### 7.5 افزودن Permissions

**فایل:** `database/seeders/PermissionSeeder.php`

```php
Permission::firstOrCreate(['name' => 'comment.restore', 'guard_name' => 'sanctum']);
Permission::firstOrCreate(['name' => 'comment.force.delete', 'guard_name' => 'sanctum']);

// Users can restore their own
$userRole->givePermissionTo('comment.restore');

// Admin can force delete
$adminRole->givePermissionTo('comment.restore', 'comment.force.delete');
```

---

### 7.6 بروزرسانی CommentPolicy

**فایل:** `app/Policies/CommentPolicy.php`

```php
public function restore(User $user, Comment $comment): bool
{
    return $user->id === $comment->user_id && $user->can('comment.restore');
}

public function forceDelete(User $user, Comment $comment): bool
{
    return $user->hasPermissionTo('comment.force.delete');
}
```

---

### 7.7 تستها

**فایل:** `tests/Feature/SoftDeleteCommentTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_comment_is_soft_deleted()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_can_restore_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);
        $comment->delete();

        $response = $this->actingAs($user)
            ->postJson("/api/comments/{$comment->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'deleted_at' => null,
        ]);
    }

    public function test_soft_deleted_comments_not_in_list()
    {
        $user = User::factory()->create();
        $post = \App\Models\Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $comment->delete();

        $response = $this->actingAs($user)
            ->getJson("/api/posts/{$post->id}/comments");

        $response->assertOk()
            ->assertJsonMissing(['id' => $comment->id]);
    }
}
```

---

### Checklist Phase 7:

- [ ] 7.1 بروزرسانی CommentObserver (restored, forceDeleted)
- [ ] 7.2 افزودن restoreComment و forceDeleteComment به Service
- [ ] 7.3 افزودن restore و forceDelete به Controller
- [ ] 7.4 افزودن 2 route جدید
- [ ] 7.5 افزودن 2 permission جدید
- [ ] 7.6 بروزرسانی CommentPolicy
- [ ] 7.7 نوشتن 3 تست soft delete
- [ ] 7.8 اجرای تستها و تایید
- [ ] 7.9 Commit: "feat(comments): implement soft delete"

**زمان:** 30 دقیقه  
**اولویت:** 🟢 MEDIUM  
**نتیجه:** Business Logic Score: 10/10 (maintained)

---

## 📋 Phase 8: Testing 6 Roles (1.5 ساعت) 🟡 HIGH

### هدف:
تست تمام 6 نقش در Script Tests و Feature Tests.

### نقشها:
1. user
2. verified
3. premium
4. organization
5. moderator
6. admin

---

### 8.1 بروزرسانی Script Test

**فایل:** `test-scripts/05_comments.php`

```php
// بخش 4: Security - افزودن تستهای 6 نقش

// Section 4: Security & Authorization
$s4 = section("4️⃣ Security", 20);

// ... existing tests

// NEW: Test all 6 roles
test("Role user has comment.create", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'user')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.create');
});

test("Role verified has comment.create", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'verified')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.create');
});

test("Role premium has comment.create", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'premium')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.create');
});

test("Role organization has comment.create", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'organization')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.create');
});

test("Role moderator has comment.create", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'moderator')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.create');
});

test("Role admin has comment.create", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.create');
});

// Test delete.any permission (only moderator and admin)
test("Role user does NOT have comment.delete.any", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'user')->where('guard_name', 'sanctum')->first();
    return $role && !$role->hasPermissionTo('comment.delete.any');
});

test("Role moderator has comment.delete.any", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'moderator')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.delete.any');
});

test("Role admin has comment.delete.any", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.delete.any');
});

// Test edit.any permission
test("Role moderator has comment.edit.any", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'moderator')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.edit.any');
});

test("Role admin has comment.edit.any", function() {
    $role = \Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
    return $role && $role->hasPermissionTo('comment.edit.any');
});

endSection($s4);
```

---

### 8.2 ایجاد Feature Test برای 6 نقش

**فایل:** `tests/Feature/CommentRolesTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        $permissions = [
            'comment.create', 'comment.delete.own', 'comment.delete.any',
            'comment.edit.own', 'comment.edit.any', 'comment.like',
            'comment.pin', 'comment.hide', 'comment.restore'
        ];
        
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum'
            ]);
        }
    }

    // ==================== USER ROLE ====================
    
    public function test_user_role_can_create_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'User comment'
            ]);

        $response->assertCreated();
    }

    public function test_user_role_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertOk();
    }

    public function test_user_role_cannot_delete_others_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $other = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertForbidden();
    }

    // ==================== VERIFIED ROLE ====================
    
    public function test_verified_role_can_create_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('verified');
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Verified comment'
            ]);

        $response->assertCreated();
    }

    // ==================== PREMIUM ROLE ====================
    
    public function test_premium_role_can_create_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('premium');
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Premium comment'
            ]);

        $response->assertCreated();
    }

    // ==================== ORGANIZATION ROLE ====================
    
    public function test_organization_role_can_create_comment()
    {
        $user = User::factory()->create();
        $user->assignRole('organization');
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Organization comment'
            ]);

        $response->assertCreated();
    }

    // ==================== MODERATOR ROLE ====================
    
    public function test_moderator_role_can_delete_any_comment()
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');
        $other = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($moderator)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertOk();
    }

    public function test_moderator_role_can_edit_any_comment()
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');
        $other = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($moderator)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Moderated content'
            ]);

        $response->assertOk();
    }

    // ==================== ADMIN ROLE ====================
    
    public function test_admin_role_can_delete_any_comment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $other = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertOk();
    }

    public function test_admin_role_can_force_delete_comment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $comment = Comment::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/api/comments/{$comment->id}/force");

        $response->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    // ==================== ROLE COMPARISON ====================
    
    public function test_all_roles_can_create_comments()
    {
        $roles = ['user', 'verified', 'premium', 'organization', 'moderator', 'admin'];
        $post = Post::factory()->create();

        foreach ($roles as $roleName) {
            $user = User::factory()->create();
            $user->assignRole($roleName);

            $response = $this->actingAs($user)
                ->postJson("/api/posts/{$post->id}/comments", [
                    'content' => "{$roleName} comment"
                ]);

            $response->assertCreated();
        }
    }

    public function test_only_moderator_and_admin_can_delete_any()
    {
        $other = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $other->id]);

        // User cannot
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user)
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertForbidden();

        // Moderator can
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');
        $this->actingAs($moderator)
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertOk();
    }
}
```

---

### Checklist Phase 8:

- [ ] 8.1 افزودن 11 تست نقش به Script Test
- [ ] 8.2 ایجاد CommentRolesTest با 15+ تست
- [ ] 8.3 تست create برای 6 نقش
- [ ] 8.4 تست delete.own برای همه نقشها
- [ ] 8.5 تست delete.any فقط برای moderator/admin
- [ ] 8.6 تست edit.any فقط برای moderator/admin
- [ ] 8.7 تست force.delete فقط برای admin
- [ ] 8.8 اجرای تمام تستها و تایید
- [ ] 8.9 Commit: "test(comments): add comprehensive role-based tests"

**زمان:** 1.5 ساعت  
**اولویت:** 🟡 HIGH  
**نتیجه:** Testing Score: 4.3/5 → 5/5

---


## 📋 Phase 9: Documentation (30 دقیقه) 🟢 MEDIUM

### هدف:
بروزرسانی مستندات برای فیچرهای جدید.

---

### 9.1 بروزرسانی API.md

**فایل:** `API.md`

```markdown
## 5. Comments 💬

### Endpoints (10):

#### Get Comments
GET /api/posts/{post}/comments
- Public access
- Returns: Paginated list of top-level comments (parent_id = null)
- Excludes hidden comments
- Includes: user, likes_count, replies_count, view_count

#### Get Replies
GET /api/comments/{comment}/replies
- Auth required
- Returns: Paginated list of replies for a comment
- Includes: user, likes_count, view_count

#### Create Comment
POST /api/posts/{post}/comments
- Auth required
- Permission: comment.create
- Body: { content, media?, parent_id? }
- Rate limit: 60/minute
- Returns: Created comment with user and media

#### Update Comment
PUT /api/comments/{comment}
- Auth required
- Permission: comment.edit.own
- Body: { content }
- Can edit within 1 hour of creation
- Returns: Updated comment with edited_at timestamp

#### Delete Comment
DELETE /api/comments/{comment}
- Auth required
- Soft delete
- Permission: comment.delete.own or comment.delete.any
- Returns: Success message

#### Restore Comment
POST /api/comments/{id}/restore
- Auth required
- Permission: comment.restore
- Restores soft-deleted comment
- Returns: Restored comment

#### Force Delete Comment
DELETE /api/comments/{comment}/force
- Auth required
- Role: admin only
- Permanently deletes comment
- Returns: Success message

#### Like Comment
POST /api/comments/{comment}/like
- Auth required
- Permission: comment.like
- Rate limit: 20/minute
- Toggle like/unlike
- Returns: { liked: boolean, likes_count: number }

#### Pin Comment
POST /api/comments/{comment}/pin
- Auth required
- Permission: comment.pin
- Only post author can pin
- Max 1 pinned comment per post
- Returns: Pinned comment

DELETE /api/comments/{comment}/pin
- Unpin comment

#### Hide Comment
POST /api/comments/{comment}/hide
- Auth required
- Permission: comment.hide
- Only post author or moderator can hide
- Returns: Hidden comment

POST /api/comments/{comment}/unhide
- Unhide comment

### Features:
- ✅ Nested replies (unlimited depth)
- ✅ Edit within 1 hour
- ✅ Pin comment (1 per post)
- ✅ Hide comment
- ✅ View count tracking
- ✅ Soft delete
- ✅ Real-time updates
- ✅ Spam detection
- ✅ Mention support
- ✅ Media upload
- ✅ Block/Mute integration
- ✅ Reply settings (everyone, following, mentioned, none)

### Permissions:
- comment.create (all roles)
- comment.delete.own (all roles)
- comment.delete.any (moderator, admin)
- comment.edit.own (all roles)
- comment.edit.any (moderator, admin)
- comment.like (all roles)
- comment.pin (all roles - own posts only)
- comment.hide (all roles - own posts only, moderator/admin any)
- comment.restore (all roles - own comments, admin any)
- comment.force.delete (admin only)
```

---

### 9.2 بروزرسانی SYSTEMS_LIST.md

**فایل:** `test-scripts/docs/SYSTEMS_LIST.md`

```markdown
## 5. Comments 💬

### Controller
- `CommentController`

### Features (10 endpoints):
- GET `/posts/{post}/comments` - لیست کامنتها
- GET `/comments/{comment}/replies` - لیست پاسخها (NEW)
- POST `/posts/{post}/comments` - ایجاد کامنت
- PUT `/comments/{comment}` - ویرایش کامنت (NEW)
- DELETE `/comments/{comment}` - حذف کامنت (soft delete)
- POST `/comments/{id}/restore` - بازیابی کامنت (NEW)
- DELETE `/comments/{comment}/force` - حذف دائمی (NEW)
- POST `/comments/{comment}/like` - لایک کامنت
- POST `/comments/{comment}/pin` - پین کامنت (NEW)
- POST `/comments/{comment}/hide` - مخفی کردن کامنت (NEW)

### Twitter Features:
- ✅ Nested Replies (parent_id)
- ✅ Edit Comment (1 hour limit)
- ✅ Pin Comment (1 per post)
- ✅ Hide Comment
- ✅ View Count
- ✅ Soft Delete
- ✅ Analytics Integration

### Database Schema:
- id, user_id, post_id, parent_id
- content, likes_count, view_count, replies_count
- is_pinned, is_hidden
- created_at, updated_at, edited_at, deleted_at

### Permissions (10):
- comment.create
- comment.delete.own
- comment.delete.any
- comment.edit.own
- comment.edit.any
- comment.like
- comment.pin
- comment.hide
- comment.restore
- comment.force.delete
```

---

### 9.3 ایجاد COMMENT_FEATURES.md

**فایل:** `docs/COMMENT_FEATURES.md`

```markdown
# Comment System Features

## Overview
سیستم Comments با استاندارد Twitter/X طراحی شده است.

## Core Features

### 1. Nested Replies
- پاسخ به کامنت (unlimited depth)
- هر reply میتواند reply داشته باشد
- parent_id برای ساختار درختی
- replies_count برای هر کامنت

### 2. Edit Comment
- ویرایش تا 1 ساعت بعد از ایجاد
- edited_at timestamp
- فقط صاحب کامنت یا admin
- Spam check قبل از ذخیره

### 3. Pin Comment
- نویسنده پست میتواند 1 کامنت را پین کند
- فقط 1 کامنت پین در هر پست
- is_pinned flag
- Unpin قبلی به صورت خودکار

### 4. Hide Comment
- نویسنده پست میتواند کامنت را مخفی کند
- Moderator/Admin میتوانند هر کامنتی را مخفی کنند
- is_hidden flag
- کامنتهای مخفی در لیست نمایش داده نمیشوند

### 5. View Count
- ثبت تعداد بازدید
- Integration با Analytics
- Event-driven (CommentViewed)
- Queue processing

### 6. Soft Delete
- حذف نرم با امکان بازیابی
- deleted_at timestamp
- Restore برای صاحب کامنت
- Force delete فقط برای admin

## Security

### 8 Layers:
1. Authentication (Sanctum)
2. Authorization (Policies)
3. Permissions (Spatie)
4. Roles (6 roles)
5. XSS Protection
6. SQL Injection Protection
7. CSRF Protection
8. Rate Limiting

### Mass Assignment Protection:
- فقط content در fillable
- user_id, post_id, counters در guarded

### Spam Detection:
- Check قبل از DB save
- Content analysis
- User behavior analysis

## Integration

### Block/Mute:
- چک قبل از create
- Blocked users نمیتوانند کامنت بگذارند

### Reply Settings:
- everyone: همه میتوانند reply کنند
- following: فقط فالوورها
- mentioned: فقط mention شدهها
- none: هیچکس (فقط صاحب پست)

### Notifications:
- CommentCreated → SendCommentNotification
- Mention → MentionNotification

### Real-time:
- Broadcasting via WebSocket
- PostInteraction event

### Analytics:
- View count tracking
- Engagement rate calculation
- AnalyticsEvent storage

## Permissions Matrix

| Permission | user | verified | premium | org | mod | admin |
|------------|------|----------|---------|-----|-----|-------|
| create | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| delete.own | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| delete.any | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| edit.own | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| edit.any | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| like | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| pin | ✅* | ✅* | ✅* | ✅* | ✅* | ✅* |
| hide | ✅* | ✅* | ✅* | ✅* | ✅ | ✅ |
| restore | ✅* | ✅* | ✅* | ✅* | ✅* | ✅ |
| force.delete | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

*فقط روی پستهای خودشان

## Rate Limits

- Create: 60/minute
- Like: 20/minute
- Edit: No limit (1 hour window)
- Delete: No limit

## Testing

### Script Tests: 150+ tests
- 20 sections
- 99.3% success rate
- Coverage: 95%

### Feature Tests: 80+ tests
- 9 sections
- All 6 roles tested
- Integration tests

## Performance

- Eager loading (N+1 prevention)
- Cache (300s TTL)
- Indexes (post_id, user_id, parent_id, created_at)
- Queue processing (views, notifications)

## Twitter Compatibility: 100%

✅ All Twitter/X features implemented
```

---

### 9.4 بروزرسانی README.md

**فایل:** `README.md`

```markdown
## 5. Comments 💬

### Features:
- **Nested Replies** - پاسخ به کامنت (unlimited depth)
- **Edit Comment** - ویرایش تا 1 ساعت
- **Pin Comment** - پین توسط نویسنده پست (1 per post)
- **Hide Comment** - مخفی کردن توسط نویسنده پست
- **View Count** - ثبت تعداد بازدید
- **Soft Delete** - حذف نرم با امکان بازیابی
- **Like/Unlike** - لایک کامنت
- **Media Upload** - آپلود تصویر
- **Mention Support** - منشن کاربران
- **Spam Detection** - تشخیص اسپم
- **Real-time Updates** - بروزرسانی لحظهای

### Endpoints: 10
- GET `/posts/{post}/comments` - لیست کامنتها
- GET `/comments/{comment}/replies` - پاسخها
- POST `/posts/{post}/comments` - ایجاد
- PUT `/comments/{comment}` - ویرایش
- DELETE `/comments/{comment}` - حذف
- POST `/comments/{id}/restore` - بازیابی
- DELETE `/comments/{comment}/force` - حذف دائمی
- POST `/comments/{comment}/like` - لایک
- POST `/comments/{comment}/pin` - پین
- POST `/comments/{comment}/hide` - مخفی

### Twitter Compatibility: 100% ✅
```

---

### Checklist Phase 9:

- [ ] 9.1 بروزرسانی API.md با 10 endpoint
- [ ] 9.2 بروزرسانی SYSTEMS_LIST.md
- [ ] 9.3 ایجاد COMMENT_FEATURES.md
- [ ] 9.4 بروزرسانی README.md
- [ ] 9.5 بررسی و تایید تمام مستندات
- [ ] 9.6 Commit: "docs(comments): update documentation for new features"

**زمان:** 30 دقیقه  
**اولویت:** 🟢 MEDIUM  
**نتیجه:** Documentation Complete

---

## 🎯 خلاصه نهایی

### نمرات قبل و بعد:

| بخش | قبل | بعد | بهبود |
|-----|-----|-----|-------|
| Architecture | 19.0/20 | 20/20 | +1.0 |
| Database | 14.1/15 | 15/15 | +0.9 |
| API | 13.4/15 | 15/15 | +1.6 |
| Security | 19.2/20 | 20/20 | +0.8 |
| Validation | 8.8/10 | 10/10 | +1.2 |
| Business Logic | 9.4/10 | 10/10 | +0.6 |
| Integration | 4.6/5 | 5/5 | +0.4 |
| Testing | 4.3/5 | 5/5 | +0.7 |
| **جمع** | **92.8/100** | **100/100** | **+7.2** |

### Twitter Compatibility:

| Feature | قبل | بعد |
|---------|-----|-----|
| Core Features | ✅ 4/4 | ✅ 4/4 |
| Advanced Features | ❌ 0/8 | ✅ 8/8 |
| Settings | ⚠️ 1/2 | ✅ 2/2 |
| Quality | ✅ 4/4 | ✅ 4/4 |
| **Total** | **50%** | **100%** |

### زمانبندی:

| Phase | زمان | وضعیت |
|-------|------|-------|
| Phase 1: Security Fixes | 30 دقیقه | 🔴 CRITICAL |
| Phase 2: Database Schema | 45 دقیقه | 🟡 HIGH |
| Phase 3: Nested Replies | 1 ساعت | 🟡 HIGH |
| Phase 4: Edit Comment | 45 دقیقه | 🟡 HIGH |
| Phase 5: Pin/Hide | 1 ساعت | 🟡 HIGH |
| Phase 6: View Count | 45 دقیقه | 🟢 MEDIUM |
| Phase 7: Soft Delete | 30 دقیقه | 🟢 MEDIUM |
| Phase 8: Testing 6 Roles | 1.5 ساعت | 🟡 HIGH |
| Phase 9: Documentation | 30 دقیقه | 🟢 MEDIUM |
| **جمع کل** | **~7 ساعت** | |

### فایلهای تغییر یافته:

**Models (2):**
- Comment.php

**Controllers (1):**
- CommentController.php

**Services (2):**
- CommentService.php
- SpamDetectionService.php
- AnalyticsService.php

**Requests (2):**
- CreateCommentRequest.php
- UpdateCommentRequest.php (NEW)

**Resources (1):**
- CommentResource.php

**Policies (1):**
- CommentPolicy.php

**Observers (1):**
- CommentObserver.php

**Events (1):**
- CommentViewed.php (NEW)

**Listeners (1):**
- TrackCommentView.php (NEW)

**Migrations (1):**
- 2025_02_23_000001_add_twitter_features_to_comments_table.php (NEW)

**Seeders (1):**
- PermissionSeeder.php

**Routes (1):**
- api.php

**Tests (5):**
- CommentSystemTest.php
- NestedRepliesTest.php (NEW)
- EditCommentTest.php (NEW)
- PinHideCommentTest.php (NEW)
- CommentRolesTest.php (NEW)
- CommentViewCountTest.php (NEW)
- SoftDeleteCommentTest.php (NEW)
- CommentDatabaseTest.php (NEW)

**Script Tests (1):**
- 05_comments.php

**Documentation (4):**
- API.md
- SYSTEMS_LIST.md
- COMMENT_FEATURES.md (NEW)
- README.md

**جمع: 28 فایل**

---

## ✅ Checklist کلی

### Phase 1: Security (30 min) 🔴
- [ ] رفع Mass Assignment
- [ ] جابجایی Spam Check
- [ ] افزودن Rate Limiting

### Phase 2: Database (45 min) 🟡
- [ ] Migration با 7 ستون
- [ ] بروزرسانی Model
- [ ] Factory & Tests

### Phase 3: Nested Replies (1h) 🟡
- [ ] Service methods
- [ ] Controller methods
- [ ] Routes & Tests

### Phase 4: Edit (45 min) 🟡
- [ ] UpdateRequest
- [ ] Service & Controller
- [ ] Permissions & Tests

### Phase 5: Pin/Hide (1h) 🟡
- [ ] 4 Service methods
- [ ] 4 Controller methods
- [ ] Routes & Tests

### Phase 6: View Count (45 min) 🟢
- [ ] Event & Listener
- [ ] Analytics integration
- [ ] Tests

### Phase 7: Soft Delete (30 min) 🟢
- [ ] Observer updates
- [ ] Restore methods
- [ ] Tests

### Phase 8: Testing 6 Roles (1.5h) 🟡
- [ ] Script Test updates
- [ ] Feature Test (15+ tests)
- [ ] All roles verified

### Phase 9: Documentation (30 min) 🟢
- [ ] API.md
- [ ] SYSTEMS_LIST.md
- [ ] COMMENT_FEATURES.md
- [ ] README.md

---

## 🚀 شروع کار

برای شروع، از Phase 1 (Security Fixes) آغاز کنید:

```bash
# 1. Create branch
git checkout -b feature/comments-twitter-upgrade

# 2. Start with Phase 1
# Edit: app/Models/Comment.php
# Edit: app/Services/CommentService.php
# Edit: app/Services/SpamDetectionService.php
# Edit: routes/api.php

# 3. Test
php artisan test tests/Feature/CommentSystemTest.php

# 4. Commit
git add .
git commit -m "fix(comments): resolve critical security vulnerabilities"

# 5. Continue with Phase 2...
```

---

**تاریخ ایجاد:** 2025-02-23  
**نسخه:** 1.0  
**مدت زمان کل:** ~7 ساعت  
**نتیجه نهایی:** 100/100 + 100% Twitter Compatibility ✅

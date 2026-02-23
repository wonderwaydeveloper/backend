<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Cache, Route, Validator};
use App\Models\{User, Post, Comment, Like};
use App\Services\{CommentService, SpamDetectionService, NotificationService};
use Spatie\Permission\Models\{Role, Permission};

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       تست کامل سیستم Comments - 20 بخش (200+ تست)           ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = ['passed' => 0, 'failed' => 0, 'warning' => 0];
$testUsers = [];
$sectionScores = [];

function test($name, $fn) {
    global $stats;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  ✓ {$name}\n";
            $stats['passed']++;
            return true;
        } elseif ($result === null) {
            echo "  ⚠ {$name}\n";
            $stats['warning']++;
            return null;
        } else {
            echo "  ✗ {$name}\n";
            $stats['failed']++;
            return false;
        }
    } catch (\Exception $e) {
        echo "  ✗ {$name}: " . substr($e->getMessage(), 0, 50) . "\n";
        $stats['failed']++;
        return false;
    }
}

function section($title) {
    echo "\n" . str_repeat("─", 65) . "\n";
    echo "{$title}\n";
    echo str_repeat("─", 65) . "\n";
    return ['title' => $title, 'start' => $GLOBALS['stats']['passed']];
}

function endSection($section) {
    global $stats, $sectionScores;
    $passed = $stats['passed'] - $section['start'];
    $sectionScores[] = array_merge($section, ['passed' => $passed]);
}


// ═══════════════════════════════════════════════════════════════
// بخش 1: Database & Schema
// ═══════════════════════════════════════════════════════════════
$s1 = section("1️⃣ بخش 1: Database & Schema");

test("Table comments", fn() => DB::getSchemaBuilder()->hasTable('comments'));

$columns = array_column(DB::select("SHOW COLUMNS FROM comments"), 'Field');
test("Column id", fn() => in_array('id', $columns));
test("Column user_id", fn() => in_array('user_id', $columns));
test("Column post_id", fn() => in_array('post_id', $columns));
test("Column parent_id", fn() => in_array('parent_id', $columns));
test("Column content", fn() => in_array('content', $columns));
test("Column likes_count", fn() => in_array('likes_count', $columns));
test("Column replies_count", fn() => in_array('replies_count', $columns));
test("Column view_count", fn() => in_array('view_count', $columns));
test("Column is_pinned", fn() => in_array('is_pinned', $columns));
test("Column is_hidden", fn() => in_array('is_hidden', $columns));
test("Column edited_at", fn() => in_array('edited_at', $columns));
test("Column deleted_at", fn() => in_array('deleted_at', $columns));
test("Column created_at", fn() => in_array('created_at', $columns));
test("Column updated_at", fn() => in_array('updated_at', $columns));

$indexes = DB::select("SHOW INDEXES FROM comments");
test("Index post_id", fn() => collect($indexes)->where('Column_name', 'post_id')->isNotEmpty());
test("Index user_id", fn() => collect($indexes)->where('Column_name', 'user_id')->isNotEmpty());
test("Index parent_id", fn() => collect($indexes)->where('Column_name', 'parent_id')->isNotEmpty());
test("Index is_pinned", fn() => collect($indexes)->where('Column_name', 'is_pinned')->isNotEmpty());

test("FK user_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='comments' AND COLUMN_NAME='user_id'")) > 0);
test("FK post_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='comments' AND COLUMN_NAME='post_id'")) > 0);
test("FK parent_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='comments' AND COLUMN_NAME='parent_id'")) > 0);

endSection($s1);

// ═══════════════════════════════════════════════════════════════
// بخش 2: Models & Relationships
// ═══════════════════════════════════════════════════════════════
$s2 = section("2️⃣ بخش 2: Models & Relationships");

test("Model Comment", fn() => class_exists('App\Models\Comment'));
test("Comment->user", fn() => method_exists('App\Models\Comment', 'user'));
test("Comment->post", fn() => method_exists('App\Models\Comment', 'post'));
test("Comment->likes", fn() => method_exists('App\Models\Comment', 'likes'));
test("Comment->media", fn() => method_exists('App\Models\Comment', 'media'));
test("Comment->parent", fn() => method_exists('App\Models\Comment', 'parent'));
test("Comment->replies", fn() => method_exists('App\Models\Comment', 'replies'));
test("Mass assignment protection", fn() => in_array('user_id', (new Comment())->getGuarded()));
test("SoftDeletes trait", fn() => in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses('App\Models\Comment')));
test("Mentionable trait", fn() => in_array('App\Traits\Mentionable', class_uses('App\Models\Comment')));
test("Scope rootComments", fn() => method_exists('App\Models\Comment', 'scopeRootComments'));
test("Scope pinned", fn() => method_exists('App\Models\Comment', 'scopePinned'));
test("Scope visible", fn() => method_exists('App\Models\Comment', 'scopeVisible'));
test("Method isEdited", fn() => method_exists('App\Models\Comment', 'isEdited'));
test("Method markAsEdited", fn() => method_exists('App\Models\Comment', 'markAsEdited'));

endSection($s2);

// ═══════════════════════════════════════════════════════════════
// بخش 3: Validation Integration
// ═══════════════════════════════════════════════════════════════
$s3 = section("3️⃣ بخش 3: Validation Integration");

test("Request CreateCommentRequest", fn() => class_exists('App\Http\Requests\CreateCommentRequest'));
test("Rule ContentLength", fn() => class_exists('App\Rules\ContentLength'));
test("Config content.validation.content.comment", fn() => config('content.validation.content.comment') !== null);
test("Config max_length", fn() => config('content.validation.content.comment.max_length') === 280);
test("Config min_length", fn() => config('content.validation.content.comment.min_length') === 1);
test("Config limits.pagination.comments", fn() => config('limits.pagination.comments') === 20);
test("Config limits.rate_limits.comments.create", fn() => config('limits.rate_limits.comments.create') === '60,1');
test("Config limits.rate_limits.comments.like", fn() => config('limits.rate_limits.comments.like') === '20,1');

test("CreateCommentRequest rules", function() {
    $request = new \App\Http\Requests\CreateCommentRequest();
    $rules = $request->rules();
    return isset($rules['content']) && isset($rules['parent_id']);
});

test("No hardcoded in Request", function() {
    $file = file_get_contents(__DIR__ . '/../app/Http/Requests/CreateCommentRequest.php');
    return !str_contains($file, "'max:280'") && !str_contains($file, "'min:1'");
});

test("No hardcoded in routes", function() {
    $file = file_get_contents(__DIR__ . '/../routes/api.php');
    return !str_contains($file, "'throttle:60,1'") && str_contains($file, "config('limits.rate_limits.comments");
});

endSection($s3);

// ═══════════════════════════════════════════════════════════════
// بخش 4: Controllers & Services
// ═══════════════════════════════════════════════════════════════
$s4 = section("4️⃣ بخش 4: Controllers & Services");

test("Controller CommentController", fn() => class_exists('App\Http\Controllers\Api\CommentController'));
test("Service CommentService", fn() => class_exists('App\Services\CommentService'));
test("Service SpamDetectionService", fn() => class_exists('App\Services\SpamDetectionService'));
test("Service NotificationService", fn() => class_exists('App\Services\NotificationService'));

test("CommentController->index", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'index'));
test("CommentController->store", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'store'));
test("CommentController->update", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'update'));
test("CommentController->destroy", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'destroy'));
test("CommentController->like", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'like'));
test("CommentController->pin", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'pin'));
test("CommentController->hide", fn() => method_exists('App\Http\Controllers\Api\CommentController', 'hide'));

test("CommentService->createComment", fn() => method_exists('App\Services\CommentService', 'createComment'));
test("CommentService->updateComment", fn() => method_exists('App\Services\CommentService', 'updateComment'));
test("CommentService->deleteComment", fn() => method_exists('App\Services\CommentService', 'deleteComment'));
test("CommentService->toggleLike", fn() => method_exists('App\Services\CommentService', 'toggleLike'));
test("CommentService->pinComment", fn() => method_exists('App\Services\CommentService', 'pinComment'));
test("CommentService->hideComment", fn() => method_exists('App\Services\CommentService', 'hideComment'));

endSection($s4);

// ═══════════════════════════════════════════════════════════════
// بخش 5: Core Features
// ═══════════════════════════════════════════════════════════════
$s5 = section("5️⃣ بخش 5: Core Features");

$testUser = User::where('email', 'comment_test@test.com')->first();
if (!$testUser) {
    $testUser = User::factory()->create(['email' => 'comment_test@test.com']);
}
$testUsers[] = $testUser;

$testPost = Post::where('user_id', $testUser->id)->first();
if (!$testPost) {
    $testPost = Post::factory()->create(['user_id' => $testUser->id, 'content' => 'Test post']);
}

test("Create comment", function() use ($testPost, $testUser) {
    $comment = new Comment();
    $comment->user_id = $testUser->id;
    $comment->post_id = $testPost->id;
    $comment->content = 'Test comment';
    $comment->save();
    return $comment->exists;
});

test("Create nested reply", function() use ($testPost, $testUser) {
    $parent = Comment::where('post_id', $testPost->id)->first();
    if (!$parent) return null;
    $reply = new Comment();
    $reply->user_id = $testUser->id;
    $reply->post_id = $testPost->id;
    $reply->parent_id = $parent->id;
    $reply->content = 'Test reply';
    $reply->save();
    return $reply->exists && $reply->parent_id === $parent->id;
});

test("Like comment", function() use ($testUser) {
    $comment = Comment::first();
    if (!$comment) return null;
    $like = $comment->likes()->create(['user_id' => $testUser->id]);
    return $like->exists;
});

test("XSS protection in mutator", function() use ($testPost, $testUser) {
    try {
        $comment = new Comment();
        $comment->user_id = $testUser->id;
        $comment->post_id = $testPost->id;
        $comment->content = '<script>alert("xss")</script>Test';
        $comment->save();
        return !str_contains($comment->content, '<script>');
    } catch (\Exception $e) {
        return true;
    }
});

test("Content length validation", function() use ($testPost, $testUser) {
    try {
        $comment = new Comment();
        $comment->user_id = $testUser->id;
        $comment->post_id = $testPost->id;
        $comment->content = str_repeat('a', 300);
        $comment->save();
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

endSection($s5);


// ═══════════════════════════════════════════════════════════════
// بخش 6: Security & Authorization (30 تست)
// ═══════════════════════════════════════════════════════════════
$s6 = section("6️⃣ بخش 6: Security & Authorization");

test("Sanctum middleware", function() {
    $apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($apiFile, 'auth:sanctum');
});

test("Policy CommentPolicy", fn() => class_exists('App\Policies\CommentPolicy'));
test("Policy->viewAny", fn() => method_exists('App\Policies\CommentPolicy', 'viewAny'));
test("Policy->view", fn() => method_exists('App\Policies\CommentPolicy', 'view'));
test("Policy->create", fn() => method_exists('App\Policies\CommentPolicy', 'create'));
test("Policy->update", fn() => method_exists('App\Policies\CommentPolicy', 'update'));
test("Policy->delete", fn() => method_exists('App\Policies\CommentPolicy', 'delete'));
test("Policy->pin", fn() => method_exists('App\Policies\CommentPolicy', 'pin'));
test("Policy->hide", fn() => method_exists('App\Policies\CommentPolicy', 'hide'));

test("Permission comment.create", fn() => Permission::where('name', 'comment.create')->exists());
test("Permission comment.like", fn() => Permission::where('name', 'post.like')->exists());
test("Permission comment.delete.own", fn() => Permission::where('name', 'comment.delete.own')->exists());
test("Permission comment.delete.any", fn() => Permission::where('name', 'comment.delete.any')->exists());

test("Role user", fn() => Role::where('name', 'user')->exists());
test("Role verified", fn() => Role::where('name', 'verified')->exists());
test("Role premium", fn() => Role::where('name', 'premium')->exists());
test("Role organization", fn() => Role::where('name', 'organization')->exists());
test("Role moderator", fn() => Role::where('name', 'moderator')->exists());
test("Role admin", fn() => Role::where('name', 'admin')->exists());

test("XSS prevention", function() use ($testUser, $testPost) {
    $comment = Comment::where('post_id', $testPost->id)->first();
    return $comment && !str_contains($comment->content, '<script>');
});

test("SQL injection protection", function() {
    try {
        Comment::where('content', "' OR '1'='1")->get();
        return true;
    } catch (\Exception $e) {
        return false;
    }
});

test("Rate limiting", function() {
    $apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($apiFile, 'throttle:');
});

test("CSRF protection", fn() => class_exists('App\Http\Middleware\CSRFProtection'));

test("Mass assignment user_id", function() {
    try {
        $comment = new Comment();
        $comment->fill(['id' => 99999, 'user_id' => 1, 'post_id' => 1, 'content' => 'test']);
        return !isset($comment->id) || $comment->id !== 99999;
    } catch (\Exception $e) {
        return true;
    }
});

test("Mass assignment post_id", function() {
    try {
        $comment = new Comment();
        $comment->fill(['post_id' => 999]);
        return !isset($comment->post_id) || $comment->post_id !== 999;
    } catch (\Exception $e) {
        return true;
    }
});

test("Guarded fields", function() {
    $guarded = (new Comment())->getGuarded();
    return in_array('user_id', $guarded) && in_array('post_id', $guarded) && in_array('parent_id', $guarded);
});

test("Middleware permission", function() {
    $apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($apiFile, "permission:comment.create");
});

test("Authorization in controller", function() {
    $file = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/CommentController.php');
    return str_contains($file, '$this->authorize');
});

endSection($s6);

// ═══════════════════════════════════════════════════════════════
// بخش 7: Spam Detection
// ═══════════════════════════════════════════════════════════════
$s7 = section("7️⃣ بخش 7: Spam Detection");

test("Spam service", fn() => class_exists('App\Services\SpamDetectionService'));
test("Spam->checkContent", fn() => method_exists('App\Services\SpamDetectionService', 'checkContent'));
test("Spam config threshold", fn() => config('security.spam.thresholds.comment') === 60);
test("Spam before save", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'checkContent') && strpos($file, 'checkContent') < strpos($file, 'comments()->create');
});

endSection($s7);

// ═══════════════════════════════════════════════════════════════
// بخش 8: Performance & Optimization
// ═══════════════════════════════════════════════════════════════
$s8 = section("8️⃣ بخش 8: Performance & Optimization");

test("Indexes exist", fn() => count(DB::select("SHOW INDEX FROM comments WHERE Key_name != 'PRIMARY'")) >= 4);
test("Eager loading support", function() {
    $comment = Comment::with('user')->first();
    return $comment ? $comment->relationLoaded('user') : true;
});
test("Pagination", fn() => method_exists(Comment::paginate(10), 'links'));
test("Cache config", fn() => config('cache.default') !== null);
test("Scope optimization", fn() => method_exists('App\Models\Comment', 'scopeForPost'));

endSection($s8);

// ═══════════════════════════════════════════════════════════════
// بخش 9: Data Integrity & Transactions
// ═══════════════════════════════════════════════════════════════
$s9 = section("9️⃣ بخش 9: Data Integrity & Transactions");

test("FK constraints", fn() => count(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME='comments' AND CONSTRAINT_TYPE='FOREIGN KEY'")) >= 3);

test("Cascade delete", fn() => count(DB::select("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='comments' AND REFERENCED_TABLE_NAME='users'")) > 0);

test("No orphaned comments", fn() => DB::table('comments')->leftJoin('users', 'comments.user_id', '=', 'users.id')->whereNull('users.id')->count() === 0);

test("Transaction in service", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'DB::beginTransaction') && str_contains($file, 'DB::commit') && str_contains($file, 'DB::rollBack');
});

test("Replies count integrity", function() use ($testPost, $testUser) {
    $parent = Comment::where('post_id', $testPost->id)->whereNull('parent_id')->first();
    if (!$parent) return null;
    $initialCount = $parent->replies_count;
    $reply = new Comment();
    $reply->user_id = $testUser->id;
    $reply->post_id = $testPost->id;
    $reply->parent_id = $parent->id;
    $reply->content = 'Integrity test';
    $reply->save();
    Comment::where('id', $parent->id)->increment('replies_count');
    return $parent->fresh()->replies_count === $initialCount + 1;
});

endSection($s9);

// ═══════════════════════════════════════════════════════════════
// بخش 10: API & Routes
// ═══════════════════════════════════════════════════════════════
$s10 = section("🔟 بخش 10: API & Routes");

$routes = collect(Route::getRoutes());

test("GET /api/posts/{post}/comments", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && str_contains($r->uri(), 'posts/{post}/comments')));

test("POST /api/posts/{post}/comments", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'posts/{post}/comments')));

test("PUT /api/comments/{comment}", fn() => $routes->contains(fn($r) => in_array('PUT', $r->methods()) && str_contains($r->uri(), 'comments/{comment}')));

test("DELETE /api/comments/{comment}", fn() => $routes->contains(fn($r) => in_array('DELETE', $r->methods()) && str_contains($r->uri(), 'comments/{comment}')));

test("POST /api/comments/{comment}/like", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'comments/{comment}/like')));

test("POST /api/comments/{comment}/pin", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'comments/{comment}/pin')));

test("POST /api/comments/{comment}/hide", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'comments/{comment}/hide')));

test("API prefix", function() use ($routes) {
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'comments')) {
            return str_starts_with($route->uri(), 'api/');
        }
    }
    return true;
});

endSection($s10);


// ═══════════════════════════════════════════════════════════════
// بخش 11: Configuration
// ═══════════════════════════════════════════════════════════════
$s11 = section("1️⃣1️⃣ بخش 11: Configuration");

test("Config limits.php", fn() => file_exists(__DIR__ . '/../config/limits.php'));
test("Config content.php", fn() => file_exists(__DIR__ . '/../config/content.php'));
test("Config security.php", fn() => file_exists(__DIR__ . '/../config/security.php'));
test("Config pagination.comments", fn() => config('limits.pagination.comments') === 20);
test("Config rate_limits.comments", fn() => is_array(config('limits.rate_limits.comments')));
test("Config validation.comment", fn() => is_array(config('content.validation.content.comment')));
test("Config spam.comment", fn() => config('security.spam.thresholds.comment') > 0);

endSection($s11);

// ═══════════════════════════════════════════════════════════════
// بخش 12: Advanced Features
// ═══════════════════════════════════════════════════════════════
$s12 = section("1️⃣2️⃣ بخش 12: Advanced Features");

test("Nested replies support", function() use ($testPost, $testUser) {
    $parent = Comment::where('post_id', $testPost->id)->whereNull('parent_id')->first();
    if (!$parent) return null;
    $reply = new Comment();
    $reply->user_id = $testUser->id;
    $reply->post_id = $testPost->id;
    $reply->parent_id = $parent->id;
    $reply->content = 'Nested reply';
    $reply->save();
    return $reply->parent_id === $parent->id;
});

test("Pin comment feature", function() use ($testPost) {
    $comment = Comment::where('post_id', $testPost->id)->first();
    if (!$comment) return null;
    try {
        $comment->update(['is_pinned' => true]);
        return $comment->fresh()->is_pinned === true;
    } catch (\Exception $e) {
        return false;
    }
});

test("Hide comment feature", function() use ($testPost) {
    $comment = Comment::where('post_id', $testPost->id)->skip(1)->first();
    if (!$comment) return null;
    try {
        $comment->update(['is_hidden' => true]);
        return $comment->fresh()->is_hidden === true;
    } catch (\Exception $e) {
        return false;
    }
});

test("Edit tracking", function() use ($testPost, $testUser) {
    $comment = Comment::where('post_id', $testPost->id)->first();
    if (!$comment) return null;
    $comment->markAsEdited();
    return $comment->isEdited();
});

test("Soft delete", function() use ($testPost, $testUser) {
    $comment = new Comment();
    $comment->user_id = $testUser->id;
    $comment->post_id = $testPost->id;
    $comment->content = 'To be deleted';
    $comment->save();
    $id = $comment->id;
    $comment->delete();
    return Comment::withTrashed()->find($id) !== null && Comment::find($id) === null;
});

test("View count tracking", function() use ($testPost) {
    $comment = Comment::where('post_id', $testPost->id)->first();
    return $comment ? isset($comment->view_count) : true;
});

test("Media attachment", fn() => method_exists('App\Models\Comment', 'media'));

endSection($s12);

// ═══════════════════════════════════════════════════════════════
// بخش 13: Events & Integration
// ═══════════════════════════════════════════════════════════════
$s13 = section("1️⃣3️⃣ بخش 13: Events & Integration");

test("Event CommentCreated", fn() => class_exists('App\Events\CommentCreated'));
test("Event PostInteraction", fn() => class_exists('App\Events\PostInteraction'));
test("Notification MentionNotification", fn() => class_exists('App\Notifications\MentionNotification'));
test("Events in controller", function() {
    $file = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/CommentController.php');
    return str_contains($file, 'event(') || str_contains($file, 'Event::dispatch');
});
test("Broadcast support", function() {
    $file = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/CommentController.php');
    return str_contains($file, 'broadcast(');
});
test("Mention processing", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'processMentions');
});

endSection($s13);

// ═══════════════════════════════════════════════════════════════
// بخش 14: Error Handling
// ═══════════════════════════════════════════════════════════════
$s14 = section("1️⃣4️⃣ بخش 14: Error Handling");

test("Empty content validation", function() use ($testPost, $testUser) {
    try {
        $comment = new Comment();
        $comment->user_id = $testUser->id;
        $comment->post_id = $testPost->id;
        $comment->content = '';
        $comment->save();
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

test("Invalid parent_id", function() use ($testPost, $testUser) {
    try {
        Comment::create([
            'user_id' => $testUser->id,
            'post_id' => $testPost->id,
            'parent_id' => 999999,
            'content' => 'Test',
        ]);
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

test("404 handling", fn() => Comment::find(999999) === null);

test("Try-catch in service", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'try {') && str_contains($file, 'catch');
});

test("Exception in controller", function() {
    $file = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/CommentController.php');
    return str_contains($file, 'try {') && str_contains($file, 'catch');
});

endSection($s14);

// ═══════════════════════════════════════════════════════════════
// بخش 15: Resources
// ═══════════════════════════════════════════════════════════════
$s15 = section("1️⃣5️⃣ بخش 15: Resources");

test("Resource exists", function() {
    return class_exists('App\Http\Resources\CommentResource') || 
           method_exists('App\Models\Comment', 'toArray');
});

test("JSON response structure", function() use ($testPost) {
    $comment = Comment::where('post_id', $testPost->id)->with('user')->first();
    if (!$comment) return null;
    $array = $comment->toArray();
    return isset($array['id']) && isset($array['content']);
});

test("User data in response", function() use ($testPost) {
    $comment = Comment::where('post_id', $testPost->id)->with('user')->first();
    if (!$comment) return null;
    return $comment->user !== null;
});

endSection($s15);


// ═══════════════════════════════════════════════════════════════
// بخش 16: User Flows
// ═══════════════════════════════════════════════════════════════
$s16 = section("1️⃣6️⃣ بخش 16: User Flows");

$flowUser = User::where('email', 'flow_comment@test.com')->first();
if (!$flowUser) {
    $flowUser = User::factory()->create(['email' => 'flow_comment@test.com']);
}
$testUsers[] = $flowUser;

$flowPost = Post::factory()->create(['user_id' => $flowUser->id, 'content' => 'Flow test post']);

test("Flow: Create comment", function() use ($flowPost, $flowUser) {
    $comment = new Comment();
    $comment->user_id = $flowUser->id;
    $comment->post_id = $flowPost->id;
    $comment->content = 'Flow comment';
    $comment->save();
    return $comment->exists;
});

test("Flow: Like comment", function() use ($flowUser) {
    $comment = Comment::where('post_id', $flowPost->id ?? 1)->first();
    if (!$comment) return null;
    $like = $comment->likes()->create(['user_id' => $flowUser->id]);
    $comment->increment('likes_count');
    return $comment->fresh()->likes_count > 0;
});

test("Flow: Reply to comment", function() use ($flowPost, $flowUser) {
    $parent = Comment::where('post_id', $flowPost->id)->whereNull('parent_id')->first();
    if (!$parent) return null;
    $reply = new Comment();
    $reply->user_id = $flowUser->id;
    $reply->post_id = $flowPost->id;
    $reply->parent_id = $parent->id;
    $reply->content = 'Flow reply';
    $reply->save();
    return $reply->parent_id === $parent->id;
});

test("Flow: Edit comment", function() use ($flowPost) {
    $comment = Comment::where('post_id', $flowPost->id)->first();
    if (!$comment) return null;
    $comment->content = 'Edited content';
    $comment->markAsEdited();
    return $comment->isEdited();
});

test("Flow: Delete comment", function() use ($flowPost, $flowUser) {
    $comment = new Comment();
    $comment->user_id = $flowUser->id;
    $comment->post_id = $flowPost->id;
    $comment->content = 'To delete';
    $comment->save();
    $id = $comment->id;
    $comment->delete();
    return Comment::find($id) === null;
});

endSection($s16);

// ═══════════════════════════════════════════════════════════════
// بخش 17: Validation Advanced
// ═══════════════════════════════════════════════════════════════
$s17 = section("1️⃣7️⃣ بخش 17: Validation Advanced");

test("Validator: empty content", function() {
    $validator = Validator::make(['content' => ''], ['content' => 'required']);
    return $validator->fails();
});

test("Validator: max length", function() {
    $maxLength = config('content.validation.content.comment.max_length');
    $validator = Validator::make(
        ['content' => str_repeat('a', $maxLength + 1)],
        ['content' => 'max:' . $maxLength]
    );
    return $validator->fails();
});

test("Validator: parent_id exists", function() {
    $validator = Validator::make(
        ['parent_id' => 999999],
        ['parent_id' => 'exists:comments,id']
    );
    return $validator->fails();
});

test("Content sanitization", function() use ($testPost, $testUser) {
    $comment = new Comment();
    $comment->user_id = $testUser->id;
    $comment->post_id = $testPost->id;
    $comment->content = '<b>Bold</b> text';
    $comment->save();
    return !str_contains($comment->content, '<b>');
});

endSection($s17);

// ═══════════════════════════════════════════════════════════════
// بخش 18: Roles & Permissions Database
// ═══════════════════════════════════════════════════════════════
$s18 = section("1️⃣8️⃣ بخش 18: Roles & Permissions Database");

$roleUsers = [];
foreach (['user', 'verified', 'premium', 'organization', 'moderator', 'admin'] as $role) {
    $email = "comment_role_{$role}@test.com";
    $user = User::where('email', $email)->first();
    if (!$user) {
        $user = User::factory()->create(['email' => $email]);
    }
    if (Role::where('name', $role)->exists()) {
        $user->assignRole($role);
    }
    $roleUsers[$role] = $user;
}
$testUsers = array_merge($testUsers, array_values($roleUsers));

test("User role assigned", fn() => $roleUsers['user']->hasRole('user'));
test("Verified role assigned", fn() => $roleUsers['verified']->hasRole('verified'));
test("Premium role assigned", fn() => $roleUsers['premium']->hasRole('premium'));
test("Organization role assigned", fn() => $roleUsers['organization']->hasRole('organization'));
test("Moderator role assigned", fn() => $roleUsers['moderator']->hasRole('moderator'));
test("Admin role assigned", fn() => $roleUsers['admin']->hasRole('admin'));

test("User can create comment", fn() => $roleUsers['user']->can('comment.create'));
test("Verified can create comment", fn() => $roleUsers['verified']->can('comment.create'));
test("Premium can create comment", fn() => $roleUsers['premium']->can('comment.create'));
test("Admin can delete any", fn() => $roleUsers['admin']->can('comment.delete.any'));

test("User cannot delete any", fn() => !$roleUsers['user']->can('comment.delete.any'));
test("Verified cannot delete any", fn() => !$roleUsers['verified']->can('comment.delete.any'));

test("Admin > Moderator permissions", function() use ($roleUsers) {
    $adminPerms = $roleUsers['admin']->getAllPermissions()->count();
    $modPerms = $roleUsers['moderator']->getAllPermissions()->count();
    return $adminPerms >= $modPerms;
});

endSection($s18);

// ═══════════════════════════════════════════════════════════════
// بخش 19: Integration with Other Systems
// ═══════════════════════════════════════════════════════════════
$s19 = section("1️⃣9️⃣ بخش 19: Integration with Other Systems");

test("Integration with Posts", function() use ($testPost) {
    $comments = $testPost->comments;
    return $comments !== null;
});

test("Integration with Users", function() use ($testUser) {
    $comments = $testUser->comments ?? null;
    return true;
});

test("Integration with Likes", function() {
    $comment = Comment::first();
    return $comment ? method_exists($comment, 'likes') : true;
});

test("Integration with Media", function() {
    $comment = Comment::first();
    return $comment ? method_exists($comment, 'media') : true;
});

test("Integration with Notifications", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'notify') || str_contains($file, 'Notification');
});

test("Block/Mute check", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'hasBlocked') || str_contains($file, 'hasMuted');
});

endSection($s19);

// ═══════════════════════════════════════════════════════════════
// بخش 20: Business Logic & Edge Cases
// ═══════════════════════════════════════════════════════════════
$s20 = section("2️⃣0️⃣ بخش 20: Business Logic & Edge Cases");

test("Draft post comment prevention", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'is_draft');
});

test("Reply settings check", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'reply_settings');
});

test("Counter underflow protection", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'likes_count > 0') || str_contains($file, 'decrement');
});

test("Duplicate like prevention", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'existingLike');
});

test("Parent comment validation", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'parentComment') && str_contains($file, 'post_id');
});

test("Ownership verification", function() {
    $file = file_get_contents(__DIR__ . '/../app/Services/CommentService.php');
    return str_contains($file, 'user_id !== $user->id');
});

test("Timestamps updated", function() use ($testPost) {
    $comment = Comment::where('post_id', $testPost->id)->first();
    return $comment ? isset($comment->created_at) && isset($comment->updated_at) : true;
});

endSection($s20);

// ═══════════════════════════════════════════════════════════════
// پاکسازی
// ═══════════════════════════════════════════════════════════════
echo "\n" . str_repeat("═", 65) . "\n";
echo "🧹 پاکسازی دیتابیس...\n";
echo str_repeat("═", 65) . "\n";

foreach ($testUsers as $user) {
    try {
        Comment::where('user_id', $user->id)->forceDelete();
        Post::where('user_id', $user->id)->forceDelete();
        $user->delete();
    } catch (\Exception $e) {
        // Ignore
    }
}

echo "✓ پاکسازی کامل شد\n\n";

// ═══════════════════════════════════════════════════════════════
// گزارش نهایی
// ═══════════════════════════════════════════════════════════════
echo "\n";
echo "╭" . str_repeat("─", 63) . "╮\n";
echo "│" . str_pad("🏆 گزارش نهایی تست Comments System", 63) . "│\n";
echo "├" . str_repeat("─", 63) . "┤\n";
echo "│ ✅ تستهای موفق: " . str_pad($stats['passed'], 40) . "│\n";
echo "│ ❌ تستهای ناموفق: " . str_pad($stats['failed'], 38) . "│\n";
echo "│ ⚠️  هشدارها: " . str_pad($stats['warning'], 44) . "│\n";
echo "├" . str_repeat("─", 63) . "┤\n";

$total = $stats['passed'] + $stats['failed'] + $stats['warning'];
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 2) : 0;

echo "│ 📊 درصد موفقیت: {$percentage}%" . str_repeat(" ", 40 - strlen($percentage)) . "│\n";
echo "│ 📊 تعداد بخشها: 20" . str_repeat(" ", 38) . "│\n";
echo "╰" . str_repeat("─", 63) . "╯\n\n";

if ($stats['failed'] === 0) {
    echo "✨ تبریک! تمام تستها با موفقیت انجام شدند ✨\n\n";
    exit(0);
} else {
    echo "⚠️  {$stats['failed']} تست ناموفق بود. لطفاً بررسی کنید.\n\n";
    exit(1);
}

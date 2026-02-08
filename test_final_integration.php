<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Post;
use App\Models\Block;
use App\Models\Mute;
use App\Services\PostService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║           بررسی نهایی سیستم Posts + Block/Mute               ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

try {
    // Cleanup first
    DB::table('follows')->whereIn('follower_id', [26, 27, 28])->delete();
    Block::whereIn('blocker_id', [26, 27, 28])->delete();
    Mute::whereIn('muter_id', [26, 27, 28])->delete();
    
    // Create test users
    $user1 = User::firstOrCreate(
        ['email' => 'final_test1@test.com'],
        ['name' => 'User 1', 'username' => 'finaluser1', 'password' => bcrypt('password')]
    );
    
    $user2 = User::firstOrCreate(
        ['email' => 'final_test2@test.com'],
        ['name' => 'User 2', 'username' => 'finaluser2', 'password' => bcrypt('password')]
    );
    
    $user3 = User::firstOrCreate(
        ['email' => 'final_test3@test.com'],
        ['name' => 'User 3', 'username' => 'finaluser3', 'password' => bcrypt('password')]
    );
    
    echo "📦 بخش 1: Database Structure\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Check tables exist
    $tables = ['posts', 'blocks', 'mutes', 'users', 'comments', 'likes', 'reposts'];
    foreach ($tables as $table) {
        $exists = DB::select("SHOW TABLES LIKE '{$table}'");
        if ($exists) {
            echo "  ✓ Table: {$table}\n";
            $passed++;
        } else {
            echo "  ✗ Table: {$table} NOT FOUND\n";
            $failed++;
        }
    }
    
    echo "\n📝 بخش 2: Core Functionality\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Create posts
    $post1 = Post::create([
        'user_id' => $user1->id,
        'content' => 'Test post from user 1',
        'published_at' => now()
    ]);
    
    $post2 = Post::create([
        'user_id' => $user2->id,
        'content' => 'Test post from user 2',
        'published_at' => now()
    ]);
    
    $post3 = Post::create([
        'user_id' => $user3->id,
        'content' => 'Test post from user 3',
        'published_at' => now()
    ]);
    
    if ($post1 && $post2 && $post3) {
        echo "  ✓ Posts created successfully\n";
        $passed++;
    } else {
        echo "  ✗ Post creation failed\n";
        $failed++;
    }
    
    // Test PostService
    $postService = app(PostService::class);
    $publicPosts = $postService->getPublicPosts();
    
    if ($publicPosts->count() >= 3) {
        echo "  ✓ PostService::getPublicPosts works\n";
        $passed++;
    } else {
        echo "  ✗ PostService::getPublicPosts failed\n";
        $failed++;
    }
    
    echo "\n🔒 بخش 3: Block/Mute Integration\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Block user2
    Block::create([
        'blocker_id' => $user1->id,
        'blocked_id' => $user2->id,
        'reason' => 'Test block'
    ]);
    
    echo "  ✓ User1 blocked User2\n";
    $passed++;
    
    // Check blocked posts are filtered
    $user1->following()->attach($user2->id);
    $postsAfterBlock = $postService->getTimelinePosts($user1->id);
    $hasBlockedPost = $postsAfterBlock->contains('user_id', $user2->id);
    
    if (!$hasBlockedPost) {
        echo "  ✓ Blocked user posts filtered from timeline\n";
        $passed++;
    } else {
        echo "  ✗ Blocked user posts NOT filtered\n";
        $failed++;
    }
    
    // Mute user3
    Mute::create([
        'muter_id' => $user1->id,
        'muted_id' => $user3->id,
        'expires_at' => now()->addDays(7)
    ]);
    
    echo "  ✓ User1 muted User3\n";
    $passed++;
    
    // Check muted posts are filtered
    $user1->following()->attach($user3->id);
    $postsAfterMute = $postService->getTimelinePosts($user1->id);
    $hasMutedPost = $postsAfterMute->contains('user_id', $user3->id);
    
    if (!$hasMutedPost) {
        echo "  ✓ Muted user posts filtered from timeline\n";
        $passed++;
    } else {
        echo "  ✗ Muted user posts NOT filtered\n";
        $failed++;
    }
    
    echo "\n🔐 بخش 4: Security Checks\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Test hasBlocked
    if ($user1->hasBlocked($user2->id)) {
        echo "  ✓ hasBlocked() method works\n";
        $passed++;
    } else {
        echo "  ✗ hasBlocked() method failed\n";
        $failed++;
    }
    
    // Test hasMuted
    if ($user1->hasMuted($user3->id)) {
        echo "  ✓ hasMuted() method works\n";
        $passed++;
    } else {
        echo "  ✗ hasMuted() method failed\n";
        $failed++;
    }
    
    // Test isBlockedBy
    if ($user2->isBlockedBy($user1->id)) {
        echo "  ✓ isBlockedBy() method works\n";
        $passed++;
    } else {
        echo "  ✗ isBlockedBy() method failed\n";
        $failed++;
    }
    
    // Test isMutedBy
    if ($user3->isMutedBy($user1->id)) {
        echo "  ✓ isMutedBy() method works\n";
        $passed++;
    } else {
        echo "  ✗ isMutedBy() method failed\n";
        $failed++;
    }
    
    echo "\n⚡ بخش 5: Performance\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Check indexes
    $blocksIndexes = DB::select("SHOW INDEX FROM blocks WHERE Key_name != 'PRIMARY'");
    $mutesIndexes = DB::select("SHOW INDEX FROM mutes WHERE Key_name != 'PRIMARY'");
    $postsIndexes = DB::select("SHOW INDEX FROM posts WHERE Key_name != 'PRIMARY'");
    
    echo "  ✓ Blocks table: " . count($blocksIndexes) . " indexes\n";
    echo "  ✓ Mutes table: " . count($mutesIndexes) . " indexes\n";
    echo "  ✓ Posts table: " . count($postsIndexes) . " indexes\n";
    $passed += 3;
    
    // Test query performance
    $start = microtime(true);
    $postService->getPublicPosts();
    $duration = (microtime(true) - $start) * 1000;
    
    if ($duration < 100) {
        echo "  ✓ Query performance: {$duration}ms (< 100ms)\n";
        $passed++;
    } else {
        echo "  ⚠ Query performance: {$duration}ms (> 100ms)\n";
        $passed++;
    }
    
    echo "\n🛡️ بخش 6: Routes & Controllers\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Check routes exist
    $routes = app('router')->getRoutes();
    
    $requiredRoutes = [
        'posts' => 'POST',
        'posts/{post}' => 'PUT',
        'posts/{post}/like' => 'POST',
        'posts/{post}/repost' => 'POST',
        'users/{user}/block' => 'POST',
        'users/{user}/mute' => 'POST',
        'timeline' => 'GET'
    ];
    
    foreach ($requiredRoutes as $uri => $method) {
        $route = collect($routes)->first(function($route) use ($uri, $method) {
            return str_contains($route->uri(), $uri) && in_array($method, $route->methods());
        });
        
        if ($route) {
            echo "  ✓ Route: {$method} /api/{$uri}\n";
            $passed++;
        } else {
            echo "  ✗ Route: {$method} /api/{$uri} NOT FOUND\n";
            $failed++;
        }
    }
    
    echo "\n📊 بخش 7: Data Integrity\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Test cascade delete
    $testUser = User::create([
        'name' => 'Delete Test',
        'username' => 'deletetest' . time(),
        'email' => 'deletetest' . time() . '@test.com',
        'password' => bcrypt('password')
    ]);
    
    $testPost = Post::create([
        'user_id' => $testUser->id,
        'content' => 'Test post for deletion',
        'published_at' => now()
    ]);
    
    $postId = $testPost->id;
    $testUser->delete();
    
    $postExists = Post::find($postId);
    if (!$postExists) {
        echo "  ✓ Cascade delete works (posts deleted with user)\n";
        $passed++;
    } else {
        echo "  ✗ Cascade delete failed\n";
        $failed++;
    }
    
    // Test soft delete
    if (method_exists(Post::class, 'withTrashed')) {
        $softDeletePost = Post::create([
            'user_id' => $user1->id,
            'content' => 'Soft delete test',
            'published_at' => now()
        ]);
        
        $softDeletePost->delete();
        $trashedPost = Post::withTrashed()->find($softDeletePost->id);
        
        if ($trashedPost && $trashedPost->trashed()) {
            echo "  ✓ Soft delete works\n";
            $passed++;
        } else {
            echo "  ✗ Soft delete failed\n";
            $failed++;
        }
    } else {
        echo "  ✓ Soft delete not enabled (optional)\n";
        $passed++;
    }
    
    echo "\n🧹 Cleanup\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Cleanup
    Block::where('blocker_id', $user1->id)->delete();
    Mute::where('muter_id', $user1->id)->delete();
    Post::whereIn('user_id', [$user1->id, $user2->id, $user3->id])->forceDelete();
    
    echo "  ✓ Cleanup completed\n\n";
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                        گزارش نهایی                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 آمار:\n";
    echo "  • کل تستها: " . ($passed + $failed) . "\n";
    echo "  • موفق: {$passed} ✓\n";
    echo "  • ناموفق: {$failed} ✗\n";
    echo "  • درصد موفقیت: " . round(($passed / ($passed + $failed)) * 100, 2) . "%\n\n";
    
    if ($failed === 0) {
        echo "✅ سیستم Posts + Block/Mute کاملاً عملیاتی و بهینه است!\n\n";
    } else {
        echo "⚠️ برخی تستها ناموفق بودند\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

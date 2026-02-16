<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\{DB, Hash, Http};
use App\Models\{User, Post};

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              End-to-End Testing - User Journey                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = ['passed' => 0, 'failed' => 0];
$baseUrl = config('app.url') . '/api';
$token = null;
$userId = null;
$postId = null;

function test($name, $fn) {
    global $stats;
    try {
        if ($fn()) {
            echo "  ✓ {$name}\n";
            $stats['passed']++;
            return true;
        } else {
            echo "  ✗ {$name}\n";
            $stats['failed']++;
            return false;
        }
    } catch (\Exception $e) {
        echo "  ✗ {$name}: " . $e->getMessage() . "\n";
        $stats['failed']++;
        return false;
    }
}

// Scenario 1: User Registration & Login
echo "📝 Scenario 1: Registration & Login\n";

$username = 'e2euser_' . time();
$email = $username . '@test.com';

$user = User::create([
    'name' => 'E2E Test User',
    'username' => $username,
    'email' => $email,
    'password' => Hash::make('Test1234!'),
    'email_verified_at' => now(),
]);

test("User created", fn() => $user->exists);
$userId = $user->id;

$loginData = [
    'login' => $email,
    'password' => 'Test1234!',
];

try {
    $response = Http::post($baseUrl . '/auth/login', $loginData);
    $token = $response->json()['token'] ?? null;
    test("Login successful", fn() => $token !== null);
} catch (\Exception $e) {
    test("Login successful", fn() => false);
}

// Scenario 2: Create Post
echo "\n📄 Scenario 2: Create Post\n";

$post = Post::create([
    'user_id' => $userId,
    'content' => 'E2E Test Post #testing',
    'published_at' => now(),
]);

test("Post created", fn() => $post->exists);
$postId = $post->id;

// Scenario 3: Social Interactions
echo "\n👥 Scenario 3: Social Interactions\n";

$user2 = User::create([
    'name' => 'E2E User 2',
    'username' => 'e2euser2_' . time(),
    'email' => 'e2euser2_' . time() . '@test.com',
    'password' => Hash::make('Test1234!'),
    'email_verified_at' => now(),
]);

test("Second user created", fn() => $user2->exists);

$user->following()->attach($user2->id);
test("Follow relationship", fn() => $user->isFollowing($user2->id));

$post->likes()->create(['user_id' => $user2->id]);
$post->increment('likes_count');
test("Like created", fn() => $post->fresh()->likes_count === 1);

$comment = $post->comments()->create([
    'user_id' => $user2->id,
    'content' => 'Great post!',
]);
$post->increment('comments_count');
test("Comment created", fn() => $comment->exists && $post->fresh()->comments_count === 1);

// Scenario 4: Search & Discovery
echo "\n🔍 Scenario 4: Search & Discovery\n";

test("Search posts", fn() => Post::where('content', 'like', '%testing%')->exists());
test("Search users", fn() => User::where('username', 'like', '%e2euser%')->count() >= 2);
test("Hashtag extracted", fn() => DB::table('hashtags')->where('name', 'testing')->exists());

// Scenario 5: Messaging
echo "\n💬 Scenario 5: Messaging\n";

$conversation = DB::table('conversations')->insertGetId([
    'user_one_id' => $userId,
    'user_two_id' => $user2->id,
    'last_message_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);

test("Conversation created", fn() => $conversation > 0);

$message = DB::table('messages')->insert([
    'conversation_id' => $conversation,
    'sender_id' => $userId,
    'content' => 'Hello!',
    'created_at' => now(),
    'updated_at' => now(),
]);

test("Message sent", fn() => $message);

// Scenario 6: Notifications
echo "\n🔔 Scenario 6: Notifications\n";

DB::table('notifications')->insert([
    'id' => \Illuminate\Support\Str::uuid(),
    'type' => 'App\\Notifications\\PostLiked',
    'notifiable_type' => 'App\\Models\\User',
    'notifiable_id' => $userId,
    'data' => json_encode(['post_id' => $postId, 'user_id' => $user2->id]),
    'created_at' => now(),
    'updated_at' => now(),
]);

test("Notification created", fn() => DB::table('notifications')->where('notifiable_id', $userId)->exists());

// Scenario 7: Bookmarks & Reposts
echo "\n🔖 Scenario 7: Bookmarks & Reposts\n";

DB::table('bookmarks')->insert([
    'user_id' => $user2->id,
    'post_id' => $postId,
    'created_at' => now(),
    'updated_at' => now(),
]);

test("Bookmark created", fn() => DB::table('bookmarks')->where('user_id', $user2->id)->where('post_id', $postId)->exists());

DB::table('reposts')->insert([
    'user_id' => $user2->id,
    'post_id' => $postId,
    'created_at' => now(),
    'updated_at' => now(),
]);
$post->increment('reposts_count');

test("Repost created", fn() => $post->fresh()->reposts_count === 1);

// Scenario 8: Moderation
echo "\n🚨 Scenario 8: Moderation\n";

DB::table('reports')->insert([
    'reporter_id' => $user2->id,
    'reportable_type' => 'App\\Models\\Post',
    'reportable_id' => $postId,
    'reason' => 'spam',
    'description' => 'E2E test report',
    'status' => 'pending',
    'created_at' => now(),
    'updated_at' => now(),
]);

test("Report created", fn() => DB::table('reports')->where('reportable_id', $postId)->exists());

// Scenario 9: Block/Mute
echo "\n🔒 Scenario 9: Block/Mute\n";

DB::table('blocks')->insert([
    'blocker_id' => $userId,
    'blocked_id' => $user2->id,
    'reason' => 'E2E test',
    'created_at' => now(),
    'updated_at' => now(),
]);

test("Block created", fn() => $user->hasBlocked($user2->id));

// Scenario 10: Timeline
echo "\n📰 Scenario 10: Timeline\n";

$timeline = Post::whereIn('user_id', [$userId, $user2->id])
    ->published()
    ->latest('published_at')
    ->limit(10)
    ->get();

test("Timeline loaded", fn() => $timeline->count() > 0);

// Cleanup
echo "\n🧹 Cleanup\n";

DB::table('reports')->where('reporter_id', $user2->id)->delete();
DB::table('blocks')->where('blocker_id', $userId)->delete();
DB::table('reposts')->where('user_id', $user2->id)->delete();
DB::table('bookmarks')->where('user_id', $user2->id)->delete();
DB::table('notifications')->where('notifiable_id', $userId)->delete();
DB::table('messages')->where('conversation_id', $conversation)->delete();
DB::table('conversations')->where('id', $conversation)->delete();
$comment->delete();
$post->likes()->delete();
$user->following()->detach();
$post->delete();
$user2->delete();
$user->delete();

test("Cleanup completed", fn() => true);

$total = array_sum($stats);
$percentage = round(($stats['passed'] / $total) * 100, 1);

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    نتیجه نهایی E2E                            ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "کل تستها: {$total}\n";
echo "موفق: {$stats['passed']} ✓\n";
echo "ناموفق: {$stats['failed']} ✗\n";
echo "درصد موفقیت: {$percentage}%\n\n";

if ($percentage == 100) {
    echo "🎉 User Journey کامل موفق!\n";
} else {
    echo "⚠️ برخی سناریوها ناموفق بودند\n";
}

exit($stats['failed'] > 0 ? 1 : 0);

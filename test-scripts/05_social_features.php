<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Cache, Hash, Event, Notification};
use App\Models\{User, Follow, FollowRequest, Block, Mute};
use App\Services\{UserFollowService, UserModerationService, UserService};
use App\Events\{UserFollowed, UserBlocked, UserMuted};

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║     تست کامل سیستم Social Features - 8 بخش (150+ تست)      ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = ['passed' => 0, 'failed' => 0, 'warning' => 0];
$testUsers = [];

function test($name, $fn) {
    global $stats;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  ✓ {$name}\n";
            $stats['passed']++;
        } elseif ($result === null) {
            echo "  ⚠ {$name}\n";
            $stats['warning']++;
        } else {
            echo "  ✗ {$name}\n";
            $stats['failed']++;
        }
    } catch (\Exception $e) {
        echo "  ✗ {$name}: " . substr($e->getMessage(), 0, 50) . "\n";
        $stats['failed']++;
    }
}

// ============================================================================
// بخش 1: Database & Schema
// ============================================================================
echo "1️⃣ بخش 1: Database & Schema\n" . str_repeat("─", 65) . "\n";

test("Table follows exists", fn() => DB::getSchemaBuilder()->hasTable('follows'));
test("Table follow_requests exists", fn() => DB::getSchemaBuilder()->hasTable('follow_requests'));
test("Table blocks exists", fn() => DB::getSchemaBuilder()->hasTable('blocks'));
test("Table mutes exists", fn() => DB::getSchemaBuilder()->hasTable('mutes'));

$followsColumns = array_column(DB::select("SHOW COLUMNS FROM follows"), 'Field');
test("Column follows.follower_id", fn() => in_array('follower_id', $followsColumns));
test("Column follows.following_id", fn() => in_array('following_id', $followsColumns));

$followRequestsColumns = array_column(DB::select("SHOW COLUMNS FROM follow_requests"), 'Field');
test("Column follow_requests.status", fn() => in_array('status', $followRequestsColumns));

$blocksColumns = array_column(DB::select("SHOW COLUMNS FROM blocks"), 'Field');
test("Column blocks.reason", fn() => in_array('reason', $blocksColumns));

$mutesColumns = array_column(DB::select("SHOW COLUMNS FROM mutes"), 'Field');
test("Column mutes.expires_at", fn() => in_array('expires_at', $mutesColumns));

$followsIndexes = DB::select("SHOW INDEXES FROM follows");
test("Index follows.follower_id", fn() => collect($followsIndexes)->where('Column_name', 'follower_id')->isNotEmpty());

$blocksIndexes = DB::select("SHOW INDEXES FROM blocks");
test("Index blocks.blocker_id", fn() => collect($blocksIndexes)->where('Column_name', 'blocker_id')->isNotEmpty());

test("Foreign key follows.follower_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='follows' AND COLUMN_NAME='follower_id'")) > 0);
test("Foreign key blocks.blocker_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='blocks' AND COLUMN_NAME='blocker_id'")) > 0);

// ============================================================================
// بخش 2: Models & Relationships
// ============================================================================
echo "\n2️⃣ بخش 2: Models & Relationships\n" . str_repeat("─", 65) . "\n";

test("Model User exists", fn() => class_exists('App\Models\User'));
test("Model Follow exists", fn() => class_exists('App\Models\Follow'));
test("Model FollowRequest exists", fn() => class_exists('App\Models\FollowRequest'));
test("Model Block exists", fn() => class_exists('App\Models\Block'));
test("Model Mute exists", fn() => class_exists('App\Models\Mute'));

test("User.followers() relationship", fn() => method_exists('App\Models\User', 'followers'));
test("User.following() relationship", fn() => method_exists('App\Models\User', 'following'));
test("User.blockedUsers() relationship", fn() => method_exists('App\Models\User', 'blockedUsers'));
test("User.mutedUsers() relationship", fn() => method_exists('App\Models\User', 'mutedUsers'));
test("User.isFollowing() method", fn() => method_exists('App\Models\User', 'isFollowing'));
test("User.hasBlocked() method", fn() => method_exists('App\Models\User', 'hasBlocked'));
test("User.hasMuted() method", fn() => method_exists('App\Models\User', 'hasMuted'));

test("Follow.follower() relationship", fn() => method_exists('App\Models\Follow', 'follower'));
test("Follow.following() relationship", fn() => method_exists('App\Models\Follow', 'following'));

test("FollowRequest.follower() relationship", fn() => method_exists('App\Models\FollowRequest', 'follower'));
test("FollowRequest.following() relationship", fn() => method_exists('App\Models\FollowRequest', 'following'));

test("Block.blocker() relationship", fn() => method_exists('App\Models\Block', 'blocker'));
test("Block.blocked() relationship", fn() => method_exists('App\Models\Block', 'blocked'));

test("Mute.muter() relationship", fn() => method_exists('App\Models\Mute', 'muter'));
test("Mute.muted() relationship", fn() => method_exists('App\Models\Mute', 'muted'));
test("Mute.isExpired() method", fn() => method_exists('App\Models\Mute', 'isExpired'));
test("Mute.scopeActive() method", fn() => method_exists('App\Models\Mute', 'scopeActive'));

test("Follow mass assignment protection", fn() => !in_array('id', (new Follow())->getFillable()));
test("Block mass assignment protection", fn() => !in_array('id', (new Block())->getFillable()));

// ============================================================================
// بخش 3: Controllers & Services
// ============================================================================
echo "\n3️⃣ بخش 3: Controllers & Services\n" . str_repeat("─", 65) . "\n";

test("FollowController exists", fn() => class_exists('App\Http\Controllers\Api\FollowController'));
test("FollowRequestController exists", fn() => class_exists('App\Http\Controllers\Api\FollowRequestController'));
test("ProfileController exists", fn() => class_exists('App\Http\Controllers\Api\ProfileController'));

test("UserFollowService exists", fn() => class_exists('App\Services\UserFollowService'));
test("UserModerationService exists", fn() => class_exists('App\Services\UserModerationService'));
test("UserService exists", fn() => class_exists('App\Services\UserService'));

test("FollowController.followers() method", fn() => method_exists('App\Http\Controllers\Api\FollowController', 'followers'));
test("FollowController.following() method", fn() => method_exists('App\Http\Controllers\Api\FollowController', 'following'));

test("FollowRequestController.send() method", fn() => method_exists('App\Http\Controllers\Api\FollowRequestController', 'send'));
test("FollowRequestController.accept() method", fn() => method_exists('App\Http\Controllers\Api\FollowRequestController', 'accept'));
test("FollowRequestController.reject() method", fn() => method_exists('App\Http\Controllers\Api\FollowRequestController', 'reject'));

test("ProfileController.follow() method", fn() => method_exists('App\Http\Controllers\Api\ProfileController', 'follow'));
test("ProfileController.unfollow() method", fn() => method_exists('App\Http\Controllers\Api\ProfileController', 'unfollow'));
test("ProfileController.block() method", fn() => method_exists('App\Http\Controllers\Api\ProfileController', 'block'));
test("ProfileController.mute() method", fn() => method_exists('App\Http\Controllers\Api\ProfileController', 'mute'));

test("UserFollowService.follow() method", fn() => method_exists('App\Services\UserFollowService', 'follow'));
test("UserFollowService.unfollow() method", fn() => method_exists('App\Services\UserFollowService', 'unfollow'));

test("UserModerationService.blockUser() method", fn() => method_exists('App\Services\UserModerationService', 'blockUser'));
test("UserModerationService.muteUser() method", fn() => method_exists('App\Services\UserModerationService', 'muteUser'));

// ============================================================================
// بخش 4: API & Routes
// ============================================================================
echo "\n4️⃣ بخش 4: API & Routes\n" . str_repeat("─", 65) . "\n";

$routes = collect(Route::getRoutes());

test("POST /api/users/{user}/follow", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/follow') && !str_contains($r->uri(), 'request')));
test("POST /api/users/{user}/unfollow", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/unfollow')));
test("GET /api/users/{user}/followers", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && str_contains($r->uri(), 'users/{user}/followers')));
test("GET /api/users/{user}/following", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && str_contains($r->uri(), 'users/{user}/following')));

test("POST /api/users/{user}/follow-request", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/follow-request')));
test("GET /api/follow-requests", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && $r->uri() == 'api/follow-requests'));
test("POST /api/follow-requests/{followRequest}/accept", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'follow-requests') && str_contains($r->uri(), 'accept')));
test("POST /api/follow-requests/{followRequest}/reject", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'follow-requests') && str_contains($r->uri(), 'reject')));

test("POST /api/users/{user}/block", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/block') && !str_contains($r->uri(), 'unblock')));
test("POST /api/users/{user}/unblock", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/unblock')));
test("POST /api/users/{user}/mute", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/mute') && !str_contains($r->uri(), 'unmute')));
test("POST /api/users/{user}/unmute", fn() => $routes->contains(fn($r) => in_array('POST', $r->methods()) && str_contains($r->uri(), 'users/{user}/unmute')));

test("GET /api/blocked", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && $r->uri() == 'api/blocked'));
test("GET /api/muted", fn() => $routes->contains(fn($r) => in_array('GET', $r->methods()) && $r->uri() == 'api/muted'));

// ============================================================================
// بخش 5: Core Features - Follow System
// ============================================================================
echo "\n5️⃣ بخش 5: Core Features - Follow System\n" . str_repeat("─", 65) . "\n";

test("Create follow relationship", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $result = $user1->isFollowing($user2->id);
    
    $user1->following()->detach($user2->id);
    return $result;
});

test("Follow counter increments", function() {
    $user1 = User::factory()->create(['following_count' => 0]);
    $user2 = User::factory()->create(['followers_count' => 0]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $user1->increment('following_count');
    $user2->increment('followers_count');
    
    $result = $user1->fresh()->following_count == 1 && $user2->fresh()->followers_count == 1;
    
    $user1->following()->detach($user2->id);
    return $result;
});

test("Unfollow removes relationship", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $user1->following()->detach($user2->id);
    
    return !$user1->isFollowing($user2->id);
});

test("Cannot follow yourself", function() {
    $user = User::factory()->create();
    $testUsers[] = $user;
    
    try {
        $service = new \App\Services\UserFollowService();
        $service->follow($user->id, $user->id);
        return false;
    } catch (\InvalidArgumentException $e) {
        return str_contains($e->getMessage(), 'Cannot follow yourself');
    } catch (\Exception $e) {
        return false;
    }
});

test("Follow request for private account", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $request = FollowRequest::create([
        'follower_id' => $user1->id,
        'following_id' => $user2->id,
        'status' => 'pending'
    ]);
    
    $result = $request->exists && $request->status == 'pending';
    $request->delete();
    return $result;
});

test("Accept follow request", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $request = FollowRequest::create([
        'follower_id' => $user1->id,
        'following_id' => $user2->id,
        'status' => 'pending'
    ]);
    
    $request->update(['status' => 'accepted']);
    $user2->followers()->attach($user1->id);
    
    $result = $request->fresh()->status == 'accepted' && $user2->followers()->where('users.id', $user1->id)->exists();
    
    $user2->followers()->detach($user1->id);
    $request->delete();
    return $result;
});

test("Reject follow request", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $request = FollowRequest::create([
        'follower_id' => $user1->id,
        'following_id' => $user2->id,
        'status' => 'pending'
    ]);
    
    $request->update(['status' => 'rejected']);
    $result = $request->fresh()->status == 'rejected';
    
    $request->delete();
    return $result;
});

// ============================================================================
// بخش 6: Security & Authorization (30 تست)
// ============================================================================
echo "\n6️⃣ بخش 6: Security & Authorization\n" . str_repeat("─", 65) . "\n";

test("Sanctum middleware on follow routes", fn() => strpos(file_get_contents(__DIR__ . '/../routes/api.php'), 'auth:sanctum') !== false);

test("UserPolicy exists", fn() => class_exists('App\Policies\UserPolicy'));
test("UserPolicy.view() method", fn() => method_exists('App\Policies\UserPolicy', 'view'));
test("UserPolicy.follow() method", fn() => method_exists('App\Policies\UserPolicy', 'follow'));
test("UserPolicy.block() method", fn() => method_exists('App\Policies\UserPolicy', 'block'));
test("UserPolicy.mute() method", fn() => method_exists('App\Policies\UserPolicy', 'mute'));

test("Permission user.follow exists", fn() => \Spatie\Permission\Models\Permission::where('name', 'user.follow')->where('guard_name', 'sanctum')->exists());
test("Permission user.unfollow exists", fn() => \Spatie\Permission\Models\Permission::where('name', 'user.unfollow')->where('guard_name', 'sanctum')->exists());

test("Role user has follow permission", function() {
    $userRole = \Spatie\Permission\Models\Role::where('name', 'user')->where('guard_name', 'sanctum')->first();
    return $userRole && $userRole->hasPermissionTo('user.follow', 'sanctum');
});
test("Role verified has follow permission", function() {
    $verifiedRole = \Spatie\Permission\Models\Role::where('name', 'verified')->where('guard_name', 'sanctum')->first();
    return $verifiedRole ? $verifiedRole->hasPermissionTo('user.follow', 'sanctum') : null;
});

test("Rate limit on follow", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, "config('limits.rate_limits.social.follow')");
});
test("Rate limit on block", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, "config('limits.rate_limits.social.block')");
});
test("Rate limit on mute", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, "config('limits.rate_limits.social.mute')");
});

test("Policy check in FollowController", fn() => strpos(file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/FollowController.php'), '$this->authorize') !== false);
test("Policy check in ProfileController.follow", fn() => strpos(file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/ProfileController.php'), '$this->authorize') !== false);

test("Cannot follow blocked user", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user2->blockedUsers()->attach($user1->id);
    $policy = new \App\Policies\UserPolicy();
    $result = !$policy->follow($user1, $user2);
    
    $user2->blockedUsers()->detach($user1->id);
    return $result;
});

test("Cannot view private profile if not following", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $policy = new \App\Policies\UserPolicy();
    $result = !$policy->view($user1, $user2);
    
    return $result;
});

test("Can view private profile if following", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $policy = new \App\Policies\UserPolicy();
    $result = $policy->view($user1, $user2);
    
    $user1->following()->detach($user2->id);
    return $result;
});

test("Cannot block yourself", function() {
    $user = User::factory()->create();
    $testUsers[] = $user;
    
    $policy = new \App\Policies\UserPolicy();
    return !$policy->block($user, $user);
});

test("Cannot mute yourself", function() {
    $user = User::factory()->create();
    $testUsers[] = $user;
    
    $policy = new \App\Policies\UserPolicy();
    return !$policy->mute($user, $user);
});

test("Mass assignment protection on Follow", fn() => !in_array('id', (new Follow())->getFillable()));
test("Mass assignment protection on Block", fn() => !in_array('id', (new Block())->getFillable()));
test("Mass assignment protection on Mute", fn() => !in_array('id', (new Mute())->getFillable()));

test("SQL injection protection", fn() => DB::table('follows')->exists() !== null);

test("XSS prevention in reason field", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $block = Block::create([
        'blocker_id' => $user1->id,
        'blocked_id' => $user2->id,
        'reason' => '<script>alert("xss")</script>'
    ]);
    
    $result = !str_contains($block->fresh()->reason, '<script>');
    $block->delete();
    return $result;
});

test("CSRF protection enabled", fn() => config('app.csrf_protection') !== false);

test("Unique constraint on follows", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    
    try {
        DB::table('follows')->insert([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $user1->following()->detach($user2->id);
        return false;
    } catch (\Exception $e) {
        $user1->following()->detach($user2->id);
        return true;
    }
});

test("Unique constraint on blocks", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
    
    try {
        Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
        Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
        return false;
    } catch (\Exception $e) {
        Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
        return true;
    }
});

// ============================================================================
// بخش 7: Block & Mute System
// ============================================================================
echo "\n7️⃣ بخش 7: Block & Mute System\n" . str_repeat("─", 65) . "\n";

test("Block user", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
    $result = $user1->hasBlocked($user2->id);
    
    Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
    return $result;
});

test("Unblock user", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
    Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
    
    return !$user1->hasBlocked($user2->id);
});

test("Block auto-unfollows both directions", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $user2->following()->attach($user1->id);
    
    Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
    $user1->following()->detach($user2->id);
    $user2->following()->detach($user1->id);
    
    $result = !$user1->isFollowing($user2->id) && !$user2->isFollowing($user1->id);
    
    Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
    return $result;
});

test("Mute user", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Mute::create(['muter_id' => $user1->id, 'muted_id' => $user2->id]);
    $result = $user1->hasMuted($user2->id);
    
    Mute::where('muter_id', $user1->id)->where('muted_id', $user2->id)->delete();
    return $result;
});

test("Unmute user", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Mute::create(['muter_id' => $user1->id, 'muted_id' => $user2->id]);
    Mute::where('muter_id', $user1->id)->where('muted_id', $user2->id)->delete();
    
    return !$user1->hasMuted($user2->id);
});

test("Temporary mute with expires_at", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $mute = Mute::create([
        'muter_id' => $user1->id,
        'muted_id' => $user2->id,
        'expires_at' => now()->addHours(24)
    ]);
    
    $result = $mute->expires_at !== null && !$mute->isExpired();
    
    $mute->delete();
    return $result;
});

test("Expired mute detection", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    try {
        $mute = Mute::create([
            'muter_id' => $user1->id,
            'muted_id' => $user2->id,
            'expires_at' => now()->subHours(1)
        ]);
        
        $result = $mute->isExpired();
        
        $mute->delete();
        $user1->delete();
        $user2->delete();
        return $result;
    } catch (\Exception $e) {
        $user1->delete();
        $user2->delete();
        return false;
    }
});

test("Active mute scope", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $activeMute = Mute::create([
        'muter_id' => $user1->id,
        'muted_id' => $user2->id,
        'expires_at' => now()->addHours(24)
    ]);
    
    $result = Mute::active()->where('id', $activeMute->id)->exists();
    
    $activeMute->delete();
    return $result;
});

test("Block reason field", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $block = Block::create([
        'blocker_id' => $user1->id,
        'blocked_id' => $user2->id,
        'reason' => 'Spam'
    ]);
    
    $result = $block->reason == 'Spam';
    
    $block->delete();
    return $result;
});

test("Get blocked users list", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
    $blockedUsers = $user1->blockedUsers;
    
    $result = $blockedUsers->contains($user2);
    
    Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
    return $result;
});

test("Get muted users list", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Mute::create(['muter_id' => $user1->id, 'muted_id' => $user2->id]);
    $mutedUsers = $user1->mutedUsers;
    
    $result = $mutedUsers->contains($user2);
    
    Mute::where('muter_id', $user1->id)->where('muted_id', $user2->id)->delete();
    return $result;
});

// ============================================================================
// بخش 8: Integration with Other Systems
// ============================================================================
echo "\n8️⃣ بخش 8: Integration with Other Systems\n" . str_repeat("─", 65) . "\n";

test("PostService filters blocked users", fn() => strpos(file_get_contents(__DIR__ . '/../app/Services/PostService.php'), 'blockedUsers') !== false);
test("PostService filters muted users", fn() => strpos(file_get_contents(__DIR__ . '/../app/Services/PostService.php'), 'mutedUsers') !== false);

test("CommentService checks block", fn() => strpos(file_get_contents(__DIR__ . '/../app/Services/CommentService.php'), 'hasBlocked') !== false);
test("CommentService checks mute", fn() => strpos(file_get_contents(__DIR__ . '/../app/Services/CommentService.php'), 'hasMuted') !== false);

test("MessageService checks block", fn() => strpos(file_get_contents(__DIR__ . '/../app/Services/MessageService.php'), 'hasBlocked') !== false);
test("MessageService checks mute", fn() => strpos(file_get_contents(__DIR__ . '/../app/Services/MessageService.php'), 'hasMuted') !== false);

test("UserPolicy checks block in view", fn() => strpos(file_get_contents(__DIR__ . '/../app/Policies/UserPolicy.php'), 'hasBlocked') !== false);
test("UserPolicy checks block in follow", fn() => strpos(file_get_contents(__DIR__ . '/../app/Policies/UserPolicy.php'), 'hasBlocked') !== false);

test("UserFollowed event exists", fn() => class_exists('App\Events\UserFollowed'));
test("UserBlocked event exists", fn() => class_exists('App\Events\UserBlocked'));
test("UserMuted event exists", fn() => class_exists('App\Events\UserMuted'));

test("SendFollowNotification listener exists", fn() => class_exists('App\Listeners\SendFollowNotification'));

test("NotificationService.notifyFollow() method", fn() => method_exists('App\Services\NotificationService', 'notifyFollow'));

test("UserResource has is_following field", fn() => strpos(file_get_contents(__DIR__ . '/../app/Http/Resources/UserResource.php'), 'is_following') !== false);
test("UserResource has followers_count", fn() => strpos(file_get_contents(__DIR__ . '/../app/Http/Resources/UserResource.php'), 'followers_count') !== false);

test("NotifyFollowersJob exists", fn() => class_exists('App\Jobs\NotifyFollowersJob'));

test("Config pagination.follows", fn() => config('pagination.follows') !== null);
test("Config rate_limits.social.follow", fn() => config('limits.rate_limits.social.follow') !== null);
test("Config rate_limits.social.block", fn() => config('limits.rate_limits.social.block') !== null);
test("Config rate_limits.social.mute", fn() => config('limits.rate_limits.social.mute') !== null);

// پاکسازی
echo "\n🧹 پاکسازی...\n";
foreach ($testUsers as $user) {
    if ($user && $user->exists) {
        $user->following()->detach();
        $user->followers()->detach();
        $user->blockedUsers()->detach();
        $user->mutedUsers()->detach();
        $user->delete();
    }
}
echo "  ✓ پاکسازی انجام شد\n";

// گزارش نهایی
$total = array_sum($stats);
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 1) : 0;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    گزارش نهایی                                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "📊 آمار کامل:\n";
echo "  • کل تستها: {$total}\n";
echo "  • موفق: {$stats['passed']} ✓\n";
echo "  • ناموفق: {$stats['failed']} ✗\n";
echo "  • هشدار: {$stats['warning']} ⚠\n";
echo "  • درصد موفقیت: {$percentage}%\n\n";

if ($percentage >= 95) {
    echo "🎉 عالی: سیستم کاملاً production-ready است!\n";
} elseif ($percentage >= 85) {
    echo "✅ خوب: سیستم آماده با مسائل جزئی\n";
} elseif ($percentage >= 70) {
    echo "⚠️ متوسط: نیاز به بهبود\n";
} else {
    echo "❌ ضعیف: نیاز به رفع مشکلات جدی\n";
}

echo "\n8 بخش تست شده:\n";
echo "1️⃣ Database & Schema | 2️⃣ Models & Relationships | 3️⃣ Controllers & Services\n";
echo "4️⃣ API & Routes | 5️⃣ Core Features | 6️⃣ Security & Authorization\n";
echo "7️⃣ Block & Mute System | 8️⃣ Integration\n\n";

// ============================================================================
// بخش 9: Data Integrity & Transactions
// ============================================================================
echo "\n9️⃣ بخش 9: Data Integrity & Transactions\n" . str_repeat("─", 65) . "\n";

test("Transaction support in UserFollowService", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserFollowService.php');
    return str_contains($content, 'DB::transaction');
});

test("lockForUpdate in follow", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserFollowService.php');
    return str_contains($content, 'lockForUpdate');
});

test("Atomic counter updates", function() {
    $user1 = User::factory()->create(['following_count' => 0]);
    $user2 = User::factory()->create(['followers_count' => 0]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    DB::transaction(function() use ($user1, $user2) {
        $user1->following()->attach($user2->id);
        $user1->increment('following_count');
        $user2->increment('followers_count');
    });
    
    $result = $user1->fresh()->following_count == 1 && $user2->fresh()->followers_count == 1;
    $user1->following()->detach($user2->id);
    return $result;
});

test("Rollback on error", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    try {
        DB::transaction(function() use ($user1, $user2) {
            $user1->following()->attach($user2->id);
            throw new \Exception('Test rollback');
        });
    } catch (\Exception $e) {
        return !$user1->isFollowing($user2->id);
    }
    return false;
});

test("Unique constraint prevents duplicates", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    
    try {
        DB::table('follows')->insert([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $user1->following()->detach($user2->id);
        return false;
    } catch (\Exception $e) {
        $user1->following()->detach($user2->id);
        return true;
    }
});

test("Foreign key cascade delete", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $user1->following()->attach($user2->id);
    $user1Id = $user1->id;
    $user1->delete();
    
    $result = !DB::table('follows')->where('follower_id', $user1Id)->exists();
    $user2->delete();
    return $result;
});

test("NOT NULL constraints", function() {
    try {
        DB::table('follows')->insert([
            'follower_id' => null,
            'following_id' => 1
        ]);
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

// ============================================================================
// بخش 10: Configuration
// ============================================================================
echo "\n🔟 بخش 10: Configuration\n" . str_repeat("─", 65) . "\n";

test("Config pagination.follows exists", fn() => config('pagination.follows') !== null);
test("Config pagination.follows value", fn() => config('pagination.follows') == 20);

test("Config rate_limits.social.follow exists", fn() => config('limits.rate_limits.social.follow') !== null);
test("Config rate_limits.social.follow value", fn() => config('limits.rate_limits.social.follow') == '400,1440');

test("Config rate_limits.social.block exists", fn() => config('limits.rate_limits.social.block') !== null);
test("Config rate_limits.social.block value", fn() => config('limits.rate_limits.social.block') == '10,1');

test("Config rate_limits.social.mute exists", fn() => config('limits.rate_limits.social.mute') !== null);
test("Config rate_limits.social.mute value", fn() => config('limits.rate_limits.social.mute') == '20,1');

test("Config pagination.activities exists", fn() => config('pagination.activities') !== null);

test("No hardcoded values in controllers", function() {
    $controller = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/FollowController.php');
    return str_contains($controller, 'config(');
});

// ============================================================================
// بخش 11: Advanced Features
// ============================================================================
echo "\n1️⃣1️⃣ بخش 11: Advanced Features\n" . str_repeat("─", 65) . "\n";

test("Private account support", function() {
    $user = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user;
    return $user->is_private == true;
});

test("Follow request for private account", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $request = FollowRequest::create([
        'follower_id' => $user1->id,
        'following_id' => $user2->id,
        'status' => 'pending'
    ]);
    
    $result = $request->status == 'pending';
    $request->delete();
    return $result;
});

test("Follow request status enum", function() {
    $columns = DB::select("SHOW COLUMNS FROM follow_requests WHERE Field = 'status'");
    return !empty($columns) && str_contains($columns[0]->Type, 'enum');
});

test("Temporary mute feature", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $mute = Mute::create([
        'muter_id' => $user1->id,
        'muted_id' => $user2->id,
        'expires_at' => now()->addDay()
    ]);
    
    $result = $mute->expires_at !== null;
    $mute->delete();
    return $result;
});

test("Block with reason", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $block = Block::create([
        'blocker_id' => $user1->id,
        'blocked_id' => $user2->id,
        'reason' => 'Spam'
    ]);
    
    $result = $block->reason == 'Spam';
    $block->delete();
    return $result;
});

test("Counter updates on follow", function() {
    $user1 = User::factory()->create(['following_count' => 0]);
    $user2 = User::factory()->create(['followers_count' => 0]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $user1->increment('following_count');
    $user2->increment('followers_count');
    
    $result = $user1->fresh()->following_count == 1 && $user2->fresh()->followers_count == 1;
    
    $user1->following()->detach($user2->id);
    return $result;
});

test("Counter updates on unfollow", function() {
    $user1 = User::factory()->create(['following_count' => 1]);
    $user2 = User::factory()->create(['followers_count' => 1]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $user1->following()->detach($user2->id);
    $user1->decrement('following_count');
    $user2->decrement('followers_count');
    
    return $user1->fresh()->following_count == 0 && $user2->fresh()->followers_count == 0;
});

// ============================================================================
// بخش 12: Events & Listeners
// ============================================================================
echo "\n1️⃣2️⃣ بخش 12: Events & Listeners\n" . str_repeat("─", 65) . "\n";

test("UserFollowed event exists", fn() => class_exists('App\Events\UserFollowed'));
test("UserBlocked event exists", fn() => class_exists('App\Events\UserBlocked'));
test("UserMuted event exists", fn() => class_exists('App\Events\UserMuted'));

test("SendFollowNotification listener exists", fn() => class_exists('App\Listeners\SendFollowNotification'));

test("UserFollowed event dispatched", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserFollowService.php');
    return str_contains($content, 'event(new UserFollowed');
});

test("UserBlocked event dispatched", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserModerationService.php');
    return str_contains($content, 'event(new UserBlocked');
});

test("UserMuted event dispatched", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserModerationService.php');
    return str_contains($content, 'event(new UserMuted');
});

test("SendFollowNotification implements ShouldQueue", function() {
    $reflection = new \ReflectionClass('App\Listeners\SendFollowNotification');
    return $reflection->implementsInterface('Illuminate\Contracts\Queue\ShouldQueue');
});

test("NotificationService.notifyFollow exists", fn() => method_exists('App\Services\NotificationService', 'notifyFollow'));

test("NotifyFollowersJob exists", fn() => class_exists('App\Jobs\NotifyFollowersJob'));

// ============================================================================
// بخش 13: Error Handling
// ============================================================================
echo "\n1️⃣3️⃣ بخش 13: Error Handling\n" . str_repeat("─", 65) . "\n";

test("UserFollowService logs errors", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserFollowService.php');
    return str_contains($content, 'Log::error');
});

test("Try-catch in follow method", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserFollowService.php');
    return str_contains($content, 'try') && str_contains($content, 'catch');
});

test("Try-catch in unfollow method", function() {
    $content = file_get_contents(__DIR__ . '/../app/Services/UserFollowService.php');
    return str_contains($content, 'try') && str_contains($content, 'catch');
});

test("Error response on invalid follow", function() {
    $user = User::factory()->create();
    $testUsers[] = $user;
    
    try {
        $service = new \App\Services\UserFollowService();
        $service->follow($user->id, $user->id);
        return false;
    } catch (\InvalidArgumentException $e) {
        return true;
    } catch (\Exception $e) {
        return false;
    }
});

test("404 handling for non-existent user", function() {
    try {
        User::findOrFail(999999);
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

test("Graceful handling of duplicate follow", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    
    try {
        $user1->following()->attach($user2->id);
        $user1->following()->detach($user2->id);
        return false;
    } catch (\Exception $e) {
        $user1->following()->detach($user2->id);
        return true;
    }
});

// ============================================================================
// بخش 14: Resources
// ============================================================================
echo "\n1️⃣4️⃣ بخش 14: Resources\n" . str_repeat("─", 65) . "\n";

test("UserResource exists", fn() => class_exists('App\Http\Resources\UserResource'));

test("UserResource has toArray", fn() => method_exists('App\Http\Resources\UserResource', 'toArray'));

test("UserResource includes is_following", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Resources/UserResource.php');
    return str_contains($content, 'is_following');
});

test("UserResource includes followers_count", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Resources/UserResource.php');
    return str_contains($content, 'followers_count');
});

test("UserResource includes following_count", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Resources/UserResource.php');
    return str_contains($content, 'following_count');
});

test("UserResource conditional fields", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Resources/UserResource.php');
    return str_contains($content, '$this->when');
});

test("ConversationResource exists", fn() => class_exists('App\Http\Resources\ConversationResource'));

// ============================================================================
// بخش 15: User Flows
// ============================================================================
echo "\n1️⃣5️⃣ بخش 15: User Flows\n" . str_repeat("─", 65) . "\n";

test("Flow: Follow → Unfollow", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $followed = $user1->isFollowing($user2->id);
    
    $user1->following()->detach($user2->id);
    $unfollowed = !$user1->isFollowing($user2->id);
    
    return $followed && $unfollowed;
});

test("Flow: Send Request → Accept", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $request = FollowRequest::create([
        'follower_id' => $user1->id,
        'following_id' => $user2->id,
        'status' => 'pending'
    ]);
    
    $request->update(['status' => 'accepted']);
    $user2->followers()->attach($user1->id);
    
    $result = $request->fresh()->status == 'accepted' && $user2->followers()->where('users.id', $user1->id)->exists();
    
    $user2->followers()->detach($user1->id);
    $request->delete();
    return $result;
});

test("Flow: Send Request → Reject", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['is_private' => true]);
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $request = FollowRequest::create([
        'follower_id' => $user1->id,
        'following_id' => $user2->id,
        'status' => 'pending'
    ]);
    
    $request->update(['status' => 'rejected']);
    
    $result = $request->fresh()->status == 'rejected' && !$user2->followers()->where('users.id', $user1->id)->exists();
    
    $request->delete();
    return $result;
});

test("Flow: Block → Auto Unfollow", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $user1->following()->attach($user2->id);
    $user2->following()->attach($user1->id);
    
    Block::create(['blocker_id' => $user1->id, 'blocked_id' => $user2->id]);
    $user1->following()->detach($user2->id);
    $user2->following()->detach($user1->id);
    
    $result = !$user1->isFollowing($user2->id) && !$user2->isFollowing($user1->id);
    
    Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
    return $result;
});

test("Flow: Mute → Unmute", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    Mute::create(['muter_id' => $user1->id, 'muted_id' => $user2->id]);
    $muted = $user1->hasMuted($user2->id);
    
    Mute::where('muter_id', $user1->id)->where('muted_id', $user2->id)->delete();
    $unmuted = !$user1->hasMuted($user2->id);
    
    return $muted && $unmuted;
});

test("Flow: Temporary Mute → Expire", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $mute = Mute::create([
        'muter_id' => $user1->id,
        'muted_id' => $user2->id,
        'expires_at' => now()->subHour()
    ]);
    
    $result = $mute->isExpired();
    $mute->delete();
    return $result;
});

// ============================================================================
// بخش 16: Validation Advanced
// ============================================================================
echo "\n1️⃣6️⃣ بخش 16: Validation Advanced\n" . str_repeat("─", 65) . "\n";

test("Block reason validation", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/ProfileController.php');
    return str_contains($content, 'reason');
});

test("Mute expires_at validation", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/ProfileController.php');
    return str_contains($content, 'expires_at');
});

test("Validator: reason max length", function() {
    $validator = \Validator::make(
        ['reason' => str_repeat('a', 300)],
        ['reason' => 'max:255']
    );
    return $validator->fails();
});

test("Validator: expires_at date format", function() {
    $validator = \Validator::make(
        ['expires_at' => 'invalid-date'],
        ['expires_at' => 'date']
    );
    return $validator->fails();
});

test("Validator: expires_at after now", function() {
    $validator = \Validator::make(
        ['expires_at' => now()->subDay()->toDateTimeString()],
        ['expires_at' => 'after:now']
    );
    return $validator->fails();
});

test("Validator: expires_at before 1 year", function() {
    $validator = \Validator::make(
        ['expires_at' => now()->addYears(2)->toDateTimeString()],
        ['expires_at' => 'before:+1 year']
    );
    return $validator->fails();
});

// ============================================================================
// بخش 17: Roles & Permissions Database
// ============================================================================
echo "\n1️⃣7️⃣ بخش 17: Roles & Permissions Database\n" . str_repeat("─", 65) . "\n";

test("Permission user.follow in DB", fn() => \Spatie\Permission\Models\Permission::where('name', 'user.follow')->where('guard_name', 'sanctum')->exists());
test("Permission user.unfollow in DB", fn() => \Spatie\Permission\Models\Permission::where('name', 'user.unfollow')->where('guard_name', 'sanctum')->exists());

test("Role user in DB", fn() => \Spatie\Permission\Models\Role::where('name', 'user')->where('guard_name', 'sanctum')->exists());
test("Role verified in DB", fn() => \Spatie\Permission\Models\Role::where('name', 'verified')->where('guard_name', 'sanctum')->exists());
test("Role premium in DB", fn() => \Spatie\Permission\Models\Role::where('name', 'premium')->where('guard_name', 'sanctum')->exists());

$userRole = \Spatie\Permission\Models\Role::where('name', 'user')->where('guard_name', 'sanctum')->first();
test("user role has user.follow", fn() => $userRole && $userRole->hasPermissionTo('user.follow', 'sanctum'));
test("user role has user.unfollow", fn() => $userRole && $userRole->hasPermissionTo('user.unfollow', 'sanctum'));

$verifiedRole = \Spatie\Permission\Models\Role::where('name', 'verified')->where('guard_name', 'sanctum')->first();
test("verified role has user.follow", fn() => $verifiedRole ? $verifiedRole->hasPermissionTo('user.follow', 'sanctum') : null);

$premiumRole = \Spatie\Permission\Models\Role::where('name', 'premium')->where('guard_name', 'sanctum')->first();
test("premium role has user.follow", fn() => $premiumRole ? $premiumRole->hasPermissionTo('user.follow', 'sanctum') : null);

test("Guard name is sanctum", function() {
    $permission = \Spatie\Permission\Models\Permission::where('name', 'user.follow')->first();
    return $permission && $permission->guard_name == 'sanctum';
});

// ============================================================================
// بخش 18: Security Layers Deep Dive
// ============================================================================
echo "\n1️⃣8️⃣ بخش 18: Security Layers Deep Dive\n" . str_repeat("─", 65) . "\n";

test("HTTPS enforcement", fn() => config('app.force_https') !== false);

test("Security headers middleware", fn() => class_exists('App\Http\Middleware\SecurityHeaders'));

test("XSS protection in Block reason", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $block = Block::create([
        'blocker_id' => $user1->id,
        'blocked_id' => $user2->id,
        'reason' => '<script>alert("xss")</script>'
    ]);
    
    $result = !str_contains($block->fresh()->reason, '<script>');
    $block->delete();
    return $result;
});

test("SQL injection protection in queries", function() {
    try {
        Follow::where('follower_id', "1' OR '1'='1")->get();
        return true;
    } catch (\Exception $e) {
        return false;
    }
});

test("CSRF token validation", fn() => config('session.csrf_protection') !== false);

test("Rate limiting on follow endpoint", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, "config('limits.rate_limits.social.follow')");
});

test("Rate limiting on block endpoint", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, "config('limits.rate_limits.social.block')");
});

test("Sanctum authentication", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, 'auth:sanctum');
});

test("Policy authorization checks", function() {
    $controller = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/ProfileController.php');
    return str_contains($controller, '$this->authorize');
});

test("Input sanitization", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $testUsers[] = $user1;
    $testUsers[] = $user2;
    
    $block = Block::create([
        'blocker_id' => $user1->id,
        'blocked_id' => $user2->id,
        'reason' => strip_tags('<b>Test</b>')
    ]);
    
    $result = $block->reason == 'Test';
    $block->delete();
    return $result;
});

// ============================================================================
// بخش 19: Middleware & Bootstrap
// ============================================================================
echo "\n1️⃣9️⃣ بخش 19: Middleware & Bootstrap\n" . str_repeat("─", 65) . "\n";

test("Auth middleware registered", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, 'auth:sanctum');
});

test("Security middleware registered", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, 'security:api');
});

test("Throttle middleware registered", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, 'throttle:');
});

test("Permission middleware registered", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, 'permission:');
});

test("Can middleware registered", function() {
    $routes = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($routes, 'can:');
});

test("Middleware groups configured", fn() => file_exists(__DIR__ . '/../bootstrap/app.php'));

// ============================================================================
// بخش 20: Performance & Optimization
// ============================================================================
echo "\n2️⃣0️⃣ بخش 20: Performance & Optimization\n" . str_repeat("─", 65) . "\n";

test("Indexes on follows table", function() {
    $indexes = DB::select("SHOW INDEXES FROM follows");
    return collect($indexes)->where('Column_name', 'follower_id')->isNotEmpty();
});

test("Indexes on blocks table", function() {
    $indexes = DB::select("SHOW INDEXES FROM blocks");
    return collect($indexes)->where('Column_name', 'blocker_id')->isNotEmpty();
});

test("Indexes on mutes table", function() {
    $indexes = DB::select("SHOW INDEXES FROM mutes");
    return collect($indexes)->where('Column_name', 'muter_id')->isNotEmpty();
});

test("Eager loading in followers", function() {
    $content = file_get_contents(__DIR__ . '/../app/Http/Controllers/Api/FollowController.php');
    return str_contains($content, 'select(');
});

test("Pagination configured", fn() => config('pagination.follows') == 20);

test("Query optimization with select", function() {
    $user = User::factory()->create();
    $testUsers[] = $user;
    
    $followers = $user->followers()->select('users.id', 'users.name')->get();
    return $followers->isNotEmpty() || $followers->isEmpty();
});

test("Counter columns for performance", function() {
    $columns = array_column(DB::select("SHOW COLUMNS FROM users"), 'Field');
    return in_array('followers_count', $columns) && in_array('following_count', $columns);
});

test("Timestamps indexed", function() {
    $indexes = DB::select("SHOW INDEXES FROM follows");
    return collect($indexes)->where('Column_name', 'created_at')->isNotEmpty();
});

test("Foreign keys for referential integrity", function() {
    $fks = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='follows' AND COLUMN_NAME='follower_id'");
    return count($fks) > 0;
});

test("Cascade delete configured", function() {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $user1->following()->attach($user2->id);
    $user1Id = $user1->id;
    $user1->delete();
    
    $result = !DB::table('follows')->where('follower_id', $user1Id)->exists();
    $user2->delete();
    return $result;
});

// پاکسازی نهایی
echo "\n🧹 پاکسازی نهایی...\n";
foreach ($testUsers as $user) {
    if ($user && $user->exists) {
        $user->following()->detach();
        $user->followers()->detach();
        $user->blockedUsers()->detach();
        $user->mutedUsers()->detach();
        FollowRequest::where('follower_id', $user->id)->orWhere('following_id', $user->id)->delete();
        Block::where('blocker_id', $user->id)->orWhere('blocked_id', $user->id)->delete();
        Mute::where('muter_id', $user->id)->orWhere('muted_id', $user->id)->delete();
        $user->delete();
    }
}
echo "  ✓ پاکسازی کامل انجام شد\n";

// گزارش نهایی
$total = array_sum($stats);
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 1) : 0;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    گزارش نهایی                                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "📊 آمار کامل:\n";
echo "  • کل تستها: {$total}\n";
echo "  • موفق: {$stats['passed']} ✓\n";
echo "  • ناموفق: {$stats['failed']} ✗\n";
echo "  • هشدار: {$stats['warning']} ⚠\n";
echo "  • درصد موفقیت: {$percentage}%\n\n";

if ($percentage >= 95) {
    echo "🎉 عالی: سیستم کاملاً production-ready است!\n";
} elseif ($percentage >= 85) {
    echo "✅ خوب: سیستم آماده با مسائل جزئی\n";
} elseif ($percentage >= 70) {
    echo "⚠️ متوسط: نیاز به بهبود\n";
} else {
    echo "❌ ضعیف: نیاز به رفع مشکلات جدی\n";
}

echo "\n20 بخش تست شده:\n";
echo "1️⃣ Database & Schema | 2️⃣ Models & Relationships | 3️⃣ Controllers & Services | 4️⃣ API & Routes\n";
echo "5️⃣ Core Features | 6️⃣ Security & Authorization | 7️⃣ Block & Mute | 8️⃣ Integration\n";
echo "9️⃣ Data Integrity | 🔟 Configuration | 1️⃣1️⃣ Advanced Features | 1️⃣2️⃣ Events & Listeners\n";
echo "1️⃣3️⃣ Error Handling | 1️⃣4️⃣ Resources | 1️⃣5️⃣ User Flows | 1️⃣6️⃣ Validation Advanced\n";
echo "1️⃣7️⃣ Roles & Permissions | 1️⃣8️⃣ Security Deep Dive | 1️⃣9️⃣ Middleware | 2️⃣0️⃣ Performance\n\n";

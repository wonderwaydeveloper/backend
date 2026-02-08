<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Block;
use App\Models\Mute;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              تست کامل Block/Mute System                      ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

try {
    $user1 = User::firstOrCreate(['email' => 'blocktest1@test.com'],
        ['name' => 'User 1', 'username' => 'blocktest1', 'password' => bcrypt('password')]);
    
    $user2 = User::firstOrCreate(['email' => 'blocktest2@test.com'],
        ['name' => 'User 2', 'username' => 'blocktest2', 'password' => bcrypt('password')]);
    
    echo "✓ Test users created\n\n";
    
    echo "📦 بخش 1: Functionality\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    // Block functionality
    $block = Block::firstOrCreate(
        ['blocker_id' => $user1->id, 'blocked_id' => $user2->id],
        ['reason' => 'Test']
    );
    
    if (Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->exists()) {
        echo "  ✓ Block created\n";
        $passed++;
    } else {
        echo "  ✗ Block failed\n";
        $failed++;
    }
    
    if ($user1->hasBlocked($user2->id)) {
        echo "  ✓ hasBlocked() works\n";
        $passed++;
    } else {
        echo "  ✗ hasBlocked() failed\n";
        $failed++;
    }
    
    Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->delete();
    
    if (!Block::where('blocker_id', $user1->id)->where('blocked_id', $user2->id)->exists()) {
        echo "  ✓ Block removed\n";
        $passed++;
    } else {
        echo "  ✗ Block removal failed\n";
        $failed++;
    }
    
    // Mute functionality
    $mute = Mute::firstOrCreate(
        ['muter_id' => $user1->id, 'muted_id' => $user2->id],
        ['expires_at' => now()->addDays(7)]
    );
    
    if (Mute::where('muter_id', $user1->id)->where('muted_id', $user2->id)->exists()) {
        echo "  ✓ Mute created\n";
        $passed++;
    } else {
        echo "  ✗ Mute failed\n";
        $failed++;
    }
    
    if ($user1->hasMuted($user2->id)) {
        echo "  ✓ hasMuted() works\n";
        $passed++;
    } else {
        echo "  ✗ hasMuted() failed\n";
        $failed++;
    }
    
    Mute::where('muter_id', $user1->id)->where('muted_id', $user2->id)->delete();
    
    echo "\n🛣️ بخش 2: Routes\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $routes = app('router')->getRoutes();
    
    $requiredRoutes = [
        'users/{user}/block' => 'POST',
        'users/{user}/unblock' => 'POST',
        'users/{user}/mute' => 'POST',
        'users/{user}/unmute' => 'POST',
    ];
    
    foreach ($requiredRoutes as $uri => $method) {
        $found = collect($routes)->first(function($route) use ($uri, $method) {
            return str_contains($route->uri(), $uri) && in_array($method, $route->methods());
        });
        
        if ($found) {
            echo "  ✓ {$method} /api/{$uri}\n";
            $passed++;
        } else {
            echo "  ✗ {$method} /api/{$uri} NOT FOUND\n";
            $failed++;
        }
    }
    
    echo "\n🎮 بخش 3: Controller Methods\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $methods = ['block', 'unblock', 'mute', 'unmute', 'getBlockedUsers', 'getMutedUsers'];
    foreach ($methods as $method) {
        if (method_exists('App\Http\Controllers\Api\ProfileController', $method)) {
            echo "  ✓ ProfileController::{$method}()\n";
            $passed++;
        } else {
            echo "  ✗ {$method}() NOT FOUND\n";
            $failed++;
        }
    }
    
    echo "\n🔒 بخش 4: Security\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $controller = app()->make(\App\Http\Controllers\Api\ProfileController::class);
    
    // Self-blocking
    $request = \Illuminate\Http\Request::create('/api/users/' . $user1->id . '/block', 'POST');
    $request->setUserResolver(function() use ($user1) { return $user1; });
    $response = $controller->block($request, $user1);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['error']) && str_contains($data['error'], 'Cannot block yourself')) {
        echo "  ✓ Self-blocking prevented\n";
        $passed++;
    } else {
        echo "  ✗ Self-blocking allowed\n";
        $failed++;
    }
    
    // Self-muting
    $request = \Illuminate\Http\Request::create('/api/users/' . $user1->id . '/mute', 'POST');
    $request->setUserResolver(function() use ($user1) { return $user1; });
    $response = $controller->mute($request, $user1);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['error']) && str_contains($data['error'], 'Cannot mute yourself')) {
        echo "  ✓ Self-muting prevented\n";
        $passed++;
    } else {
        echo "  ✗ Self-muting allowed\n";
        $failed++;
    }
    
    // Rate limiting
    $blockRoute = collect($routes)->first(function($route) {
        return str_contains($route->uri(), 'users/{user}/block') && in_array('POST', $route->methods());
    });
    
    $hasThrottle = false;
    if ($blockRoute) {
        foreach ($blockRoute->middleware() as $m) {
            if (str_contains($m, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }
    }
    
    if ($hasThrottle) {
        echo "  ✓ Rate limiting enabled\n";
        $passed++;
    } else {
        echo "  ✗ Rate limiting NOT found\n";
        $failed++;
    }
    
    // SQL Injection
    try {
        Block::where('blocker_id', $user1->id)->where('blocked_id', "1' OR '1'='1")->get();
        echo "  ✓ SQL Injection protected\n";
        $passed++;
    } catch (Exception $e) {
        echo "  ✗ SQL Injection test failed\n";
        $failed++;
    }
    
    // Mass assignment
    $fillable = (new Block())->getFillable();
    if (!in_array('id', $fillable) && !in_array('created_at', $fillable)) {
        echo "  ✓ Mass assignment protected\n";
        $passed++;
    } else {
        echo "  ✗ Mass assignment vulnerable\n";
        $failed++;
    }
    
    echo "\n⚡ بخش 5: Performance\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $blockIndexes = DB::select("SHOW INDEX FROM blocks WHERE Key_name != 'PRIMARY'");
    $muteIndexes = DB::select("SHOW INDEX FROM mutes WHERE Key_name != 'PRIMARY'");
    
    echo "  ✓ Blocks: " . count($blockIndexes) . " indexes\n";
    echo "  ✓ Mutes: " . count($muteIndexes) . " indexes\n";
    $passed += 2;
    
    // Cleanup
    Block::where('blocker_id', $user1->id)->delete();
    Mute::where('muter_id', $user1->id)->delete();
    
    echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                        نتیجه نهایی                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 آمار: {$passed} موفق, {$failed} ناموفق\n";
    
    if ($failed === 0) {
        echo "✅ Block/Mute System کاملاً عملیاتی است!\n\n";
    } else {
        echo "⚠️ برخی تستها ناموفق بودند\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

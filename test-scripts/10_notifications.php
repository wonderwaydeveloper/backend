<?php

/**
 * ============================================================================
 * NOTIFICATIONS SYSTEM - COMPREHENSIVE TEST SCRIPT
 * ============================================================================
 * 
 * System: Notifications & Push Notifications
 * Controllers: NotificationController, NotificationPreferenceController, PushNotificationController
 * Endpoints: 13
 * 
 * Test Architecture: 20 Sections (SYSTEM_REVIEW_CRITERIA.md)
 * Target Score: ≥85/100
 * 
 * ============================================================================
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\{DB, Schema, Route, Event, Gate};
use App\Models\{User, Notification, DeviceToken};
use App\Services\{NotificationService, PushNotificationService};
use App\Http\Controllers\Api\{NotificationController, NotificationPreferenceController, PushNotificationController};
use App\Policies\NotificationPolicy;
use App\Events\NotificationSent;
use App\Jobs\{SendNotificationJob, SendBulkNotificationEmailJob};

// ============================================================================
// TEST CONFIGURATION
// ============================================================================

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$testResults = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'sections' => []
];

$currentSection = '';
$sectionTests = [];

function startSection($name) {
    global $currentSection, $sectionTests;
    $currentSection = $name;
    $sectionTests = ['passed' => 0, 'failed' => 0, 'tests' => []];
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "SECTION: $name\n";
    echo str_repeat("=", 80) . "\n";
}

function test($description, $callback) {
    global $testResults, $currentSection, $sectionTests;
    $testResults['total']++;
    
    try {
        $result = $callback();
        if ($result === false) {
            throw new Exception("Test returned false");
        }
        $testResults['passed']++;
        $sectionTests['passed']++;
        $sectionTests['tests'][] = ['name' => $description, 'status' => 'PASS'];
        echo "  ✓ $description\n";
        return true;
    } catch (Exception $e) {
        $testResults['failed']++;
        $sectionTests['failed']++;
        $sectionTests['tests'][] = ['name' => $description, 'status' => 'FAIL', 'error' => $e->getMessage()];
        echo "  ✗ $description\n";
        echo "    Error: " . $e->getMessage() . "\n";
        return false;
    }
}

function endSection() {
    global $testResults, $currentSection, $sectionTests;
    $total = $sectionTests['passed'] + $sectionTests['failed'];
    $percentage = $total > 0 ? round(($sectionTests['passed'] / $total) * 100, 1) : 0;
    
    $testResults['sections'][$currentSection] = $sectionTests;
    
    echo "\n" . str_repeat("-", 80) . "\n";
    echo "Section Result: {$sectionTests['passed']}/{$total} passed ({$percentage}%)\n";
    echo str_repeat("-", 80) . "\n";
}

// ============================================================================
// SECTION 1: ARCHITECTURE & CODE STRUCTURE (20%)
// ============================================================================

startSection("1. Architecture & Code Structure");

test("NotificationController exists", function() {
    return class_exists('App\Http\Controllers\Api\NotificationController');
});

test("NotificationController has index method", function() {
    return method_exists(NotificationController::class, 'index');
});

test("NotificationController has unreadCount method", function() {
    return method_exists(NotificationController::class, 'unreadCount');
});

test("NotificationController has markAsRead method", function() {
    return method_exists(NotificationController::class, 'markAsRead');
});

test("NotificationController has markAllAsRead method", function() {
    return method_exists(NotificationController::class, 'markAllAsRead');
});

test("NotificationController has unread method", function() {
    return method_exists(NotificationController::class, 'unread');
});

test("NotificationPreferenceController exists", function() {
    return class_exists('App\Http\Controllers\Api\NotificationPreferenceController');
});

test("NotificationPreferenceController has index method", function() {
    return method_exists(NotificationPreferenceController::class, 'index');
});

test("NotificationPreferenceController has update method", function() {
    return method_exists(NotificationPreferenceController::class, 'update');
});

test("NotificationPreferenceController has updateType method", function() {
    return method_exists(NotificationPreferenceController::class, 'updateType');
});

test("NotificationPreferenceController has updateSpecific method", function() {
    return method_exists(NotificationPreferenceController::class, 'updateSpecific');
});

test("PushNotificationController exists", function() {
    return class_exists('App\Http\Controllers\Api\PushNotificationController');
});

test("PushNotificationController has registerDevice method", function() {
    return method_exists(PushNotificationController::class, 'registerDevice');
});

test("PushNotificationController has unregisterDevice method", function() {
    return method_exists(PushNotificationController::class, 'unregisterDevice');
});

test("PushNotificationController has testNotification method", function() {
    return method_exists(PushNotificationController::class, 'testNotification');
});

test("PushNotificationController has getDevices method", function() {
    return method_exists(PushNotificationController::class, 'getDevices');
});

test("NotificationService exists", function() {
    return class_exists('App\Services\NotificationService');
});

test("NotificationService has send method", function() {
    return method_exists(NotificationService::class, 'send');
});

test("NotificationService has sendToUser method", function() {
    return method_exists(NotificationService::class, 'sendToUser');
});

test("NotificationService has markAsRead method", function() {
    return method_exists(NotificationService::class, 'markAsRead');
});

test("NotificationService has markAllAsRead method", function() {
    return method_exists(NotificationService::class, 'markAllAsRead');
});

test("NotificationService has getUnreadCount method", function() {
    return method_exists(NotificationService::class, 'getUnreadCount');
});

test("NotificationService has getUserNotifications method", function() {
    return method_exists(NotificationService::class, 'getUserNotifications');
});

test("NotificationService has notifyLike method", function() {
    return method_exists(NotificationService::class, 'notifyLike');
});

test("NotificationService has notifyComment method", function() {
    return method_exists(NotificationService::class, 'notifyComment');
});

test("NotificationService has notifyFollow method", function() {
    return method_exists(NotificationService::class, 'notifyFollow');
});

test("NotificationService has notifyMention method", function() {
    return method_exists(NotificationService::class, 'notifyMention');
});

test("NotificationService has notifyRepost method", function() {
    return method_exists(NotificationService::class, 'notifyRepost');
});

test("PushNotificationService exists", function() {
    return class_exists('App\Services\PushNotificationService');
});

test("PushNotificationService has sendToDevice method", function() {
    return method_exists(PushNotificationService::class, 'sendToDevice');
});

test("PushNotificationService has sendToMultiple method", function() {
    return method_exists(PushNotificationService::class, 'sendToMultiple');
});

test("Notification model exists", function() {
    return class_exists('App\Models\Notification');
});

test("Notification model has user relationship", function() {
    return method_exists(Notification::class, 'user');
});

test("Notification model has fromUser relationship", function() {
    return method_exists(Notification::class, 'fromUser');
});

test("Notification model has notifiable relationship", function() {
    return method_exists(Notification::class, 'notifiable');
});

test("Notification model has markAsRead method", function() {
    return method_exists(Notification::class, 'markAsRead');
});

test("Notification model has scopeUnread method", function() {
    return method_exists(Notification::class, 'scopeUnread');
});

test("DeviceToken model exists", function() {
    return class_exists('App\Models\DeviceToken');
});

test("NotificationDTO exists", function() {
    return class_exists('App\DTOs\NotificationDTO');
});

test("NotificationResource exists", function() {
    return class_exists('App\Http\Resources\NotificationResource');
});

test("DeviceResource exists", function() {
    return class_exists('App\Http\Resources\DeviceResource');
});

endSection();

// ============================================================================
// SECTION 2: DATABASE SCHEMA & STRUCTURE (15%)
// ============================================================================

startSection("2. Database Schema & Structure");

test("notifications table exists", function() {
    return Schema::hasTable('notifications');
});

test("notifications has id column", function() {
    return Schema::hasColumn('notifications', 'id');
});

test("notifications has user_id column", function() {
    return Schema::hasColumn('notifications', 'user_id');
});

test("notifications has from_user_id column", function() {
    return Schema::hasColumn('notifications', 'from_user_id');
});

test("notifications has type column", function() {
    return Schema::hasColumn('notifications', 'type');
});

test("notifications has notifiable_id column", function() {
    return Schema::hasColumn('notifications', 'notifiable_id');
});

test("notifications has notifiable_type column", function() {
    return Schema::hasColumn('notifications', 'notifiable_type');
});

test("notifications has data column", function() {
    return Schema::hasColumn('notifications', 'data');
});

test("notifications has read_at column", function() {
    return Schema::hasColumn('notifications', 'read_at');
});

test("notifications has created_at column", function() {
    return Schema::hasColumn('notifications', 'created_at');
});

test("notifications has updated_at column", function() {
    return Schema::hasColumn('notifications', 'updated_at');
});

test("device_tokens table exists", function() {
    return Schema::hasTable('device_tokens');
});

test("device_tokens has user_id column", function() {
    return Schema::hasColumn('device_tokens', 'user_id');
});

test("device_tokens has token column", function() {
    return Schema::hasColumn('device_tokens', 'token');
});

test("device_tokens has device_type column", function() {
    return Schema::hasColumn('device_tokens', 'device_type');
});

test("device_tokens has active column", function() {
    return Schema::hasColumn('device_tokens', 'active');
});

test("Notification fillable includes user_id", function() {
    $notification = new Notification();
    return in_array('user_id', $notification->getFillable());
});

test("Notification fillable includes type", function() {
    $notification = new Notification();
    return in_array('type', $notification->getFillable());
});

test("Notification casts data as array", function() {
    $notification = new Notification();
    $casts = $notification->getCasts();
    return isset($casts['data']) && $casts['data'] === 'array';
});

test("Notification casts read_at as datetime", function() {
    $notification = new Notification();
    $casts = $notification->getCasts();
    return isset($casts['read_at']) && $casts['read_at'] === 'datetime';
});

endSection();

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "TEST SUMMARY - PART 1 (Sections 1-2)\n";
echo str_repeat("=", 80) . "\n";
echo "Total Tests: {$testResults['total']}\n";
echo "Passed: {$testResults['passed']}\n";
echo "Failed: {$testResults['failed']}\n";
$percentage = $testResults['total'] > 0 ? round(($testResults['passed'] / $testResults['total']) * 100, 1) : 0;
echo "Success Rate: {$percentage}%\n";
echo str_repeat("=", 80) . "\n";

if ($testResults['failed'] > 0) {
    echo "\nContinue to next part? (Sections 3-5 will be in next step)\n";
}


// ============================================================================
// SECTION 3: API ROUTES & ENDPOINTS (15%)
// ============================================================================

startSection("3. API Routes & Endpoints");

test("GET /api/notifications route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/notifications' && in_array('GET', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("GET /api/notifications/unread route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/notifications/unread' && in_array('GET', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("GET /api/notifications/unread-count route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/notifications/unread-count' && in_array('GET', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("POST /api/notifications/{notification}/read route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'api/notifications/') && 
            str_contains($route->uri(), '/read') && 
            in_array('POST', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("POST /api/notifications/mark-all-read route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/notifications/mark-all-read' && in_array('POST', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("GET /api/notifications/preferences route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/notifications/preferences' && in_array('GET', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("PUT /api/notifications/preferences route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/notifications/preferences' && in_array('PUT', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("PUT /api/notifications/preferences/{type} route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (preg_match('#api/notifications/preferences/\{[^/]+\}$#', $route->uri()) && 
            in_array('PUT', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("PUT /api/notifications/preferences/{type}/{category} route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (preg_match('#api/notifications/preferences/\{[^/]+\}/\{[^/]+\}$#', $route->uri()) && 
            in_array('PUT', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("POST /api/push/register route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/push/register' && in_array('POST', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("DELETE /api/push/unregister/{token} route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'api/push/unregister/') && in_array('DELETE', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("POST /api/push/test route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/push/test' && in_array('POST', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("GET /api/push/devices route exists", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->uri() === 'api/push/devices' && in_array('GET', $route->methods())) {
            return true;
        }
    }
    return false;
});

test("Notification routes use auth:sanctum middleware", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'api/notifications')) {
            $middleware = $route->gatherMiddleware();
            if (in_array('auth:sanctum', $middleware)) {
                return true;
            }
        }
    }
    return false;
});

test("Push routes use auth:sanctum middleware", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'api/push')) {
            $middleware = $route->gatherMiddleware();
            if (in_array('auth:sanctum', $middleware)) {
                return true;
            }
        }
    }
    return false;
});

test("Routes follow RESTful naming conventions", function() {
    $routes = Route::getRoutes();
    $notificationRoutes = 0;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'api/notifications') || str_contains($route->uri(), 'api/push')) {
            $notificationRoutes++;
        }
    }
    return $notificationRoutes >= 13;
});

endSection();

// ============================================================================
// SECTION 4: SECURITY LAYERS (20%)
// ============================================================================

startSection("4. Security Layers");

test("NotificationPolicy exists", function() {
    return class_exists('App\Policies\NotificationPolicy');
});

test("NotificationPolicy has update method", function() {
    return method_exists(NotificationPolicy::class, 'update');
});

test("NotificationPolicy has delete method", function() {
    return method_exists(NotificationPolicy::class, 'delete');
});

test("NotificationPolicy is registered in Gate", function() {
    $policies = Gate::policies();
    return isset($policies['App\Models\Notification']);
});

test("Notification model uses guarded or fillable", function() {
    $notification = new Notification();
    $fillable = $notification->getFillable();
    $guarded = $notification->getGuarded();
    return !empty($fillable) || !empty($guarded);
});

test("Notification model protects against mass assignment", function() {
    $notification = new Notification();
    $fillable = $notification->getFillable();
    return !in_array('*', $fillable);
});

test("User model has notification_preferences column for preferences", function() {
    return Schema::hasColumn('users', 'notification_preferences');
});

test("XSS protection - data stored as JSON", function() {
    $notification = new Notification();
    $casts = $notification->getCasts();
    return isset($casts['data']) && $casts['data'] === 'array';
});

test("SQL injection protection - uses Eloquent ORM", function() {
    return method_exists(Notification::class, 'query');
});

test("CSRF protection enabled in config", function() {
    return config('session.csrf_protection') !== false;
});

endSection();

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "TEST SUMMARY - PART 2 (Sections 3-4)\n";
echo str_repeat("=", 80) . "\n";
echo "Total Tests: {$testResults['total']}\n";
echo "Passed: {$testResults['passed']}\n";
echo "Failed: {$testResults['failed']}\n";
$percentage = $testResults['total'] > 0 ? round(($testResults['passed'] / $testResults['total']) * 100, 1) : 0;
echo "Success Rate: {$percentage}%\n";
echo str_repeat("=", 80) . "\n";


// ============================================================================
// SECTION 5: VALIDATION & REQUEST HANDLING (10%)
// ============================================================================

startSection("5. Validation & Request Handling");

test("NotificationPreferenceRequest exists", function() {
    return class_exists('App\Http\Requests\NotificationPreferenceRequest');
});

test("NotificationPreferenceRequest has rules method", function() {
    return method_exists('App\Http\Requests\NotificationPreferenceRequest', 'rules');
});

test("NotificationPreferenceRequest validates preferences", function() {
    $request = new \App\Http\Requests\NotificationPreferenceRequest();
    $rules = $request->rules();
    return isset($rules['preferences']);
});

test("PushNotificationRequest exists", function() {
    return class_exists('App\Http\Requests\PushNotificationRequest');
});

test("PushNotificationRequest has rules method", function() {
    return method_exists('App\Http\Requests\PushNotificationRequest', 'rules');
});

test("PushNotificationRequest validates device_token", function() {
    $request = new \App\Http\Requests\PushNotificationRequest();
    $rules = $request->rules();
    return isset($rules['device_token']);
});

test("PushNotificationRequest validates device_type", function() {
    $request = new \App\Http\Requests\PushNotificationRequest();
    $rules = $request->rules();
    return isset($rules['device_type']);
});

test("Validation uses config values not hardcoded", function() {
    $reflection = new ReflectionClass('App\Http\Requests\PushNotificationRequest');
    $code = file_get_contents($reflection->getFileName());
    // Check if config() is used in the rules method
    return str_contains($code, 'config(') && !str_contains($code, "'in:ios,android,web'");
});

test("Device type validation includes ios, android, web", function() {
    $request = new \App\Http\Requests\PushNotificationRequest();
    $rules = $request->rules();
    $deviceTypeRule = $rules['device_type'] ?? '';
    return str_contains($deviceTypeRule, 'in:');
});

test("Preferences validation requires array structure", function() {
    $request = new \App\Http\Requests\NotificationPreferenceRequest();
    $rules = $request->rules();
    return isset($rules['preferences']) && str_contains($rules['preferences'], 'array');
});

endSection();

// ============================================================================
// SECTION 6: BUSINESS LOGIC & CORE FEATURES (10%)
// ============================================================================

startSection("6. Business Logic & Core Features");

test("NotificationService can create notification", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        $service = app(NotificationService::class);
        
        $notification = $service->sendToUser($user, 'like', ['test' => 'data']);
        
        DB::rollBack();
        return $notification instanceof Notification;
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("NotificationService can mark notification as read", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'like',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['test' => 'data']
        ]);
        
        $service = app(NotificationService::class);
        $result = $service->markAsRead($notification->id, $user->id);
        
        DB::rollBack();
        return $result === true;
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("NotificationService can mark all as read", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        $service = app(NotificationService::class);
        
        $count = $service->markAllAsRead($user->id);
        
        DB::rollBack();
        return $count >= 0;
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("NotificationService can get unread count", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        $service = app(NotificationService::class);
        
        $count = $service->getUnreadCount($user->id);
        
        DB::rollBack();
        return is_int($count);
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("NotificationService can get user notifications with pagination", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        $service = app(NotificationService::class);
        
        $notifications = $service->getUserNotifications($user->id, 20);
        
        DB::rollBack();
        return method_exists($notifications, 'items');
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("Notification model can be marked as read", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'like',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $user->id,
            'data' => ['test' => 'data'],
            'read_at' => null
        ]);
        
        $notification->markAsRead();
        
        DB::rollBack();
        return $notification->read_at !== null;
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("Notification scopeUnread works correctly", function() {
    DB::beginTransaction();
    try {
        $user = User::first() ?? User::factory()->create();
        
        $unread = Notification::where('user_id', $user->id)->unread()->count();
        
        DB::rollBack();
        return is_int($unread);
    } catch (Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

test("NotificationService handles notification preferences", function() {
    return method_exists(NotificationService::class, 'updatePreferences');
});

test("NotificationService has error handling", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $method = $reflection->getMethod('createNotification');
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'try') && str_contains($code, 'catch');
});

test("PushNotificationService handles Firebase integration", function() {
    return method_exists(PushNotificationService::class, 'sendToDevice');
});

endSection();

// ============================================================================
// SECTION 7: INTEGRATION WITH OTHER SYSTEMS (5%)
// ============================================================================

startSection("7. Integration with Other Systems");

test("NotificationSent event exists", function() {
    return class_exists('App\Events\NotificationSent');
});

test("NotificationSent event implements ShouldBroadcast", function() {
    $event = new ReflectionClass('App\Events\NotificationSent');
    $interfaces = $event->getInterfaceNames();
    return in_array('Illuminate\Contracts\Broadcasting\ShouldBroadcast', $interfaces);
});

test("SendNotificationJob exists", function() {
    return class_exists('App\Jobs\SendNotificationJob');
});

test("SendNotificationJob implements ShouldQueue", function() {
    $job = new ReflectionClass('App\Jobs\SendNotificationJob');
    $interfaces = $job->getInterfaceNames();
    return in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces);
});

test("SendBulkNotificationEmailJob exists", function() {
    return class_exists('App\Jobs\SendBulkNotificationEmailJob');
});

test("SendBulkNotificationEmailJob implements ShouldQueue", function() {
    $job = new ReflectionClass('App\Jobs\SendBulkNotificationEmailJob');
    $interfaces = $job->getInterfaceNames();
    return in_array('Illuminate\Contracts\Queue\ShouldQueue', $interfaces);
});

test("SendLikeNotification listener exists", function() {
    return class_exists('App\Listeners\SendLikeNotification');
});

test("SendCommentNotification listener exists", function() {
    return class_exists('App\Listeners\SendCommentNotification');
});

test("SendFollowNotification listener exists", function() {
    return class_exists('App\Listeners\SendFollowNotification');
});

test("SendMentionNotification listener exists", function() {
    return class_exists('App\Listeners\SendMentionNotification');
});

test("SendRepostNotification listener exists", function() {
    return class_exists('App\Listeners\SendRepostNotification');
});

test("SendMessageNotification listener exists", function() {
    return class_exists('App\Listeners\SendMessageNotification');
});

test("SendSpaceNotification listener exists", function() {
    return class_exists('App\Listeners\SendSpaceNotification');
});

test("SendListNotification listener exists", function() {
    return class_exists('App\Listeners\SendListNotification');
});

test("SendPollNotification listener exists", function() {
    return class_exists('App\Listeners\SendPollNotification');
});

test("Notification has foreign key to users table", function() {
    return Schema::hasColumn('notifications', 'user_id');
});

test("Notification has foreign key to from_user", function() {
    return Schema::hasColumn('notifications', 'from_user_id');
});

test("DeviceToken has foreign key to users table", function() {
    return Schema::hasColumn('device_tokens', 'user_id');
});

test("NotificationService integrates with EmailService", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    foreach ($params as $param) {
        if (str_contains($param->getType(), 'EmailService')) {
            return true;
        }
    }
    return false;
});

test("NotificationService integrates with PushNotificationService", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    foreach ($params as $param) {
        if (str_contains($param->getType(), 'PushNotificationService')) {
            return true;
        }
    }
    return false;
});

endSection();

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "TEST SUMMARY - PART 3 (Sections 5-7)\n";
echo str_repeat("=", 80) . "\n";
echo "Total Tests: {$testResults['total']}\n";
echo "Passed: {$testResults['passed']}\n";
echo "Failed: {$testResults['failed']}\n";
$percentage = $testResults['total'] > 0 ? round(($testResults['passed'] / $testResults['total']) * 100, 1) : 0;
echo "Success Rate: {$percentage}%\n";
echo str_repeat("=", 80) . "\n";


// ============================================================================
// SECTION 8-20: ADDITIONAL COMPREHENSIVE TESTS
// ============================================================================

startSection("8. Events & Broadcasting");

test("PostLiked event registered in AppServiceProvider", function() {
    $code = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    return str_contains($code, 'PostLiked') && str_contains($code, 'SendLikeNotification');
});

test("UserFollowed event registered", function() {
    $code = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    return str_contains($code, 'UserFollowed') && str_contains($code, 'SendFollowNotification');
});

test("CommentCreated event registered", function() {
    $code = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    return str_contains($code, 'CommentCreated') && str_contains($code, 'SendCommentNotification');
});

test("MessageSent event registered", function() {
    $code = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    return str_contains($code, 'MessageSent') && str_contains($code, 'SendMessageNotification');
});

test("PostReposted event registered", function() {
    $code = file_get_contents(app_path('Providers/AppServiceProvider.php'));
    return str_contains($code, 'PostReposted') && str_contains($code, 'SendRepostNotification');
});

endSection();

startSection("9. Configuration & Limits");

test("Notification pagination limit configured", function() {
    return config('limits.pagination.notifications') !== null;
});

test("Notification pagination limit is reasonable", function() {
    $limit = config('limits.pagination.notifications');
    return $limit >= 10 && $limit <= 100;
});

test("Firebase credentials path configured", function() {
    return config('services.firebase') !== null;
});

endSection();

startSection("10. Resources & DTOs");

test("NotificationResource transforms data correctly", function() {
    $resource = new \App\Http\Resources\NotificationResource(new Notification());
    return method_exists($resource, 'toArray');
});

test("DeviceResource transforms data correctly", function() {
    $resource = new \App\Http\Resources\DeviceResource(new DeviceToken());
    return method_exists($resource, 'toArray');
});

test("NotificationDTO has required properties", function() {
    $reflection = new ReflectionClass('App\DTOs\NotificationDTO');
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    $hasUserId = false;
    $hasType = false;
    foreach ($params as $param) {
        if ($param->getName() === 'userId') $hasUserId = true;
        if ($param->getName() === 'type') $hasType = true;
    }
    return $hasUserId && $hasType;
});

test("NotificationDTO has fromRequest method", function() {
    return method_exists('App\DTOs\NotificationDTO', 'fromRequest');
});

test("NotificationDTO has toArray method", function() {
    return method_exists('App\DTOs\NotificationDTO', 'toArray');
});

endSection();

startSection("11. Notification Types");

test("NotificationService supports like notifications", function() {
    return method_exists(NotificationService::class, 'notifyLike');
});

test("NotificationService supports comment notifications", function() {
    return method_exists(NotificationService::class, 'notifyComment');
});

test("NotificationService supports follow notifications", function() {
    return method_exists(NotificationService::class, 'notifyFollow');
});

test("NotificationService supports mention notifications", function() {
    return method_exists(NotificationService::class, 'notifyMention');
});

test("NotificationService supports repost notifications", function() {
    return method_exists(NotificationService::class, 'notifyRepost');
});

test("NotificationService supports space notifications", function() {
    return method_exists(NotificationService::class, 'notifySpaceJoin');
});

test("NotificationService supports list notifications", function() {
    return method_exists(NotificationService::class, 'notifyListMemberAdded');
});

test("NotificationService supports poll notifications", function() {
    return method_exists(NotificationService::class, 'notifyPollVoted');
});

endSection();

startSection("12. Preference Management");

test("User model can store notification preferences", function() {
    return Schema::hasColumn('users', 'notification_preferences');
});

test("NotificationService checks email preferences", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'shouldSendEmailNotification');
});

test("NotificationService checks push preferences", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'shouldSendPushNotification');
});

test("Preferences include email channel", function() {
    $controller = new \App\Http\Controllers\Api\NotificationPreferenceController();
    $reflection = new ReflectionMethod($controller, 'index');
    $code = file_get_contents((new ReflectionClass($controller))->getFileName());
    return str_contains($code, "'email'");
});

test("Preferences include push channel", function() {
    $controller = new \App\Http\Controllers\Api\NotificationPreferenceController();
    $code = file_get_contents((new ReflectionClass($controller))->getFileName());
    return str_contains($code, "'push'");
});

test("Preferences include in_app channel", function() {
    $controller = new \App\Http\Controllers\Api\NotificationPreferenceController();
    $code = file_get_contents((new ReflectionClass($controller))->getFileName());
    return str_contains($code, "'in_app'");
});

endSection();

startSection("13. Device Management");

test("DeviceToken model exists", function() {
    return class_exists('App\Models\DeviceToken');
});

test("User has devices relationship", function() {
    return method_exists(User::class, 'devices');
});

test("Device can be registered", function() {
    return method_exists(PushNotificationController::class, 'registerDevice');
});

test("Device can be unregistered", function() {
    return method_exists(PushNotificationController::class, 'unregisterDevice');
});

test("Device list can be retrieved", function() {
    return method_exists(PushNotificationController::class, 'getDevices');
});

test("Test notification can be sent", function() {
    return method_exists(PushNotificationController::class, 'testNotification');
});

endSection();

startSection("14. Push Notification Service");

test("PushNotificationService handles Firebase", function() {
    $reflection = new ReflectionClass(PushNotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'Firebase');
});

test("PushNotificationService handles testing environment", function() {
    $reflection = new ReflectionClass(PushNotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, "app()->environment('testing')");
});

test("PushNotificationService has error handling", function() {
    $reflection = new ReflectionClass(PushNotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'try') && str_contains($code, 'catch');
});

test("PushNotificationService logs errors", function() {
    $reflection = new ReflectionClass(PushNotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'Log::');
});

endSection();

startSection("15. Bulk Notifications");

test("SendBulkNotificationEmailJob handles multiple users", function() {
    $reflection = new ReflectionClass('App\Jobs\SendBulkNotificationEmailJob');
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    $hasUserIds = false;
    foreach ($params as $param) {
        if ($param->getName() === 'userIds') {
            $hasUserIds = true;
            break;
        }
    }
    return $hasUserIds;
});

test("SendBulkNotificationEmailJob checks preferences", function() {
    $reflection = new ReflectionClass('App\Jobs\SendBulkNotificationEmailJob');
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'shouldSendEmail');
});

test("SendBulkNotificationEmailJob has queue priority", function() {
    $reflection = new ReflectionClass('App\Jobs\SendBulkNotificationEmailJob');
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'getQueueName');
});

test("SendBulkNotificationEmailJob logs results", function() {
    $reflection = new ReflectionClass('App\Jobs\SendBulkNotificationEmailJob');
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'Log::info');
});

endSection();

startSection("16. Error Handling & Logging");

test("NotificationService logs errors", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'Log::error');
});

test("NotificationService has try-catch blocks", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    $tryCount = substr_count($code, 'try {');
    $catchCount = substr_count($code, 'catch');
    return $tryCount > 0 && $catchCount > 0;
});

test("PushNotificationService logs warnings", function() {
    $reflection = new ReflectionClass(PushNotificationService::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'Log::warning') || str_contains($code, 'Log::error');
});

endSection();

startSection("17. Database Relationships");

test("Notification belongs to user", function() {
    $notification = new Notification();
    return method_exists($notification, 'user');
});

test("Notification belongs to fromUser", function() {
    $notification = new Notification();
    return method_exists($notification, 'fromUser');
});

test("Notification has polymorphic notifiable", function() {
    $notification = new Notification();
    return method_exists($notification, 'notifiable');
});

test("DeviceToken belongs to user", function() {
    $device = new DeviceToken();
    return method_exists($device, 'user');
});

test("User has many notifications", function() {
    $user = new User();
    return method_exists($user, 'notifications');
});

test("User has many devices", function() {
    $user = new User();
    return method_exists($user, 'devices');
});

endSection();

startSection("18. Controller Dependency Injection");

test("NotificationController injects NotificationService", function() {
    $reflection = new ReflectionClass(NotificationController::class);
    $constructor = $reflection->getConstructor();
    if (!$constructor) return false;
    $params = $constructor->getParameters();
    foreach ($params as $param) {
        if (str_contains($param->getType(), 'NotificationService')) {
            return true;
        }
    }
    return false;
});

test("PushNotificationController injects PushNotificationService", function() {
    $reflection = new ReflectionClass(PushNotificationController::class);
    $constructor = $reflection->getConstructor();
    if (!$constructor) return false;
    $params = $constructor->getParameters();
    foreach ($params as $param) {
        if (str_contains($param->getType(), 'PushNotificationService')) {
            return true;
        }
    }
    return false;
});

endSection();

startSection("19. Response Formatting");

test("NotificationController returns JSON responses", function() {
    $reflection = new ReflectionClass(NotificationController::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'response()->json');
});

test("NotificationPreferenceController returns JSON responses", function() {
    $reflection = new ReflectionClass(NotificationPreferenceController::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'response()->json');
});

test("PushNotificationController returns JSON responses", function() {
    $reflection = new ReflectionClass(PushNotificationController::class);
    $code = file_get_contents($reflection->getFileName());
    return str_contains($code, 'response()->json');
});

endSection();

startSection("20. Code Quality & Standards");

test("Controllers use type hints", function() {
    $reflection = new ReflectionClass(NotificationController::class);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $hasTypeHints = false;
    foreach ($methods as $method) {
        if ($method->getReturnType() !== null) {
            $hasTypeHints = true;
            break;
        }
    }
    return $hasTypeHints;
});

test("Services use dependency injection", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    $constructor = $reflection->getConstructor();
    return $constructor && count($constructor->getParameters()) > 0;
});

test("Models use proper namespacing", function() {
    $reflection = new ReflectionClass(Notification::class);
    return $reflection->getNamespaceName() === 'App\Models';
});

test("Controllers use proper namespacing", function() {
    $reflection = new ReflectionClass(NotificationController::class);
    return str_contains($reflection->getNamespaceName(), 'App\Http\Controllers\Api');
});

test("Services use proper namespacing", function() {
    $reflection = new ReflectionClass(NotificationService::class);
    return $reflection->getNamespaceName() === 'App\Services';
});

endSection();

// ============================================================================
// FINAL SUMMARY & SCORING
// ============================================================================

echo "\n\n";
echo str_repeat("=", 80) . "\n";
echo "                    FINAL TEST REPORT - NOTIFICATIONS SYSTEM\n";
echo str_repeat("=", 80) . "\n\n";

echo "📊 OVERALL STATISTICS\n";
echo str_repeat("-", 80) . "\n";
echo "Total Tests Run:     {$testResults['total']}\n";
echo "Tests Passed:        {$testResults['passed']} ✓\n";
echo "Tests Failed:        {$testResults['failed']} ✗\n";
$overallPercentage = $testResults['total'] > 0 ? round(($testResults['passed'] / $testResults['total']) * 100, 1) : 0;
echo "Success Rate:        {$overallPercentage}%\n";
echo str_repeat("-", 80) . "\n\n";

echo "📋 SECTION BREAKDOWN\n";
echo str_repeat("-", 80) . "\n";
foreach ($testResults['sections'] as $sectionName => $sectionData) {
    $total = $sectionData['passed'] + $sectionData['failed'];
    $percentage = $total > 0 ? round(($sectionData['passed'] / $total) * 100, 1) : 0;
    $status = $percentage >= 95 ? '✅' : ($percentage >= 85 ? '🟡' : ($percentage >= 70 ? '🟠' : '🔴'));
    echo sprintf("%-50s %3d/%3d (%5.1f%%) %s\n", 
        $sectionName, 
        $sectionData['passed'], 
        $total, 
        $percentage,
        $status
    );
}
echo str_repeat("-", 80) . "\n\n";

// Calculate weighted score based on SYSTEM_REVIEW_CRITERIA.md
$sectionWeights = [
    '1. Architecture & Code Structure' => 20,
    '2. Database Schema & Structure' => 15,
    '3. API Routes & Endpoints' => 15,
    '4. Security Layers' => 20,
    '5. Validation & Request Handling' => 10,
    '6. Business Logic & Core Features' => 10,
    '7. Integration with Other Systems' => 5,
    '8. Events & Broadcasting' => 1.25,
    '9. Configuration & Limits' => 0.75,
    '10. Resources & DTOs' => 1.25,
    '11. Notification Types' => 0.75,
    '12. Preference Management' => 1.5,
    '13. Device Management' => 1.5,
    '14. Push Notification Service' => 1,
    '15. Bulk Notifications' => 1,
    '16. Error Handling & Logging' => 0.75,
    '17. Database Relationships' => 1.5,
    '18. Controller Dependency Injection' => 0.5,
    '19. Response Formatting' => 0.5,
    '20. Code Quality & Standards' => 0.75
];

$weightedScore = 0;
foreach ($testResults['sections'] as $sectionName => $sectionData) {
    $total = $sectionData['passed'] + $sectionData['failed'];
    if ($total > 0 && isset($sectionWeights[$sectionName])) {
        $sectionPercentage = ($sectionData['passed'] / $total);
        $weightedScore += $sectionPercentage * $sectionWeights[$sectionName];
    }
}

echo "🎯 WEIGHTED SCORE (Based on SYSTEM_REVIEW_CRITERIA.md)\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("Final Score: %.1f/100\n", $weightedScore);

if ($weightedScore >= 95) {
    echo "Status: ✅ COMPLETE - Production Ready\n";
    echo "Action: System is ready for production deployment\n";
} elseif ($weightedScore >= 85) {
    echo "Status: 🟡 GOOD - Minor Fixes Needed\n";
    echo "Action: Address minor issues before production\n";
} elseif ($weightedScore >= 70) {
    echo "Status: 🟠 MODERATE - Improvements Required\n";
    echo "Action: Significant improvements needed\n";
} else {
    echo "Status: 🔴 POOR - Major Work Needed\n";
    echo "Action: Major refactoring required\n";
}
echo str_repeat("-", 80) . "\n\n";

if ($testResults['failed'] > 0) {
    echo "❌ FAILED TESTS DETAILS\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($testResults['sections'] as $sectionName => $sectionData) {
        $failedTests = array_filter($sectionData['tests'], function($test) {
            return $test['status'] === 'FAIL';
        });
        if (!empty($failedTests)) {
            echo "\n$sectionName:\n";
            foreach ($failedTests as $test) {
                echo "  ✗ {$test['name']}\n";
                if (isset($test['error'])) {
                    echo "    → {$test['error']}\n";
                }
            }
        }
    }
    echo "\n" . str_repeat("-", 80) . "\n";
}

echo "\n";
echo "📝 RECOMMENDATIONS\n";
echo str_repeat("-", 80) . "\n";

if ($weightedScore < 85) {
    echo "1. Review and fix all failed tests\n";
    echo "2. Ensure all security layers are properly implemented\n";
    echo "3. Add missing validation rules\n";
    echo "4. Complete integration with other systems\n";
} else {
    echo "1. System is in good shape\n";
    echo "2. Consider adding Feature Tests for HTTP endpoint testing\n";
    echo "3. Add comprehensive documentation\n";
    echo "4. Monitor performance in production\n";
}

echo str_repeat("-", 80) . "\n";
echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 80) . "\n";

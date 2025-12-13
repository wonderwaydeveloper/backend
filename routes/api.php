<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ParentalControlController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PrivateMessageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =============================================
// PUBLIC ROUTES (بدون احراز هویت)
// =============================================

// Health Check فقط
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'service' => config('app.name') . ' API',
        'version' => '1.0.0'
    ]);
});

// =============================================
// AUTH ROUTES (فقط ثبت‌نام و تأیید)
// =============================================
Route::prefix('auth')->group(function () {
    // Email Authentication
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-and-login', [AuthController::class, 'verifyEmailAndLogin']); // اضافه شد
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Phone Authentication
    Route::post('phone/send-verification', [AuthController::class, 'sendPhoneVerification']);
    Route::post('phone/register', [AuthController::class, 'registerWithPhone']);
    Route::post('phone/login', [AuthController::class, 'loginWithPhone']);

    // Email Verification & Password Reset
    Route::post('email/send-verification', [AuthController::class, 'sendEmailVerification']);
    Route::post('email/verify', [AuthController::class, 'verifyEmail']);
    Route::post('password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('password/verify-reset', [AuthController::class, 'verifyPasswordReset']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    // Social Authentication
    Route::get('{provider}/redirect', [AuthController::class, 'redirectToProvider'])
        ->where('provider', 'google|facebook|github');
    Route::get('{provider}/callback', [AuthController::class, 'handleProviderCallback'])
        ->where('provider', 'google|facebook|github');
});

// =============================================
// PROTECTED ROUTES (نیاز به تأیید ایمیل/تلفن)
// =============================================
Route::middleware(['auth:sanctum', 'verified.email', 'track.online'])->group(function () {

    // ====================
    // AUTH MANAGEMENT
    // ====================
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('two-factor/enable', [AuthController::class, 'enableTwoFactor']);
        Route::post('two-factor/disable', [AuthController::class, 'disableTwoFactor']);
        Route::post('two-factor/verify', [AuthController::class, 'verifyTwoFactor']);

        // SESSION MANAGEMENT
        Route::get('sessions', [AuthController::class, 'activeSessions']);
        Route::delete('sessions/{tokenId}', [AuthController::class, 'revokeSession'])->where('tokenId', '[0-9]+');
        Route::delete('sessions/others', [AuthController::class, 'revokeOtherSessions']);
        Route::post('logout-all', [AuthController::class, 'logoutFromAllDevices']);
        Route::post('logout/{tokenId}', [AuthController::class, 'logoutFromSpecificDevice'])->where('tokenId', '[0-9]+');
    });

    // ====================
    // USER MANAGEMENT
    // ====================
    Route::prefix('users')->group(function () {
        // Current User
        Route::get('me', [UserController::class, 'showCurrent']);
        Route::put('me', [UserController::class, 'updateCurrent']);

        // Update other user (requires permission)
        Route::put('{user}', [UserController::class, 'update'])->where('user', '[0-9]+')->middleware('can:update,user');

        // User Actions
        Route::post('{user}/follow', [UserController::class, 'follow'])->where('user', '[0-9]+');
        Route::post('{user}/unfollow', [UserController::class, 'unfollow'])->where('user', '[0-9]+');

        // User Relationships
        Route::get('{user}/followers', [UserController::class, 'followers'])->where('user', '[0-9]+');
        Route::get('{user}/following', [UserController::class, 'following'])->where('user', '[0-9]+');

        // Follow Requests (for private accounts)
        Route::get('me/follow-requests', [UserController::class, 'followRequests']);

        Route::post('{follower}/accept-follow-request', [UserController::class, 'acceptFollowRequest'])->where('follower', '[0-9]+');
        Route::post('{follower}/reject-follow-request', [UserController::class, 'rejectFollowRequest'])->where('follower', '[0-9]+');

        // Search (now requires authentication)
        Route::get('search', [UserController::class, 'search']);
    });

    // ====================
    // POST MANAGEMENT
    // ====================
    Route::prefix('posts')->group(function () {
        // مشاهده پست‌ها (اکنون نیاز به احراز هویت دارد)
        Route::get('/', [PostController::class, 'index']);
        Route::get('{post}', [PostController::class, 'show']);

        Route::post('/', [PostController::class, 'store']);
        Route::put('{post}', [PostController::class, 'update'])->middleware('can:update,post');
        Route::delete('{post}', [PostController::class, 'destroy'])->middleware('can:delete,post');

        // Post Interactions
        Route::post('{post}/like', [PostController::class, 'like']);
        Route::post('{post}/repost', [PostController::class, 'repost']);
        Route::post('{post}/bookmark', [PostController::class, 'bookmark']);

        // User-specific posts
        Route::get('user/{userId}', [PostController::class, 'userPosts'])->where('userId', '[0-9]+');

        // Feed
        Route::get('feed/personal', [PostController::class, 'feed']);
    });

    // ====================
    // COMMENT MANAGEMENT
    // ====================
    Route::prefix('comments')->group(function () {
        Route::post('/', [CommentController::class, 'store']);

        // Comment Interactions
        Route::post('{comment}/like', [CommentController::class, 'like']);
        Route::post('{comment}/reply', [CommentController::class, 'reply']);

        Route::put('{comment}', [CommentController::class, 'update'])->middleware('can:update,comment');
        Route::delete('{comment}', [CommentController::class, 'destroy'])->middleware('can:delete,comment');

        // Get comments for content
        Route::get('/', [CommentController::class, 'index']);
    });

    // ====================
    // PRIVATE MESSAGING
    // ====================
    Route::middleware(['underage.access:private_messaging'])->prefix('messages')->group(function () {
        Route::get('conversations', [PrivateMessageController::class, 'conversations']);
        Route::post('conversations', [PrivateMessageController::class, 'createConversation']);
        Route::get('conversations/{conversation}', [PrivateMessageController::class, 'show']);
        Route::post('conversations/{conversation}/messages', [PrivateMessageController::class, 'sendMessage']);
        Route::get('conversations/{conversation}/messages', [PrivateMessageController::class, 'messages']);
        Route::post('conversations/{conversation}/mark-seen', [PrivateMessageController::class, 'markAsSeen']);
        Route::delete('messages/{message}', [PrivateMessageController::class, 'deleteMessage']);
        Route::post('conversations/{conversation}/add-participant', [PrivateMessageController::class, 'addParticipant']);
        Route::post('conversations/{conversation}/leave', [PrivateMessageController::class, 'leaveConversation']);
    });

    // ====================
    // BOOKMARK MANAGEMENT
    // ====================
    Route::prefix('bookmarks')->group(function () {
        Route::get('/', [BookmarkController::class, 'index']);
        Route::delete('{bookmarkableType}/{bookmarkableId}', [BookmarkController::class, 'destroy']);
    });

    // ====================
    // PARENTAL CONTROLS
    // ====================
    Route::prefix('parental-controls')->group(function () {
        Route::get('/', [ParentalControlController::class, 'index']);
        Route::post('/', [ParentalControlController::class, 'store']);
        Route::put('{childId}', [ParentalControlController::class, 'update'])->where('childId', '[0-9]+');
        Route::delete('{childId}', [ParentalControlController::class, 'destroy'])->where('childId', '[0-9]+');
        Route::get('{childId}/usage-report', [ParentalControlController::class, 'usageReport'])->where('childId', '[0-9]+');
    });

    // ====================
    // ADMIN MANAGEMENT
    // ====================
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // Stats & Analytics
        Route::get('stats', [AdminController::class, 'stats']);
        Route::get('redis-stats', [AdminController::class, 'redisStats']);

        // Settings Management
        Route::get('settings', [AdminController::class, 'getSettings']);
        Route::put('settings', [AdminController::class, 'updateSettings']);
        Route::get('upload-limits', [AdminController::class, 'getUploadLimits']);
        Route::put('upload-limits/{type}', [AdminController::class, 'updateUploadLimits']);

        // Feature Toggles
        Route::post('phone-auth/toggle', [AdminController::class, 'togglePhoneAuth']);

        // User Management
        Route::get('underage-users', [AdminController::class, 'underageUsers']);
        Route::get('security-reports', [AdminController::class, 'securityReports']);

        // Content Moderation
        Route::post('users/{user}/ban', [AdminController::class, 'banUser'])->where('user', '[0-9]+');
        Route::post('users/{user}/unban', [AdminController::class, 'unbanUser'])->where('user', '[0-9]+');
        Route::post('posts/{post}/feature', [AdminController::class, 'featurePost']);
    });

    // ====================
    // UTILITY ENDPOINTS
    // ====================
    Route::get('online-users', function (Request $request) {
        $redisService = app(\App\Services\RedisService::class);
        return response()->json([
            'online_users' => $redisService->getOnlineUsersCount(),
            'timestamp' => now()->toISOString()
        ]);
    });

    Route::get('notifications', function (Request $request) {
        $notificationService = app(\App\Services\NotificationService::class);
        $filters = $request->all();

        $notifications = $notificationService->getUserNotifications($request->user(), $filters);

        return response()->json([
            'data' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'total_count' => $request->user()->notifications()->count()
        ]);
    });

    Route::post('notifications/mark-all-read', function (Request $request) {
        $notificationService = app(\App\Services\NotificationService::class);
        $notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    });

    // Global Search (now requires authentication)
    Route::get('search', function (Request $request) {
        $searchService = app(\App\Services\SearchService::class);
        $query = $request->get('q', '');
        $filters = $request->all();

        if (empty($query)) {
            return response()->json([
                'message' => 'Search query is required',
                'error' => 'validation_error'
            ], 400);
        }

        try {
            $user = $request->user();
            $results = $searchService->globalSearch($query, $user, $filters);

            return response()->json([
                'data' => $results,
                'message' => 'Search completed successfully',
                'query' => $query,
                'results_count' => array_sum(array_map('count', $results))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'search_error'
            ], 500);
        }
    });
});

// =============================================
// FALLBACK ROUTE
// =============================================
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint not found. Please check the API documentation.',
        'error' => 'not_found',
        'available_endpoints' => [
            'GET /health',
            'POST /auth/register',
            'POST /auth/verify-and-login',
            'POST /auth/login'
        ]
    ], 404);
});
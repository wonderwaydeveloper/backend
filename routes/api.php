<?php

use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\UnifiedAuthController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\FollowController;
use App\Http\Controllers\Api\FollowRequestController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\GifController;
use App\Http\Controllers\Api\RepostController;
use App\Http\Controllers\Api\ThreadController;
use App\Http\Controllers\Api\ScheduledPostController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CommunityNoteController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\HashtagController;
use App\Http\Controllers\Api\ListController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MentionController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\MomentController;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\OnlineStatusController;

use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\FinalPerformanceController;
use App\Http\Controllers\Api\PerformanceDashboardController;
use App\Http\Controllers\Api\PollController;
use App\Http\Controllers\Api\ConversionController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TrendingController;
use App\Http\Controllers\Api\SpaceController;
use App\Http\Controllers\Api\PushNotificationController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\ABTestController;
use App\Http\Controllers\Api\AutoScalingController;
use App\Http\Controllers\Api\GraphQLController;
use App\Monetization\Controllers\AdvertisementController;
use App\Monetization\Controllers\CreatorFundController;
use App\Monetization\Controllers\PremiumController;

use Illuminate\Support\Facades\Route;

// Health Check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '3.0.0',
        'environment' => app()->environment(),
    ]);
});

// Test route for security testing
Route::post('/test', function () {
    return response()->json(['message' => 'Test endpoint']);
});

// Upload route for testing
Route::post('/upload', function () {
    return response()->json(['message' => 'Upload endpoint']);
});

// GraphQL endpoint
Route::post('/graphql', [GraphQLController::class, 'handle'])->middleware('auth:sanctum');

// === Authentication Routes ===
Route::prefix('auth')->group(function () {
    // Login & Basic Auth
    Route::post('/login', [UnifiedAuthController::class, 'login'])->middleware(['captcha', 'security:auth.login']);
    Route::post('/logout', [UnifiedAuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/logout-all', [UnifiedAuthController::class, 'logoutAll'])->middleware('auth:sanctum');
    Route::get('/me', [UnifiedAuthController::class, 'me'])->middleware(['auth:sanctum', 'security:auth.me']);
    
    // Session Management
    Route::prefix('sessions')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [UnifiedAuthController::class, 'getSessions']);
        Route::delete('/{token_id}', [UnifiedAuthController::class, 'revokeSession']);
    });

    // Multi-step Registration
    Route::prefix('register')->group(function () {
        Route::post('/step1', [UnifiedAuthController::class, 'multiStepStep1'])->middleware('security:auth.register');
        Route::post('/step2', [UnifiedAuthController::class, 'multiStepStep2'])->middleware('security:auth.register');
        Route::post('/step3', [UnifiedAuthController::class, 'multiStepStep3'])->middleware('security:auth.register');
        Route::post('/resend-code', [UnifiedAuthController::class, 'multiStepResendCode'])->middleware('security:auth.resend');
        Route::post('/check-username', [UnifiedAuthController::class, 'checkUsernameAvailability']);
    });

    // Email Verification
    Route::prefix('email')->group(function () {
        Route::post('/verify', [UnifiedAuthController::class, 'verifyEmail'])->middleware('security:email.verification');
        Route::post('/resend', [UnifiedAuthController::class, 'resendEmailVerification'])->middleware('security:email.resend');
        Route::get('/status', [UnifiedAuthController::class, 'emailVerificationStatus'])->middleware('auth:sanctum');
    });

    // Phone Authentication
    Route::prefix('phone')->group(function () {
        Route::post('/login/send-code', [UnifiedAuthController::class, 'phoneLoginSendCode'])->middleware('security:auth.phone_login');
        Route::post('/login/verify-code', [UnifiedAuthController::class, 'phoneLoginVerifyCode'])->middleware('security:auth.phone_login');
        Route::post('/login/resend-code', [UnifiedAuthController::class, 'phoneLoginResendCode'])->middleware('security:auth.phone_resend');
    });

    // Password Management
    Route::prefix('password')->group(function () {
        Route::post('/forgot', [PasswordResetController::class, 'forgotPassword'])->middleware('security:auth.password_reset');
        Route::post('/verify-code', [PasswordResetController::class, 'verifyCode'])->middleware('security:auth.reset_verify');
        Route::post('/resend', [PasswordResetController::class, 'resendCode'])->middleware('security:auth.reset_resend');
        Route::post('/reset', [PasswordResetController::class, 'resetPassword'])->middleware('security:auth.reset_verify');
        Route::post('/change', [UnifiedAuthController::class, 'changePassword'])->middleware('auth:sanctum');
    });

    // Two Factor Authentication
    Route::prefix('2fa')->middleware('auth:sanctum')->group(function () {
        Route::post('/enable', [UnifiedAuthController::class, 'enable2FA']);
        Route::post('/verify', [UnifiedAuthController::class, 'verify2FA']);
        Route::post('/disable', [UnifiedAuthController::class, 'disable2FA']);
    });
    
    // Device Verification (without auth middleware since user is logging in)
    Route::post('/verify-device', [DeviceController::class, 'verifyDevice'])
        ->middleware('throttle:5,1'); // 5 attempts per minute
    Route::post('/resend-device-code', [DeviceController::class, 'resendDeviceCode'])
        ->middleware('throttle:3,1'); // 3 attempts per minute
    
    // Age Verification
    Route::post('/complete-age-verification', [UnifiedAuthController::class, 'completeAgeVerification'])->middleware('auth:sanctum');
    
    // Security Events
    Route::get('/security/events', [UnifiedAuthController::class, 'getSecurityEvents'])->middleware('auth:sanctum');
    
    // Audit Logs
    Route::prefix('audit')->middleware('auth:sanctum')->group(function () {
        Route::get('/my-activity', [\App\Http\Controllers\Api\AuditController::class, 'getUserAuditTrail']);
        Route::get('/anomalies', [\App\Http\Controllers\Api\AuditController::class, 'detectAnomalies']);
        Route::get('/security-events', [\App\Http\Controllers\Api\AuditController::class, 'getSecurityEvents']);
        Route::get('/high-risk', [\App\Http\Controllers\Api\AuditController::class, 'getHighRiskActivities']);
        Route::get('/statistics', [\App\Http\Controllers\Api\AuditController::class, 'getAuditStatistics']);
    });
});

// Social Authentication
Route::prefix('auth/social')->group(function () {
    Route::get('/{provider}', [SocialAuthController::class, 'redirect'])
        ->where('provider', 'google')
        ->middleware('throttle:10,1'); // 10 attempts per minute
    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->where('provider', 'google'); // No rate limit - it's a redirect from Google
});

Route::middleware(['auth:sanctum', 'security:api'])->group(function () {

    // Posts Routes
    Route::post('posts', [PostController::class, 'store'])->middleware('permission:post.create');
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::put('posts/{post}', [PostController::class, 'update'])->middleware('permission:post.edit.own');
    Route::delete('posts/{post}', [PostController::class, 'destroy'])->middleware('permission:post.delete.own');
    Route::get('/posts/{post}/edit-history', [PostController::class, 'editHistory']);
    Route::post('/posts/{post}/like', [PostController::class, 'like'])->middleware('permission:post.like');
    Route::delete('/posts/{post}/like', [PostController::class, 'unlike'])->middleware('permission:post.like');
    Route::get('/posts/{post}/likes', [PostController::class, 'likes']);
    Route::post('/posts/{post}/quote', [PostController::class, 'quote'])->middleware('permission:post.create');
    Route::get('/posts/{post}/quotes', [PostController::class, 'quotes']);
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->middleware('permission:post.edit.own');
    Route::get('/timeline', [PostController::class, 'timeline'])->name('main.timeline');
    Route::get('/drafts', [PostController::class, 'drafts']);

    // Video routes
    Route::get('/videos/{video}/status', [VideoController::class, 'status']);

    // Community Notes routes
    Route::prefix('posts/{post}/community-notes')->group(function () {
        Route::get('/', [CommunityNoteController::class, 'index']);
        Route::post('/', [CommunityNoteController::class, 'store']);
    });
    Route::post('/community-notes/{note}/vote', [CommunityNoteController::class, 'vote']);
    Route::get('/community-notes/pending', [CommunityNoteController::class, 'pending']);

    // Analytics routes (User-level only)
    Route::prefix('analytics')->group(function () {
        Route::get('/user', [AnalyticsController::class, 'userAnalytics']);
        Route::get('/posts/{post}', [AnalyticsController::class, 'postAnalytics']);
    });
    
    // Public analytics tracking (no auth required)
    Route::post('/analytics/track', [AnalyticsController::class, 'trackEvent'])->withoutMiddleware(['auth:sanctum']);

    Route::post('/threads', [ThreadController::class, 'create'])->middleware('permission:post.create');
    Route::get('/threads/{post}', [ThreadController::class, 'show']);
    Route::post('/threads/{post}/add', [ThreadController::class, 'addToThread'])->middleware('permission:post.create');
    Route::get('/threads/{post}/stats', [ThreadController::class, 'stats']);

    Route::post('/scheduled-posts', [ScheduledPostController::class, 'store'])->middleware('permission:post.schedule');
    Route::get('/scheduled-posts', [ScheduledPostController::class, 'index']);
    Route::delete('/scheduled-posts/{scheduledPost}', [ScheduledPostController::class, 'destroy']);

    Route::get('/gifs/search', [GifController::class, 'search']);
    Route::get('/gifs/trending', [GifController::class, 'trending']);

    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/posts/{post}/bookmark', [BookmarkController::class, 'toggle'])->middleware('permission:post.bookmark');

    Route::post('/posts/{post}/repost', [RepostController::class, 'repost'])->middleware('permission:post.repost');
    Route::delete('/posts/{post}/repost', [RepostController::class, 'unrepost'])->middleware('permission:post.repost');
    Route::get('/posts/{post}/reposts', [RepostController::class, 'reposts']);
    Route::get('/my-reposts', [RepostController::class, 'myReposts']);


    // Comments Routes
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware(['permission:comment.create', 'check.reply.permission']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::post('/comments/{comment}/like', [CommentController::class, 'like'])->middleware('permission:post.like');

    // Follow/Unfollow Actions (ProfileController)
    Route::post('/users/{user}/follow', [ProfileController::class, 'follow'])->middleware(['throttle:30,1', 'can:follow,user']);
    Route::post('/users/{user}/unfollow', [ProfileController::class, 'unfollow'])->middleware(['throttle:30,1', 'can:follow,user']);
    
    // Follow Requests
    Route::post('/users/{user}/follow-request', [FollowRequestController::class, 'send']);
    Route::get('/follow-requests', [FollowRequestController::class, 'index']);
    Route::post('/follow-requests/{followRequest}/accept', [FollowRequestController::class, 'accept']);
    Route::post('/follow-requests/{followRequest}/reject', [FollowRequestController::class, 'reject']);
    
    // Followers/Following Lists (FollowController)
    Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowController::class, 'following']);

    Route::get('/users/{user}', [ProfileController::class, 'show'])->middleware('can:view,user');
    Route::get('/users/{user}/posts', [ProfileController::class, 'posts'])->middleware('can:view,user');
    Route::get('/users/{user}/media', [ProfileController::class, 'media'])->middleware('can:view,user');
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/privacy', [ProfileController::class, 'updatePrivacy']);
    
    // Settings Routes
    Route::prefix('settings')->group(function () {
        Route::get('/privacy', [ProfileController::class, 'getPrivacySettings']);
        Route::put('/privacy', [ProfileController::class, 'updatePrivacySettings']);
    });
    
    // Account Management Routes
    Route::prefix('account')->group(function () {
        Route::get('/export-data', [ProfileController::class, 'exportData']);
        Route::post('/delete-account', [ProfileController::class, 'deleteAccount']);
    });
    
    Route::get('/search/users', [SearchController::class, 'users']);
    Route::get('/search/posts', [SearchController::class, 'posts']);
    Route::get('/search/hashtags', [SearchController::class, 'hashtags']);
    Route::get('/search/all', [SearchController::class, 'all']);
    Route::get('/search/advanced', [SearchController::class, 'advanced']);
    Route::get('/search/suggestions', [SearchController::class, 'suggestions']);
    Route::get('/suggestions/users', [SuggestionController::class, 'users']);

    Route::post('/devices/register', [DeviceController::class, 'register']);
    Route::delete('/devices/{token}', [DeviceController::class, 'unregister']);
    
    // Device management
    Route::prefix('devices')->group(function () {
        Route::post('/advanced/register', [DeviceController::class, 'registerAdvanced']);
        Route::get('/list', [DeviceController::class, 'list']);
        Route::get('/{device}/activity', [DeviceController::class, 'getActivity']);
        Route::post('/{device}/trust', [DeviceController::class, 'trust']);
        Route::delete('/{device}/revoke', [DeviceController::class, 'revoke']);
        Route::post('/revoke-all', [DeviceController::class, 'revokeAll']);
        Route::get('/security-check', [DeviceController::class, 'checkSuspiciousActivity']);
    });


    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [MessageController::class, 'conversations']);
        Route::get('/users/{user}', [MessageController::class, 'messages']);
        Route::post('/users/{user}', [MessageController::class, 'send'])->middleware('throttle:60,1');
        Route::post('/users/{user}/typing', [MessageController::class, 'typing']);
        Route::post('/{message}/read', [MessageController::class, 'markAsRead']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
    });

    Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
    Route::get('/subscription/current', [SubscriptionController::class, 'current']);
    Route::post('/subscription/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
    Route::get('/subscription/history', [SubscriptionController::class, 'history']);

    Route::get('/hashtags/trending', [HashtagController::class, 'trending']);
    Route::get('/hashtags/search', [HashtagController::class, 'search']);
    Route::get('/hashtags/suggestions', [HashtagController::class, 'suggestions']);
    Route::get('/hashtags/{hashtag:slug}', [HashtagController::class, 'show']);

    // Advanced Trending Routes
    Route::prefix('trending')->group(function () {
        Route::get('/hashtags', [TrendingController::class, 'hashtags']);
        Route::get('/posts', [TrendingController::class, 'posts']);
        Route::get('/users', [TrendingController::class, 'users']);
        Route::get('/personalized', [TrendingController::class, 'personalized']);
        Route::get('/velocity/{type}/{id}', [TrendingController::class, 'velocity']);
        Route::get('/all', [TrendingController::class, 'all']);
        Route::get('/stats', [TrendingController::class, 'stats']);
        Route::post('/refresh', [TrendingController::class, 'refresh']);
    });

    // Spaces (Audio Rooms) Routes
    Route::prefix('spaces')->group(function () {
        Route::get('/', [SpaceController::class, 'index']);
        Route::post('/', [SpaceController::class, 'store']);
        Route::get('/{space}', [SpaceController::class, 'show']);
        Route::post('/{space}/join', [SpaceController::class, 'join']);
        Route::post('/{space}/leave', [SpaceController::class, 'leave']);
        Route::put('/{space}/participants/{participant}/role', [SpaceController::class, 'updateRole']);
        Route::post('/{space}/end', [SpaceController::class, 'end']);
    });

    // Lists Routes
    Route::prefix('lists')->group(function () {
        Route::get('/', [ListController::class, 'index']);
        Route::post('/', [ListController::class, 'store']);
        Route::get('/discover', [ListController::class, 'discover']);
        Route::get('/{list}', [ListController::class, 'show']);
        Route::put('/{list}', [ListController::class, 'update']);
        Route::delete('/{list}', [ListController::class, 'destroy']);
        Route::post('/{list}/members', [ListController::class, 'addMember']);
        Route::delete('/{list}/members/{user}', [ListController::class, 'removeMember']);
        Route::post('/{list}/subscribe', [ListController::class, 'subscribe']);
        Route::post('/{list}/unsubscribe', [ListController::class, 'unsubscribe']);
        Route::get('/{list}/posts', [ListController::class, 'posts']);
    });

    // Poll routes
    Route::post('/polls', [PollController::class, 'store']);
    Route::post('/polls/{poll}/vote/{option}', [PollController::class, 'vote']);
    Route::get('/polls/{poll}/results', [PollController::class, 'results']);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });



    // Performance & Monitoring (User-level only)
    Route::prefix('performance')->group(function () {
        Route::get('/dashboard', [PerformanceDashboardController::class, 'dashboard']);
        Route::get('/timeline/optimized', [PerformanceController::class, 'optimizeTimeline']);
        Route::post('/cache/warmup', [PerformanceController::class, 'warmupCache']);
        Route::delete('/cache/clear', [PerformanceController::class, 'clearCache']);
    });

    // Optimized routes
    Route::prefix('optimized')->group(function () {
        Route::get('/timeline', [TimelineController::class, 'index']);
    });

    // Final Performance routes
    Route::prefix('final-performance')->group(function () {
        Route::get('/system-status', [FinalPerformanceController::class, 'systemStatus']);
    });

    // Monitoring routes
    Route::prefix('monitoring')->group(function () {
        Route::get('/dashboard', [MonitoringController::class, 'dashboard']);
        Route::get('/cache', [MonitoringController::class, 'cache']);
        Route::get('/queue', [MonitoringController::class, 'queue']);
    });

    Route::post('/users/{user}/block', [ProfileController::class, 'block'])->middleware(['throttle:10,1', 'can:block,user']);
    Route::post('/users/{user}/unblock', [ProfileController::class, 'unblock'])->middleware(['throttle:10,1', 'can:block,user']);
    Route::post('/users/{user}/mute', [ProfileController::class, 'mute'])->middleware(['throttle:20,1', 'can:mute,user']);
    Route::post('/users/{user}/unmute', [ProfileController::class, 'unmute'])->middleware(['throttle:20,1', 'can:mute,user']);

    // Block/Mute Management
    Route::prefix('blocked')->group(function () {
        Route::get('/', [ProfileController::class, 'getBlockedUsers']);
    });
    Route::prefix('muted')->group(function () {
        Route::get('/', [ProfileController::class, 'getMutedUsers']);
    });

    // Phase 3: Notification Preferences
    Route::prefix('notifications/preferences')->group(function () {
        Route::get('/', [NotificationPreferenceController::class, 'index']);
        Route::put('/', [NotificationPreferenceController::class, 'update']);
        Route::put('/{type}', [NotificationPreferenceController::class, 'updateType']);
        Route::put('/{type}/{category}', [NotificationPreferenceController::class, 'updateSpecific']);
    });

    // Phase 3: Media Upload
    Route::prefix('media')->group(function () {
        Route::post('/upload/image', [MediaController::class, 'uploadImage']);
        Route::post('/upload/video', [MediaController::class, 'uploadVideo']);
        Route::post('/upload/document', [MediaController::class, 'uploadDocument']);
        Route::delete('/delete', [MediaController::class, 'deleteMedia']);
    });

    // Content Moderation & Reporting
    Route::prefix('reports')->group(function () {
        // User reporting
        Route::post('/post/{post}', [ModerationController::class, 'reportPost'])->middleware('throttle:5,1');
        Route::post('/user/{user}', [ModerationController::class, 'reportUser'])->middleware('throttle:5,1');
        Route::post('/comment/{comment}', [ModerationController::class, 'reportComment'])->middleware('throttle:5,1');
        Route::get('/my-reports', [ModerationController::class, 'myReports']);
        
        // Admin only
        Route::middleware('role:admin')->group(function () {
            Route::get('/', [ModerationController::class, 'getReports']);
            Route::get('/{report}', [ModerationController::class, 'showReport']);
            Route::patch('/{report}/status', [ModerationController::class, 'updateReportStatus']);
            Route::post('/{report}/action', [ModerationController::class, 'takeAction']);
            Route::get('/stats/overview', [ModerationController::class, 'getContentStats']);
        });
    });

    // Phase 3: Push Notifications
    Route::prefix('push')->group(function () {
        Route::post('/register', [PushNotificationController::class, 'registerDevice']);
        Route::delete('/unregister/{token}', [PushNotificationController::class, 'unregisterDevice']);
        Route::post('/test', [PushNotificationController::class, 'testNotification']);
        Route::get('/devices', [PushNotificationController::class, 'getDevices']);
    });

    // Mention System
    Route::prefix('mentions')->group(function () {
        Route::get('/search-users', [MentionController::class, 'searchUsers']);
        Route::get('/my-mentions', [MentionController::class, 'getUserMentions']);
        Route::get('/{type}/{id}', [MentionController::class, 'getMentions'])
            ->where('type', 'post|comment');
    });

    // Real-time Features
    Route::prefix('realtime')->group(function () {
        Route::post('/status', [OnlineStatusController::class, 'updateStatus']);
        Route::get('/online-users', [OnlineStatusController::class, 'getOnlineUsers']);
        Route::get('/timeline', [TimelineController::class, 'liveTimeline']);
        Route::get('/posts/{post}', [TimelineController::class, 'getPostUpdates']);
    });

    // Moments Routes
    Route::prefix('moments')->group(function () {
        Route::get('/', [MomentController::class, 'index']);
        Route::post('/', [MomentController::class, 'store']);
        Route::get('/featured', [MomentController::class, 'featured']);
        Route::get('/my-moments', [MomentController::class, 'myMoments']);
        Route::get('/{moment}', [MomentController::class, 'show']);
        Route::put('/{moment}', [MomentController::class, 'update']);
        Route::delete('/{moment}', [MomentController::class, 'destroy']);
        Route::post('/{moment}/posts', [MomentController::class, 'addPost']);
        Route::delete('/{moment}/posts/{post}', [MomentController::class, 'removePost']);
    });

    // A/B Testing
    Route::prefix('ab-tests')->group(function () {
        Route::get('/', [ABTestController::class, 'index']);
        Route::post('/', [ABTestController::class, 'store']);
        Route::get('/{id}', [ABTestController::class, 'show']);
        Route::post('/{id}/start', [ABTestController::class, 'start']);
        Route::post('/{id}/stop', [ABTestController::class, 'stop']);
        Route::post('/assign', [ABTestController::class, 'assign']);
        Route::post('/track', [ABTestController::class, 'track']);
    });


    // Conversion Tracking Routes
    Route::prefix('conversions')->group(function () {
        Route::post('/track', [ConversionController::class, 'track']);
        Route::get('/funnel', [ConversionController::class, 'funnel']);
        Route::get('/by-source', [ConversionController::class, 'bySource']);
        Route::get('/user-journey', [ConversionController::class, 'userJourney']);
        Route::get('/cohort-analysis', [ConversionController::class, 'cohortAnalysis']);
    });

    // Auto-scaling
    Route::prefix('auto-scaling')->group(function () {
        Route::get('/status', [AutoScalingController::class, 'status']);
        Route::get('/metrics', [AutoScalingController::class, 'metrics']);
        Route::post('/force-scale', [AutoScalingController::class, 'forceScale']);
        Route::get('/predict', [AutoScalingController::class, 'predict']);
    });
    // Monetization Routes
    Route::prefix('monetization')->group(function () {
        // Advertisement Routes
        Route::prefix('ads')->group(function () {
            Route::post('/', [AdvertisementController::class, 'create']);
            Route::get('/targeted', [AdvertisementController::class, 'getTargetedAds']);
            Route::post('/{adId}/click', [AdvertisementController::class, 'recordClick']);
            Route::get('/analytics', [AdvertisementController::class, 'getAnalytics']);
            Route::post('/{adId}/pause', [AdvertisementController::class, 'pause']);
            Route::post('/{adId}/resume', [AdvertisementController::class, 'resume']);
        });

        // Creator Fund Routes
        Route::prefix('creator-fund')->group(function () {
            Route::get('/analytics', [CreatorFundController::class, 'getAnalytics']);
            Route::post('/calculate-earnings', [CreatorFundController::class, 'calculateEarnings']);
            Route::get('/earnings-history', [CreatorFundController::class, 'getEarningsHistory']);
            Route::post('/request-payout', [CreatorFundController::class, 'requestPayout']);
        });

        // Premium Subscription Routes
        Route::prefix('premium')->group(function () {
            Route::get('/plans', [PremiumController::class, 'getPlans']);
            Route::post('/subscribe', [PremiumController::class, 'subscribe']);
            Route::post('/cancel', [PremiumController::class, 'cancel']);
            Route::get('/status', [PremiumController::class, 'getStatus']);
        });
    });

    // Communities Routes
    Route::prefix('communities')->group(function () {
        Route::get('/', [CommunityController::class, 'index']);
        Route::post('/', [CommunityController::class, 'store']);
        Route::get('/{community}', [CommunityController::class, 'show']);
        Route::put('/{community}', [CommunityController::class, 'update']);
        Route::delete('/{community}', [CommunityController::class, 'destroy']);
        Route::post('/{community}/join', [CommunityController::class, 'join']);
        Route::post('/{community}/leave', [CommunityController::class, 'leave']);
        Route::get('/{community}/posts', [CommunityController::class, 'posts']);
        Route::get('/{community}/members', [CommunityController::class, 'members']);
        Route::get('/{community}/join-requests', [CommunityController::class, 'joinRequests']);
        Route::post('/{community}/join-requests/{request}/approve', [CommunityController::class, 'approveJoinRequest']);
        Route::post('/{community}/join-requests/{request}/reject', [CommunityController::class, 'rejectJoinRequest']);
    });
});


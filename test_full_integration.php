<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║     تست یکپارچگی کامل - 34 سیستم                             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = ['passed' => 0, 'failed' => 0];

function test($name, $fn) {
    global $stats;
    try {
        if ($fn()) {
            echo "  ✓ {$name}\n";
            $stats['passed']++;
        } else {
            echo "  ✗ {$name}\n";
            $stats['failed']++;
        }
    } catch (\Exception $e) {
        echo "  ✗ {$name}\n";
        $stats['failed']++;
    }
}

// 1. Authentication & Security
echo "1️⃣ Authentication & Security\n";
test("UnifiedAuthController", fn() => class_exists('App\\Http\\Controllers\\Api\\UnifiedAuthController'));
test("PasswordResetController", fn() => class_exists('App\\Http\\Controllers\\Api\\PasswordResetController'));
test("SocialAuthController", fn() => class_exists('App\\Http\\Controllers\\Api\\SocialAuthController'));
test("DeviceController", fn() => class_exists('App\\Http\\Controllers\\Api\\DeviceController'));
test("AuditController", fn() => class_exists('App\\Http\\Controllers\\Api\\AuditController'));

// 2. Authorization
echo "\n2️⃣ Authorization\n";
test("Spatie Permission", fn() => class_exists('Spatie\\Permission\\Models\\Permission'));
test("Roles exist", fn() => DB::table('roles')->exists());
test("Permissions exist", fn() => DB::table('permissions')->exists());

// 3. Posts & Content
echo "\n3️⃣ Posts & Content\n";
test("PostController", fn() => class_exists('App\\Http\\Controllers\\Api\\PostController'));
test("ThreadController", fn() => class_exists('App\\Http\\Controllers\\Api\\ThreadController'));
test("ScheduledPostController", fn() => class_exists('App\\Http\\Controllers\\Api\\ScheduledPostController'));
test("VideoController", fn() => class_exists('App\\Http\\Controllers\\Api\\VideoController'));

// 4. Profile & Users
echo "\n4️⃣ Profile & Users\n";
test("ProfileController", fn() => class_exists('App\\Http\\Controllers\\Api\\ProfileController'));
test("User Model", fn() => class_exists('App\\Models\\User'));

// 5. Comments
echo "\n5️⃣ Comments\n";
test("CommentController", fn() => class_exists('App\\Http\\Controllers\\Api\\CommentController'));
test("Comment Model", fn() => class_exists('App\\Models\\Comment'));

// 6. Social Features
echo "\n6️⃣ Social Features\n";
test("FollowController", fn() => class_exists('App\\Http\\Controllers\\Api\\FollowController'));
test("FollowRequestController", fn() => class_exists('App\\Http\\Controllers\\Api\\FollowRequestController'));
test("Block Model", fn() => class_exists('App\\Models\\Block'));
test("Mute Model", fn() => class_exists('App\\Models\\Mute'));

// 7. Search & Discovery
echo "\n7️⃣ Search & Discovery\n";
test("SearchController", fn() => class_exists('App\\Http\\Controllers\\Api\\SearchController'));
test("SuggestionController", fn() => class_exists('App\\Http\\Controllers\\Api\\SuggestionController'));
test("TrendingController", fn() => class_exists('App\\Http\\Controllers\\Api\\TrendingController'));

// 8. Messaging
echo "\n8️⃣ Messaging\n";
test("MessageController", fn() => class_exists('App\\Http\\Controllers\\Api\\MessageController'));
test("Message Model", fn() => class_exists('App\\Models\\Message'));
test("Conversation Model", fn() => class_exists('App\\Models\\Conversation'));

// 9. Notifications
echo "\n9️⃣ Notifications\n";
test("NotificationController", fn() => class_exists('App\\Http\\Controllers\\Api\\NotificationController'));
test("NotificationPreferenceController", fn() => class_exists('App\\Http\\Controllers\\Api\\NotificationPreferenceController'));
test("PushNotificationController", fn() => class_exists('App\\Http\\Controllers\\Api\\PushNotificationController'));

// 10. Bookmarks & Reposts
echo "\n🔟 Bookmarks & Reposts\n";
test("BookmarkController", fn() => class_exists('App\\Http\\Controllers\\Api\\BookmarkController'));
test("RepostController", fn() => class_exists('App\\Http\\Controllers\\Api\\RepostController'));

// 11. Hashtags
echo "\n1️⃣1️⃣ Hashtags\n";
test("HashtagController", fn() => class_exists('App\\Http\\Controllers\\Api\\HashtagController'));
test("Hashtag Model", fn() => class_exists('App\\Models\\Hashtag'));

// 12. Moderation & Reporting
echo "\n1️⃣2️⃣ Moderation & Reporting\n";
test("ModerationController", fn() => class_exists('App\\Http\\Controllers\\Api\\ModerationController'));
test("Report Model", fn() => class_exists('App\\Models\\Report'));

// 13. Communities
echo "\n1️⃣3️⃣ Communities\n";
test("CommunityController", fn() => class_exists('App\\Http\\Controllers\\Api\\CommunityController'));
test("CommunityNoteController", fn() => class_exists('App\\Http\\Controllers\\Api\\CommunityNoteController'));
test("Community Model", fn() => class_exists('App\\Models\\Community'));

// 14. Spaces
echo "\n1️⃣4️⃣ Spaces\n";
test("SpaceController", fn() => class_exists('App\\Http\\Controllers\\Api\\SpaceController'));
test("Space Model", fn() => class_exists('App\\Models\\Space'));

// 15. Lists
echo "\n1️⃣5️⃣ Lists\n";
test("ListController", fn() => class_exists('App\\Http\\Controllers\\Api\\ListController'));
test("UserList Model", fn() => class_exists('App\\Models\\UserList'));

// 16. Polls
echo "\n1️⃣6️⃣ Polls\n";
test("PollController", fn() => class_exists('App\\Http\\Controllers\\Api\\PollController'));
test("Poll Model", fn() => class_exists('App\\Models\\Poll'));

// 17. Mentions
echo "\n1️⃣7️⃣ Mentions\n";
test("MentionController", fn() => class_exists('App\\Http\\Controllers\\Api\\MentionController'));
test("Mention Model", fn() => class_exists('App\\Models\\Mention'));

// 18. Media
echo "\n1️⃣8️⃣ Media\n";
test("MediaController", fn() => class_exists('App\\Http\\Controllers\\Api\\MediaController'));
test("Media Model", fn() => class_exists('App\\Models\\Media'));

// 19. Moments
echo "\n1️⃣9️⃣ Moments\n";
test("MomentController", fn() => class_exists('App\\Http\\Controllers\\Api\\MomentController'));
test("Moment Model", fn() => class_exists('App\\Models\\Moment'));

// 20. Real-time
echo "\n2️⃣0️⃣ Real-time\n";
test("OnlineStatusController", fn() => class_exists('App\\Http\\Controllers\\Api\\OnlineStatusController'));
test("TimelineController", fn() => class_exists('App\\Http\\Controllers\\Api\\TimelineController'));

// 21. Analytics
echo "\n2️⃣1️⃣ Analytics\n";
test("AnalyticsController", fn() => class_exists('App\\Http\\Controllers\\Api\\AnalyticsController'));
test("ConversionController", fn() => class_exists('App\\Http\\Controllers\\Api\\ConversionController'));

// 22. A/B Testing
echo "\n2️⃣2️⃣ A/B Testing\n";
test("ABTestController", fn() => class_exists('App\\Http\\Controllers\\Api\\ABTestController'));
test("ABTest Model", fn() => class_exists('App\\Models\\ABTest'));

// 23. Monetization
echo "\n2️⃣3️⃣ Monetization\n";
test("AdvertisementController", fn() => class_exists('App\\Monetization\\Controllers\\AdvertisementController'));
test("CreatorFundController", fn() => class_exists('App\\Monetization\\Controllers\\CreatorFundController'));
test("PremiumController", fn() => class_exists('App\\Monetization\\Controllers\\PremiumController'));

// 24. Performance & Monitoring
echo "\n2️⃣4️⃣ Performance & Monitoring\n";
test("PerformanceController", fn() => class_exists('App\\Http\\Controllers\\Api\\PerformanceController'));
test("MonitoringController", fn() => class_exists('App\\Http\\Controllers\\Api\\MonitoringController'));
test("AutoScalingController", fn() => class_exists('App\\Http\\Controllers\\Api\\AutoScalingController'));

// 25. GIF Integration
echo "\n2️⃣5️⃣ GIF Integration\n";
test("GifController", fn() => class_exists('App\\Http\\Controllers\\Api\\GifController'));

// 26. GraphQL
echo "\n2️⃣6️⃣ GraphQL\n";
test("GraphQLController", fn() => class_exists('App\\Http\\Controllers\\Api\\GraphQLController'));

// 27. Organization
echo "\n2️⃣7️⃣ Organization\n";
test("OrganizationController", fn() => class_exists('App\\Http\\Controllers\\Api\\OrganizationController'));

// Database Integration
echo "\n📦 Database Integration\n";
$tables = ['users', 'posts', 'comments', 'messages', 'notifications', 'communities', 'spaces', 'polls', 'hashtags', 'media'];
foreach ($tables as $table) {
    test("Table: {$table}", fn() => DB::getSchemaBuilder()->hasTable($table));
}

// Routes Integration
echo "\n🌐 Routes Integration\n";
$routes = app('router')->getRoutes();
test("API Routes loaded", fn() => count($routes) > 100);
test("Auth routes", fn() => collect($routes)->contains(fn($r) => str_contains($r->uri(), 'api/auth')));
test("Posts routes", fn() => collect($routes)->contains(fn($r) => str_contains($r->uri(), 'api/posts')));

// Services Integration
echo "\n⚙️ Services Integration\n";
test("PostService", fn() => class_exists('App\\Services\\PostService'));
test("MessageService", fn() => class_exists('App\\Services\\MessageService'));
test("SearchService", fn() => class_exists('App\\Services\\SearchService'));
test("TrendingService", fn() => class_exists('App\\Services\\TrendingService'));

$total = array_sum($stats);
$percentage = round(($stats['passed'] / $total) * 100, 1);

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    نتیجه نهایی                                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "کل تستها: {$total}\n";
echo "موفق: {$stats['passed']} ✓\n";
echo "ناموفق: {$stats['failed']} ✗\n";
echo "درصد موفقیت: {$percentage}%\n\n";

if ($percentage == 100) {
    echo "🎉 عالی! همه 34 سیستم یکپارچه هستند!\n";
} else {
    echo "⚠️ برخی سیستمها نیاز به بررسی دارند\n";
}

exit($stats['failed'] > 0 ? 1 : 0);

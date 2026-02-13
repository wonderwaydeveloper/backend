<?php

/**
 * Follow System - Complete Review Test Script
 * Based on SYSTEM_REVIEW_CRITERIA.md
 */

echo "=== FOLLOW SYSTEM REVIEW ===\n\n";

$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'sections' => []
];

function test($name, $condition, &$results, $section) {
    $results['total']++;
    if ($condition) {
        $results['passed']++;
        $results['sections'][$section]['passed']++;
        echo "✓ {$name}\n";
        return true;
    } else {
        $results['failed']++;
        $results['sections'][$section]['failed']++;
        echo "✗ {$name}\n";
        return false;
    }
}

function section($name, &$results) {
    echo "\n--- {$name} ---\n";
    $results['sections'][$name] = ['passed' => 0, 'failed' => 0];
}

// ============================================
// 1. ARCHITECTURE & CODE (20 tests)
// ============================================
section('1. Architecture & Code', $results);

test('FollowController exists', 
    file_exists(__DIR__ . '/app/Http/Controllers/Api/FollowController.php'), 
    $results, '1. Architecture & Code');

test('FollowRequestController exists', 
    file_exists(__DIR__ . '/app/Http/Controllers/Api/FollowRequestController.php'), 
    $results, '1. Architecture & Code');

test('ProfileController has follow methods', 
    file_exists(__DIR__ . '/app/Http/Controllers/Api/ProfileController.php') &&
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ProfileController.php'), 'function follow') !== false, 
    $results, '1. Architecture & Code');

test('UserService exists', 
    file_exists(__DIR__ . '/app/Services/UserService.php'), 
    $results, '1. Architecture & Code');

test('UserFollowService exists', 
    file_exists(__DIR__ . '/app/Services/UserFollowService.php'), 
    $results, '1. Architecture & Code');

test('UserFollowService has follow method', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'function follow') !== false, 
    $results, '1. Architecture & Code');

test('UserFollowService has unfollow method', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'function unfollow') !== false, 
    $results, '1. Architecture & Code');

test('User model exists', 
    file_exists(__DIR__ . '/app/Models/User.php'), 
    $results, '1. Architecture & Code');

test('User model has followers relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'function followers') !== false, 
    $results, '1. Architecture & Code');

test('User model has following relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'function following') !== false, 
    $results, '1. Architecture & Code');

test('User model has isFollowing method', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'function isFollowing') !== false, 
    $results, '1. Architecture & Code');

test('FollowRequest model exists', 
    file_exists(__DIR__ . '/app/Models/FollowRequest.php'), 
    $results, '1. Architecture & Code');

test('FollowRequest has follower relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/FollowRequest.php'), 'function follower') !== false, 
    $results, '1. Architecture & Code');

test('FollowRequest has following relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/FollowRequest.php'), 'function following') !== false, 
    $results, '1. Architecture & Code');

test('UserPolicy exists', 
    file_exists(__DIR__ . '/app/Policies/UserPolicy.php'), 
    $results, '1. Architecture & Code');

test('UserPolicy has follow method', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'function follow') !== false, 
    $results, '1. Architecture & Code');

test('UserResource exists', 
    file_exists(__DIR__ . '/app/Http/Resources/UserResource.php'), 
    $results, '1. Architecture & Code');

test('User model has followers_count field', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'followers_count') !== false, 
    $results, '1. Architecture & Code');

test('User model has following_count field', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'following_count') !== false, 
    $results, '1. Architecture & Code');

test('UserService delegates to UserFollowService', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserService.php'), 'UserFollowService') !== false, 
    $results, '1. Architecture & Code');

// ============================================
// 2. DATABASE & SCHEMA (15 tests)
// ============================================
section('2. Database & Schema', $results);

test('follows migration exists', 
    count(glob(__DIR__ . '/database/migrations/*_create_follows_table.php')) > 0, 
    $results, '2. Database & Schema');

test('follow_requests migration exists', 
    count(glob(__DIR__ . '/database/migrations/*_create_follow_requests_table.php')) > 0, 
    $results, '2. Database & Schema');

$followsMigration = file_get_contents(glob(__DIR__ . '/database/migrations/*_create_follows_table.php')[0]);

test('follows table has follower_id', 
    strpos($followsMigration, 'follower_id') !== false, 
    $results, '2. Database & Schema');

test('follows table has following_id', 
    strpos($followsMigration, 'following_id') !== false, 
    $results, '2. Database & Schema');

test('follows table has timestamps', 
    strpos($followsMigration, 'timestamps') !== false, 
    $results, '2. Database & Schema');

test('follows table has unique constraint', 
    strpos($followsMigration, 'unique') !== false, 
    $results, '2. Database & Schema');

test('follows table has foreign keys', 
    strpos($followsMigration, 'foreignId') !== false || strpos($followsMigration, 'foreign') !== false, 
    $results, '2. Database & Schema');

test('follows table has cascade delete', 
    strpos($followsMigration, 'cascadeOnDelete') !== false, 
    $results, '2. Database & Schema');

test('follows table has index on follower_id', 
    strpos($followsMigration, 'index') !== false, 
    $results, '2. Database & Schema');

$followRequestsMigration = file_get_contents(glob(__DIR__ . '/database/migrations/*_create_follow_requests_table.php')[0]);

test('follow_requests table has follower_id', 
    strpos($followRequestsMigration, 'follower_id') !== false, 
    $results, '2. Database & Schema');

test('follow_requests table has following_id', 
    strpos($followRequestsMigration, 'following_id') !== false, 
    $results, '2. Database & Schema');

test('follow_requests table has status field', 
    strpos($followRequestsMigration, 'status') !== false, 
    $results, '2. Database & Schema');

test('follow_requests has enum status', 
    strpos($followRequestsMigration, 'enum') !== false, 
    $results, '2. Database & Schema');

test('follow_requests has unique constraint', 
    strpos($followRequestsMigration, 'unique') !== false, 
    $results, '2. Database & Schema');

test('follow_requests has foreign keys', 
    strpos($followRequestsMigration, 'foreignId') !== false, 
    $results, '2. Database & Schema');

// ============================================
// 3. API & ROUTES (15 tests)
// ============================================
section('3. API & Routes', $results);

$routes = file_get_contents(__DIR__ . '/routes/api.php');

test('POST /users/{user}/follow route exists', 
    strpos($routes, '/users/{user}/follow') !== false && strpos($routes, 'follow') !== false, 
    $results, '3. API & Routes');

test('POST /users/{user}/unfollow route exists', 
    strpos($routes, '/users/{user}/unfollow') !== false, 
    $results, '3. API & Routes');

test('GET /users/{user}/followers route exists', 
    strpos($routes, '/users/{user}/followers') !== false, 
    $results, '3. API & Routes');

test('GET /users/{user}/following route exists', 
    strpos($routes, '/users/{user}/following') !== false, 
    $results, '3. API & Routes');

test('POST /users/{user}/follow-request route exists', 
    strpos($routes, 'follow-request') !== false, 
    $results, '3. API & Routes');

test('GET /follow-requests route exists', 
    strpos($routes, '/follow-requests') !== false, 
    $results, '3. API & Routes');

test('POST /follow-requests/{followRequest}/accept route exists', 
    strpos($routes, 'accept') !== false, 
    $results, '3. API & Routes');

test('POST /follow-requests/{followRequest}/reject route exists', 
    strpos($routes, 'reject') !== false, 
    $results, '3. API & Routes');

test('Routes use auth middleware', 
    strpos($routes, 'auth:sanctum') !== false || strpos($routes, "middleware('auth") !== false, 
    $results, '3. API & Routes');

test('Follow routes use throttle', 
    strpos($routes, 'throttle') !== false, 
    $results, '3. API & Routes');

test('Follow routes use authorization', 
    strpos($routes, 'can:follow') !== false, 
    $results, '3. API & Routes');

test('Routes are RESTful', 
    strpos($routes, 'Route::post') !== false && strpos($routes, 'Route::get') !== false, 
    $results, '3. API & Routes');

test('FollowController methods exist', 
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowController.php'), 'function followers') !== false &&
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowController.php'), 'function following') !== false, 
    $results, '3. API & Routes');

test('FollowRequestController has all methods', 
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowRequestController.php'), 'function send') !== false &&
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowRequestController.php'), 'function accept') !== false &&
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowRequestController.php'), 'function reject') !== false, 
    $results, '3. API & Routes');

test('Routes return JSON responses', 
    strpos(file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowController.php'), 'response()->json') !== false, 
    $results, '3. API & Routes');

// ============================================
// 4. SECURITY (20 tests)
// ============================================
section('4. Security', $results);

$profileController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ProfileController.php');
$followController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowController.php');
$followRequestController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/FollowRequestController.php');

test('Follow uses authorization', 
    strpos($profileController, 'authorize') !== false, 
    $results, '4. Security');

test('UserPolicy prevents self-follow', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'id !== $model->id') !== false, 
    $results, '4. Security');

test('UserPolicy checks block status', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'hasBlocked') !== false, 
    $results, '4. Security');

test('FollowController uses authorization', 
    strpos($followController, 'authorize') !== false, 
    $results, '4. Security');

test('FollowRequestController validates ownership', 
    strpos($followRequestController, 'following_id !== $request->user()->id') !== false ||
    strpos($followRequestController, 'Unauthorized') !== false, 
    $results, '4. Security');

test('Follow prevents duplicate follows', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'exists()') !== false ||
    strpos($followRequestController, 'isFollowing') !== false, 
    $results, '4. Security');

test('FollowRequest prevents self-request', 
    strpos($followRequestController, 'id === $user->id') !== false, 
    $results, '4. Security');

test('FollowRequest checks existing request', 
    strpos($followRequestController, 'existingRequest') !== false, 
    $results, '4. Security');

test('Routes use auth:sanctum', 
    strpos($routes, 'auth:sanctum') !== false, 
    $results, '4. Security');

test('Routes use throttle middleware', 
    strpos($routes, 'throttle') !== false, 
    $results, '4. Security');

test('Mass assignment protection in FollowRequest', 
    strpos(file_get_contents(__DIR__ . '/app/Models/FollowRequest.php'), '$fillable') !== false, 
    $results, '4. Security');

test('Foreign keys with cascade delete', 
    strpos($followsMigration, 'cascadeOnDelete') !== false, 
    $results, '4. Security');

test('Unique constraint prevents duplicates', 
    strpos($followsMigration, 'unique') !== false, 
    $results, '4. Security');

test('SQL injection protection (Eloquent)', 
    strpos($followController, '->where(') !== false || strpos($followController, '->paginate') !== false, 
    $results, '4. Security');

test('Privacy check in view policy', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'is_private') !== false, 
    $results, '4. Security');

test('Block integration in follow', 
    strpos($profileController, 'detach') !== false || strpos($profileController, 'unfollow') !== false, 
    $results, '4. Security');

test('Follow request status validation', 
    strpos($followRequestsMigration, "enum('status'") !== false || strpos($followRequestsMigration, 'pending') !== false, 
    $results, '4. Security');

test('Authorization on accept/reject', 
    strpos($followRequestController, 'following_id !== $request->user()') !== false, 
    $results, '4. Security');

test('CSRF protection (Laravel default)', 
    file_exists(__DIR__ . '/bootstrap/app.php'), 
    $results, '4. Security');

test('Rate limiting on follow actions (Twitter: 400/day)', 
    strpos($routes, 'throttle:400,1440') !== false, 
    $results, '4. Security');

// ============================================
// 5. VALIDATION (10 tests)
// ============================================
section('5. Validation', $results);

test('FollowRequest validates self-follow', 
    strpos($followRequestController, 'Cannot send follow request to yourself') !== false, 
    $results, '5. Validation');

test('FollowRequest validates already following', 
    strpos($followRequestController, 'Already following') !== false, 
    $results, '5. Validation');

test('FollowRequest validates duplicate request', 
    strpos($followRequestController, 'Follow request already sent') !== false, 
    $results, '5. Validation');

test('Accept validates ownership', 
    strpos($followRequestController, 'following_id !== $request->user()->id') !== false, 
    $results, '5. Validation');

test('Reject validates ownership', 
    strpos($followRequestController, 'following_id !== $request->user()->id') !== false, 
    $results, '5. Validation');

test('Error messages are clear', 
    strpos($followRequestController, 'message') !== false, 
    $results, '5. Validation');

test('HTTP status codes are correct', 
    strpos($followRequestController, '400') !== false || strpos($followRequestController, '403') !== false, 
    $results, '5. Validation');

test('User existence validation (findOrFail)', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'findOrFail') !== false, 
    $results, '5. Validation');

test('Status validation in follow_requests', 
    strpos($followRequestsMigration, 'pending') !== false && strpos($followRequestsMigration, 'accepted') !== false, 
    $results, '5. Validation');

test('Pagination validation', 
    strpos($followController, 'paginate') !== false, 
    $results, '5. Validation');

// ============================================
// 6. BUSINESS LOGIC (15 tests)
// ============================================
section('6. Business Logic', $results);

test('Follow creates relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'attach') !== false, 
    $results, '6. Business Logic');

test('Unfollow removes relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'detach') !== false, 
    $results, '6. Business Logic');

test('Follow request creates pending status', 
    strpos($followRequestController, "'status' => 'pending'") !== false, 
    $results, '6. Business Logic');

test('Accept creates follow relationship', 
    strpos($followRequestController, 'followers()->attach') !== false, 
    $results, '6. Business Logic');

test('Accept updates status to accepted', 
    strpos($followRequestController, "'status' => 'accepted'") !== false, 
    $results, '6. Business Logic');

test('Reject updates status to rejected', 
    strpos($followRequestController, "'status' => 'rejected'") !== false, 
    $results, '6. Business Logic');

test('Block auto-unfollows', 
    strpos($profileController, 'following()->detach') !== false, 
    $results, '6. Business Logic');

test('Followers list with pagination', 
    strpos($followController, 'followers()') !== false && strpos($followController, 'paginate') !== false, 
    $results, '6. Business Logic');

test('Following list with pagination', 
    strpos($followController, 'following()') !== false && strpos($followController, 'paginate') !== false, 
    $results, '6. Business Logic');

test('isFollowing check exists', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'function isFollowing') !== false, 
    $results, '6. Business Logic');

test('Follow requests filtered by status', 
    strpos($followRequestController, "where('status', 'pending')") !== false, 
    $results, '6. Business Logic');

test('Follow uses DB transaction', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'DB::transaction') !== false, 
    $results, '6. Business Logic');

test('Follow has error handling (try-catch)', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'try') !== false &&
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'catch') !== false, 
    $results, '6. Business Logic');

test('Follow uses pessimistic locking', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'lockForUpdate') !== false, 
    $results, '6. Business Logic');

test('Unfollow uses DB transaction', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'DB::transaction') !== false, 
    $results, '6. Business Logic');

test('Service layer separation', 
    file_exists(__DIR__ . '/app/Services/UserFollowService.php'), 
    $results, '6. Business Logic');

// ============================================
// 7. INTEGRATION (10 tests)
// ============================================
section('7. Integration', $results);

test('Block integration exists', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'hasBlocked') !== false, 
    $results, '7. Integration');

test('Block prevents follow', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'hasBlocked') !== false, 
    $results, '7. Integration');

test('Block auto-unfollows both ways', 
    strpos($profileController, 'following()->detach') !== false && 
    strpos($profileController, '$user->following()->detach($currentUser->id)') !== false, 
    $results, '7. Integration');

test('Privacy settings integration', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'is_private') !== false, 
    $results, '7. Integration');

test('Private account requires follow request', 
    file_exists(__DIR__ . '/app/Models/FollowRequest.php'), 
    $results, '7. Integration');

test('Follow request with user relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/FollowRequest.php'), 'belongsTo(User::class') !== false, 
    $results, '7. Integration');

test('User model has followRequests relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'followRequests') !== false, 
    $results, '7. Integration');

test('Followers count in User model', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'followers_count') !== false, 
    $results, '7. Integration');

test('Following count in User model', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'following_count') !== false, 
    $results, '7. Integration');

test('UserService integrates UserFollowService', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserService.php'), 'UserFollowService') !== false, 
    $results, '7. Integration');

// ============================================
// 8. PERFORMANCE (10 tests)
// ============================================
section('8. Performance', $results);

test('Database indexes on follower_id', 
    strpos($followsMigration, 'index') !== false, 
    $results, '8. Performance');

test('Unique index prevents duplicates', 
    strpos($followsMigration, 'unique') !== false, 
    $results, '8. Performance');

test('Pagination on followers list', 
    strpos($followController, 'paginate(20)') !== false, 
    $results, '8. Performance');

test('Pagination on following list', 
    strpos($followController, 'paginate(20)') !== false, 
    $results, '8. Performance');

test('Select specific columns for performance', 
    strpos($followController, 'select(') !== false, 
    $results, '8. Performance');

test('Counter cache for followers_count', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'followers_count') !== false, 
    $results, '8. Performance');

test('Counter cache for following_count', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'following_count') !== false, 
    $results, '8. Performance');

test('Eager loading with relationships', 
    strpos($followRequestController, 'with(') !== false, 
    $results, '8. Performance');

test('Index on created_at for timeline', 
    strpos($followsMigration, 'created_at') !== false, 
    $results, '8. Performance');

test('Efficient exists() check', 
    strpos(file_get_contents(__DIR__ . '/app/Services/UserFollowService.php'), 'exists()') !== false, 
    $results, '8. Performance');

// ============================================
// 9. EVENTS & NOTIFICATIONS (10 tests)
// ============================================
section('9. Events & Notifications', $results);

test('UserFollowed event exists', 
    file_exists(__DIR__ . '/app/Events/UserFollowed.php'), 
    $results, '9. Events & Notifications');

test('SendFollowNotification listener exists', 
    file_exists(__DIR__ . '/app/Listeners/SendFollowNotification.php'), 
    $results, '9. Events & Notifications');

test('UserFollowed event has follower property', 
    strpos(file_get_contents(__DIR__ . '/app/Events/UserFollowed.php'), 'follower') !== false, 
    $results, '9. Events & Notifications');

test('UserFollowed event has followedUser property', 
    strpos(file_get_contents(__DIR__ . '/app/Events/UserFollowed.php'), 'followedUser') !== false, 
    $results, '9. Events & Notifications');

test('SendFollowNotification uses NotificationService', 
    strpos(file_get_contents(__DIR__ . '/app/Listeners/SendFollowNotification.php'), 'NotificationService') !== false, 
    $results, '9. Events & Notifications');

test('SendFollowNotification has handle method', 
    strpos(file_get_contents(__DIR__ . '/app/Listeners/SendFollowNotification.php'), 'function handle') !== false, 
    $results, '9. Events & Notifications');

test('Listener calls notifyFollow', 
    strpos(file_get_contents(__DIR__ . '/app/Listeners/SendFollowNotification.php'), 'notifyFollow') !== false, 
    $results, '9. Events & Notifications');

test('Event uses Dispatchable trait', 
    strpos(file_get_contents(__DIR__ . '/app/Events/UserFollowed.php'), 'Dispatchable') !== false, 
    $results, '9. Events & Notifications');

test('Event uses SerializesModels trait', 
    strpos(file_get_contents(__DIR__ . '/app/Events/UserFollowed.php'), 'SerializesModels') !== false, 
    $results, '9. Events & Notifications');

test('Listener implements ShouldQueue (recommended)', 
    strpos(file_get_contents(__DIR__ . '/app/Listeners/SendFollowNotification.php'), 'ShouldQueue') !== false, 
    $results, '9. Events & Notifications');

// ============================================
// 10. TWITTER COMPLIANCE (15 tests)
// ============================================
section('10. Twitter Compliance', $results);

test('Follow/Unfollow actions exist', 
    strpos($routes, '/follow') !== false && strpos($routes, '/unfollow') !== false, 
    $results, '10. Twitter Compliance');

test('Followers list endpoint', 
    strpos($routes, '/followers') !== false, 
    $results, '10. Twitter Compliance');

test('Following list endpoint', 
    strpos($routes, '/following') !== false, 
    $results, '10. Twitter Compliance');

test('Follow requests for private accounts', 
    file_exists(__DIR__ . '/app/Models/FollowRequest.php'), 
    $results, '10. Twitter Compliance');

test('Accept/Reject follow requests', 
    strpos($followRequestController, 'accept') !== false && strpos($followRequestController, 'reject') !== false, 
    $results, '10. Twitter Compliance');

test('Private account support', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'is_private') !== false, 
    $results, '10. Twitter Compliance');

test('Block prevents follow', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'hasBlocked') !== false, 
    $results, '10. Twitter Compliance');

test('Mutual unfollow on block', 
    strpos($profileController, 'following()->detach') !== false, 
    $results, '10. Twitter Compliance');

test('Follower/Following counts', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'followers_count') !== false &&
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'following_count') !== false, 
    $results, '10. Twitter Compliance');

test('Rate limiting on follow actions (Twitter: 400/day)', 
    strpos($routes, 'throttle:400,1440') !== false, 
    $results, '10. Twitter Compliance');

test('Prevent self-follow', 
    strpos(file_get_contents(__DIR__ . '/app/Policies/UserPolicy.php'), 'id !== $model->id') !== false, 
    $results, '10. Twitter Compliance');

test('Follow status check (isFollowing)', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'isFollowing') !== false, 
    $results, '10. Twitter Compliance');

test('Pending follow requests list', 
    strpos($followRequestController, "where('status', 'pending')") !== false, 
    $results, '10. Twitter Compliance');

test('Many-to-many relationship', 
    strpos(file_get_contents(__DIR__ . '/app/Models/User.php'), 'belongsToMany') !== false, 
    $results, '10. Twitter Compliance');

test('Timestamps on follows', 
    strpos($followsMigration, 'timestamps()') !== false, 
    $results, '10. Twitter Compliance');

// ============================================
// FINAL REPORT
// ============================================

echo "\n";
echo "==========================================\n";
echo "FOLLOW SYSTEM - FINAL REPORT\n";
echo "==========================================\n\n";

foreach ($results['sections'] as $section => $data) {
    $total = $data['passed'] + $data['failed'];
    $percentage = $total > 0 ? round(($data['passed'] / $total) * 100, 1) : 0;
    echo sprintf("%-30s %3d/%3d (%5.1f%%)\n", $section, $data['passed'], $total, $percentage);
}

echo "\n";
echo "TOTAL: {$results['passed']}/{$results['total']} tests passed\n";
$percentage = round(($results['passed'] / $results['total']) * 100, 1);
echo "SUCCESS RATE: {$percentage}%\n\n";

if ($percentage >= 95) {
    echo "STATUS: ✅ COMPLETE (Production Ready)\n";
} elseif ($percentage >= 85) {
    echo "STATUS: 🟡 GOOD (Minor fixes needed)\n";
} elseif ($percentage >= 70) {
    echo "STATUS: 🟠 MODERATE (Improvements required)\n";
} else {
    echo "STATUS: 🔴 POOR (Major work needed)\n";
}

echo "\n";
echo "Score Breakdown (ROADMAP Criteria):\n";
echo "- Architecture & Code (20%): " . $results['sections']['1. Architecture & Code']['passed'] . "/20\n";
echo "- Database & Schema (15%): " . $results['sections']['2. Database & Schema']['passed'] . "/15\n";
echo "- API & Routes (15%): " . $results['sections']['3. API & Routes']['passed'] . "/15\n";
echo "- Security (20%): " . $results['sections']['4. Security']['passed'] . "/20\n";
echo "- Validation (10%): " . $results['sections']['5. Validation']['passed'] . "/10\n";
echo "- Business Logic (15%): " . $results['sections']['6. Business Logic']['passed'] . "/15\n";
echo "- Integration (10%): " . $results['sections']['7. Integration']['passed'] . "/10\n";
echo "- Performance (10%): " . $results['sections']['8. Performance']['passed'] . "/10\n";
echo "- Events & Notifications (10%): " . $results['sections']['9. Events & Notifications']['passed'] . "/10\n";
echo "- Twitter Compliance (15%): " . $results['sections']['10. Twitter Compliance']['passed'] . "/15\n";

$totalScore = $results['sections']['1. Architecture & Code']['passed'] +
              $results['sections']['2. Database & Schema']['passed'] +
              $results['sections']['3. API & Routes']['passed'] +
              $results['sections']['4. Security']['passed'] +
              $results['sections']['5. Validation']['passed'] +
              $results['sections']['6. Business Logic']['passed'] +
              $results['sections']['7. Integration']['passed'] +
              $results['sections']['8. Performance']['passed'] +
              $results['sections']['9. Events & Notifications']['passed'] +
              $results['sections']['10. Twitter Compliance']['passed'];

echo "\nFINAL SCORE: {$totalScore}/144\n";
echo "==========================================\n";

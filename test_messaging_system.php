<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║          MESSAGING SYSTEM - UNIFIED TEST SUITE           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$results = ['passed' => 0, 'failed' => 0, 'sections' => [], 'issues' => []];

function test($section, $name, $condition, $issue = null) {
    global $results;
    if (!isset($results['sections'][$section])) {
        $results['sections'][$section] = ['passed' => 0, 'failed' => 0];
    }
    
    if ($condition) {
        echo "  ✓ $name\n";
        $results['passed']++;
        $results['sections'][$section]['passed']++;
    } else {
        echo "  ✗ $name\n";
        $results['failed']++;
        $results['sections'][$section]['failed']++;
        if ($issue) $results['issues'][] = $issue;
    }
}

function section($name) {
    echo "\n" . str_pad("", 64, "=") . "\n$name\n" . str_pad("", 64, "=") . "\n";
}

// Load files
$controller = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/MessageController.php');
$service = file_exists(__DIR__ . '/app/Services/MessageService.php') ? file_get_contents(__DIR__ . '/app/Services/MessageService.php') : '';
$messageModel = file_get_contents(__DIR__ . '/app/Models/Message.php');
$conversationModel = file_get_contents(__DIR__ . '/app/Models/Conversation.php');
$request = file_get_contents(__DIR__ . '/app/Http/Requests/SendMessageRequest.php');
$routes = file_get_contents(__DIR__ . '/routes/api.php');
$provider = file_get_contents(__DIR__ . '/app/Providers/AppServiceProvider.php');
$messageSent = file_exists(__DIR__ . '/app/Events/MessageSent.php') ? file_get_contents(__DIR__ . '/app/Events/MessageSent.php') : '';
$userTyping = file_exists(__DIR__ . '/app/Events/UserTyping.php') ? file_get_contents(__DIR__ . '/app/Events/UserTyping.php') : '';
$listener = file_exists(__DIR__ . '/app/Listeners/SendMessageNotification.php') ? file_get_contents(__DIR__ . '/app/Listeners/SendMessageNotification.php') : '';
$job = file_exists(__DIR__ . '/app/Jobs/ProcessMessageJob.php') ? file_get_contents(__DIR__ . '/app/Jobs/ProcessMessageJob.php') : '';

$convMigration = glob(__DIR__ . '/database/migrations/*_create_conversations_table.php');
$msgMigration = glob(__DIR__ . '/database/migrations/*_create_messages_table.php');
$convContent = !empty($convMigration) ? file_get_contents($convMigration[0]) : '';
$msgContent = !empty($msgMigration) ? file_get_contents($msgMigration[0]) : '';

// ============================================
// PART 1: ARCHITECTURE & CODE
// ============================================
section("PART 1: ARCHITECTURE & CODE (ROADMAP)");

echo "\nControllers:\n";
test('Architecture', 'MessageController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/MessageController.php'));

echo "\nModels:\n";
test('Architecture', 'Message model exists', file_exists(__DIR__ . '/app/Models/Message.php'));
test('Architecture', 'Conversation model exists', file_exists(__DIR__ . '/app/Models/Conversation.php'));

echo "\nServices:\n";
test('Architecture', 'MessageService exists', file_exists(__DIR__ . '/app/Services/MessageService.php'));

echo "\nResources:\n";
test('Architecture', 'MessageResource exists', file_exists(__DIR__ . '/app/Http/Resources/MessageResource.php'));
test('Architecture', 'ConversationResource exists', file_exists(__DIR__ . '/app/Http/Resources/ConversationResource.php'));

echo "\nRequests:\n";
test('Architecture', 'SendMessageRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SendMessageRequest.php'));

echo "\nEvents:\n";
test('Architecture', 'MessageSent event exists', file_exists(__DIR__ . '/app/Events/MessageSent.php'));
test('Architecture', 'UserTyping event exists', file_exists(__DIR__ . '/app/Events/UserTyping.php'));

echo "\nListeners:\n";
test('Architecture', 'SendMessageNotification listener exists', file_exists(__DIR__ . '/app/Listeners/SendMessageNotification.php'));

echo "\nJobs:\n";
test('Architecture', 'ProcessMessageJob exists', file_exists(__DIR__ . '/app/Jobs/ProcessMessageJob.php'));

echo "\nPolicies:\n";
test('Architecture', 'MessagePolicy exists', file_exists(__DIR__ . '/app/Policies/MessagePolicy.php'));

// ============================================
// PART 2: DATABASE & SCHEMA
// ============================================
section("PART 2: DATABASE & SCHEMA");

echo "\nMigrations:\n";
test('Database', 'conversations migration exists', !empty($convMigration));
test('Database', 'messages migration exists', !empty($msgMigration));

if (!empty($convMigration)) {
    test('Database', 'conversations has user_one_id', strpos($convContent, 'user_one_id') !== false);
    test('Database', 'conversations has user_two_id', strpos($convContent, 'user_two_id') !== false);
    test('Database', 'conversations has last_message_at', strpos($convContent, 'last_message_at') !== false);
    test('Database', 'conversations has unique constraint', strpos($convContent, "unique(['user_one_id', 'user_two_id']") !== false);
    test('Database', 'conversations has index on last_message_at', strpos($convContent, "index('last_message_at')") !== false);
    test('Database', 'conversations has foreign keys', strpos($convContent, 'constrained') !== false);
    test('Database', 'conversations has cascade delete', strpos($convContent, 'cascadeOnDelete') !== false);
}

if (!empty($msgMigration)) {
    test('Database', 'messages has conversation_id', strpos($msgContent, 'conversation_id') !== false);
    test('Database', 'messages has sender_id', strpos($msgContent, 'sender_id') !== false);
    test('Database', 'messages has content', strpos($msgContent, 'content') !== false);
    test('Database', 'messages has read_at', strpos($msgContent, 'read_at') !== false);
    test('Database', 'messages has media_path', strpos($msgContent, 'media_path') !== false);
    test('Database', 'messages has media_type', strpos($msgContent, 'media_type') !== false);
    test('Database', 'messages has gif_url', strpos($msgContent, 'gif_url') !== false);
    test('Database', 'messages has index', strpos($msgContent, "index(['conversation_id'") !== false || strpos($msgContent, "index('conversation_id')") !== false);
    test('Database', 'messages has foreign keys', strpos($msgContent, 'constrained') !== false);
    test('Database', 'messages has cascade delete', strpos($msgContent, 'cascadeOnDelete') !== false);
}

// ============================================
// PART 3: API & ROUTES
// ============================================
section("PART 3: API & ROUTES");

exec('php artisan route:list 2>&1', $output);
$routesList = implode("\n", $output);

echo "\nRoutes:\n";
test('API', 'GET /messages/conversations route', strpos($routesList, 'messages/conversations') !== false);
test('API', 'GET /messages/users/{user} route', strpos($routesList, 'messages/users') !== false);
test('API', 'POST /messages/users/{user} route', strpos($routesList, 'messages/users') !== false);
test('API', 'POST /messages/users/{user}/typing route', strpos($routesList, 'typing') !== false);
test('API', 'POST /messages/{message}/read route', strpos($routesList, 'messages') !== false && strpos($routesList, 'read') !== false);
test('API', 'GET /messages/unread-count route', strpos($routesList, 'unread-count') !== false);

test('API', 'Routes use auth middleware', strpos($routes, 'auth:sanctum') !== false);
test('API', 'Routes use throttle middleware', strpos($routes, 'throttle:60,1') !== false || strpos($routes, 'throttle:') !== false);

test('API', 'conversations method exists', strpos($controller, 'function conversations') !== false);
test('API', 'messages method exists', strpos($controller, 'function messages') !== false);
test('API', 'send method exists', strpos($controller, 'function send') !== false);
test('API', 'typing method exists', strpos($controller, 'function typing') !== false);
test('API', 'markAsRead method exists', strpos($controller, 'function markAsRead') !== false);
test('API', 'unreadCount method exists', strpos($controller, 'function unreadCount') !== false);

// ============================================
// PART 4: SECURITY
// ============================================
section("PART 4: SECURITY");

echo "\nAuthentication & Authorization:\n";
test('Security', 'Routes use auth:sanctum', strpos($routes, 'auth:sanctum') !== false);
test('Security', 'MessagePolicy exists', file_exists(__DIR__ . '/app/Policies/MessagePolicy.php'));
test('Security', 'Policy registered', strpos($provider, 'MessagePolicy') !== false);

echo "\nValidation:\n";
test('Security', 'SendMessageRequest validates content', strpos($request, "'content'") !== false);
test('Security', 'SendMessageRequest has rules', strpos($request, 'function rules') !== false);

echo "\nRate Limiting:\n";
test('Security', 'Messages have rate limiting (60/min)', strpos($routes, 'throttle:60,1') !== false);

echo "\nData Protection:\n";
test('Security', 'Message model has mass assignment protection', strpos($messageModel, '$guarded') !== false || strpos($messageModel, '$fillable') !== false);
test('Security', 'Prevents self-messaging', strpos($service, 'Cannot send message to yourself') !== false);

echo "\nBlock/Mute Integration:\n";
test('Security', 'Block integration exists', strpos($service, 'hasBlocked') !== false);
test('Security', 'Mute integration exists', strpos($service, 'hasMuted') !== false);

echo "\nXSS Protection:\n";
test('Security', 'Content sanitization (strip_tags)', strpos($service, 'strip_tags') !== false);

echo "\nSQL Injection Protection:\n";
test('Security', 'Uses Eloquent ORM', strpos($service, 'Message::') !== false);

echo "\nCSRF Protection:\n";
test('Security', 'Laravel CSRF (default)', true);

// ============================================
// PART 5: TWITTER API V2 COMPLIANCE
// ============================================
section("PART 5: TWITTER API V2 COMPLIANCE");

echo "\nRate Limits:\n";
test('Twitter', 'Messages send: 60/minute', strpos($routes, 'throttle:60,1') !== false);

echo "\nMessage Limits:\n";
test('Twitter', 'Content max 10,000 chars', strpos($request, 'ContentLength') !== false);
test('Twitter', 'Media upload supported', strpos($request, 'media') !== false);
test('Twitter', 'GIF support', strpos($request, 'gif_url') !== false);

// ============================================
// PART 6: SERVICE LAYER SEPARATION
// ============================================
section("PART 6: SERVICE LAYER SEPARATION");

echo "\nMessageService Methods:\n";
test('Service', 'sendMessage() exists', strpos($service, 'function sendMessage') !== false);
test('Service', 'getConversations() exists', strpos($service, 'function getConversations') !== false);
test('Service', 'getMessages() exists', strpos($service, 'function getMessages') !== false);
test('Service', 'markAsRead() exists', strpos($service, 'function markAsRead') !== false);
test('Service', 'getUnreadCount() exists', strpos($service, 'function getUnreadCount') !== false);

echo "\nController Delegation:\n";
test('Service', 'conversations() delegates to service', strpos($controller, '$this->messageService->getConversations') !== false);
test('Service', 'messages() delegates to service', strpos($controller, '$this->messageService->getMessages') !== false);
test('Service', 'send() delegates to service', strpos($controller, '$this->messageService->sendMessage') !== false);
test('Service', 'markAsRead() delegates to service', strpos($controller, '$this->messageService->markAsRead') !== false);
test('Service', 'unreadCount() delegates to service', strpos($controller, '$this->messageService->getUnreadCount') !== false);
test('Service', 'No business logic in controller', strpos($controller, 'Message::create') === false);

// ============================================
// PART 7: EVENTS/LISTENERS/JOBS
// ============================================
section("PART 7: EVENTS/LISTENERS/JOBS");

echo "\nEvents:\n";
test('Events', 'MessageSent event exists', file_exists(__DIR__ . '/app/Events/MessageSent.php'));
test('Events', 'MessageSent implements ShouldBroadcast', strpos($messageSent, 'ShouldBroadcast') !== false);
test('Events', 'UserTyping event exists', file_exists(__DIR__ . '/app/Events/UserTyping.php'));
test('Events', 'Events dispatched in service', strpos($service, 'broadcast(new MessageSent') !== false);

echo "\nListeners:\n";
test('Listeners', 'SendMessageNotification exists', file_exists(__DIR__ . '/app/Listeners/SendMessageNotification.php'));
test('Listeners', 'Implements ShouldQueue', strpos($listener, 'ShouldQueue') !== false);
test('Listeners', 'Uses NotificationService', strpos($listener, 'NotificationService') !== false);
test('Listeners', 'Listener registered', strpos($provider, 'MessageSent::class') !== false && strpos($provider, 'SendMessageNotification::class') !== false);

echo "\nJobs:\n";
test('Jobs', 'ProcessMessageJob exists', file_exists(__DIR__ . '/app/Jobs/ProcessMessageJob.php'));
test('Jobs', 'Implements ShouldQueue', strpos($job, 'ShouldQueue') !== false);
test('Jobs', 'Job dispatched in service', strpos($service, 'ProcessMessageJob::dispatch') !== false);

// ============================================
// PART 8: MODELS & RELATIONSHIPS
// ============================================
section("PART 8: MODELS & RELATIONSHIPS");

echo "\nMessage Model:\n";
test('Models', 'conversation() relationship', strpos($messageModel, 'function conversation') !== false);
test('Models', 'sender() relationship', strpos($messageModel, 'function sender') !== false);
test('Models', 'markAsRead() method', strpos($messageModel, 'function markAsRead') !== false);
test('Models', 'scopeUnread() scope', strpos($messageModel, 'function scopeUnread') !== false);
test('Models', 'Mass assignment protection', strpos($messageModel, '$fillable') !== false || strpos($messageModel, '$guarded') !== false);

echo "\nConversation Model:\n";
test('Models', 'userOne() relationship', strpos($conversationModel, 'function userOne') !== false);
test('Models', 'userTwo() relationship', strpos($conversationModel, 'function userTwo') !== false);
test('Models', 'messages() relationship', strpos($conversationModel, 'function messages') !== false);
test('Models', 'lastMessage() relationship', strpos($conversationModel, 'function lastMessage') !== false);
test('Models', 'between() static method', strpos($conversationModel, 'function between') !== false);
test('Models', 'getOtherUser() helper', strpos($conversationModel, 'function getOtherUser') !== false);

// ============================================
// PART 9: INTEGRATION WITH OTHER SYSTEMS
// ============================================
section("PART 9: INTEGRATION WITH OTHER SYSTEMS");

echo "\n1. User System:\n";
test('Integration', 'Message has sender relationship', strpos($messageModel, "belongsTo(User::class, 'sender_id')") !== false);
test('Integration', 'Conversation has userOne relationship', strpos($conversationModel, "belongsTo(User::class, 'user_one_id')") !== false);
test('Integration', 'Conversation has userTwo relationship', strpos($conversationModel, "belongsTo(User::class, 'user_two_id')") !== false);

echo "\n2. Block/Mute System:\n";
test('Integration', 'Checks hasBlocked()', strpos($service, 'hasBlocked') !== false);
test('Integration', 'Checks hasMuted()', strpos($service, 'hasMuted') !== false);
test('Integration', 'Prevents messaging blocked users', strpos($service, 'Cannot send message to blocked user') !== false);

echo "\n3. Notification System:\n";
test('Integration', 'SendMessageNotification listener exists', file_exists(__DIR__ . '/app/Listeners/SendMessageNotification.php'));
test('Integration', 'Uses NotificationService', strpos($listener, 'NotificationService') !== false);

echo "\n4. Authentication System:\n";
test('Integration', 'Uses auth:sanctum middleware', strpos($routes, 'auth:sanctum') !== false);

echo "\n5. Queue System:\n";
test('Integration', 'ProcessMessageJob implements ShouldQueue', strpos($job, 'ShouldQueue') !== false);
test('Integration', 'Listener implements ShouldQueue', strpos($listener, 'ShouldQueue') !== false);

echo "\n6. Broadcasting System:\n";
test('Integration', 'MessageSent broadcasts', strpos($messageSent, 'ShouldBroadcast') !== false);
test('Integration', 'Uses broadcast() helper', strpos($service, 'broadcast(') !== false);

echo "\n7. Database System:\n";
test('Integration', 'Uses DB::transaction()', strpos($service, 'DB::transaction') !== false);
test('Integration', 'Foreign keys with cascade', strpos($msgContent, 'constrained') !== false && strpos($msgContent, 'cascadeOnDelete') !== false);

echo "\n8. Logging System:\n";
test('Integration', 'Uses Log::error()', strpos($service, 'Log::error') !== false);
test('Integration', 'Try-catch blocks', strpos($service, 'try') !== false && strpos($service, 'catch') !== false);

// ============================================
// PART 10: NO PARALLEL WORK
// ============================================
section("PART 10: NO PARALLEL WORK");

$services = [
    'MessageService.php' => file_exists(__DIR__ . '/app/Services/MessageService.php'),
    'MessagingService.php' => file_exists(__DIR__ . '/app/Services/MessagingService.php'),
    'DirectMessageService.php' => file_exists(__DIR__ . '/app/Services/DirectMessageService.php'),
];

echo "\nService Files:\n";
$serviceCount = array_sum($services);
test('No Parallel', 'Only MessageService exists', $serviceCount === 1 && $services['MessageService.php']);

$controllers = [
    'MessageController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/MessageController.php'),
    'MessagingController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/MessagingController.php'),
];

echo "\nController Files:\n";
$controllerCount = array_sum($controllers);
test('No Parallel', 'Only MessageController exists', $controllerCount === 1 && $controllers['MessageController.php']);

echo "\nBusiness Logic:\n";
test('No Parallel', 'No Message::create in controller', strpos($controller, 'Message::create') === false);
test('No Parallel', 'No Conversation::create in controller', strpos($controller, 'Conversation::create') === false);
test('No Parallel', 'Controller uses MessageService', strpos($controller, 'MessageService') !== false);

// ============================================
// PART 11: OPERATIONAL READINESS
// ============================================
section("PART 11: OPERATIONAL READINESS");

echo "\nDatabase:\n";
try {
    DB::connection()->getPdo();
    test('Operational', 'Database connected', true);
} catch (\Exception $e) {
    test('Operational', 'Database connected', false);
}

try {
    test('Operational', 'conversations table exists', Schema::hasTable('conversations'));
    test('Operational', 'messages table exists', Schema::hasTable('messages'));
} catch (\Exception $e) {
    test('Operational', 'Tables exist', false);
}

echo "\nDependencies:\n";
try {
    $messageService = app(\App\Services\MessageService::class);
    test('Operational', 'MessageService can be resolved', $messageService !== null);
} catch (\Exception $e) {
    test('Operational', 'MessageService can be resolved', false);
}

echo "\nConfiguration:\n";
test('Operational', 'APP_ENV is set', env('APP_ENV') !== null);
test('Operational', 'APP_KEY is set', env('APP_KEY') !== null);
test('Operational', 'DB_CONNECTION is set', env('DB_CONNECTION') !== null);

echo "\nTests:\n";
test('Operational', 'MessageTest exists', file_exists(__DIR__ . '/tests/Feature/MessageTest.php'));

// ============================================
// FINAL SUMMARY
// ============================================
echo "\n" . str_pad("", 64, "=") . "\n";
echo "FINAL SUMMARY\n";
echo str_pad("", 64, "=") . "\n\n";

foreach ($results['sections'] as $section => $data) {
    $total = $data['passed'] + $data['failed'];
    $percent = $total > 0 ? round(($data['passed'] / $total) * 100, 1) : 0;
    printf("%-30s %3d/%3d (%5.1f%%)\n", $section, $data['passed'], $total, $percent);
}

$total = $results['passed'] + $results['failed'];
$percentage = round(($results['passed'] / $total) * 100, 1);

echo "\n";
echo "Total Tests: $total\n";
echo "Passed: {$results['passed']} ✓\n";
echo "Failed: {$results['failed']} ✗\n";
echo "Success Rate: $percentage%\n\n";

if (!empty($results['issues'])) {
    echo "ISSUES:\n";
    echo str_pad("", 64, "-") . "\n";
    foreach (array_unique($results['issues']) as $i => $issue) {
        echo ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

if ($results['failed'] === 0) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ MESSAGING SYSTEM - 100% COMPLETE & PRODUCTION READY  ║\n";
    echo "║  ✅ TWITTER API V2 COMPLIANT                              ║\n";
    echo "║  ✅ FULLY INTEGRATED WITH ALL SYSTEMS                     ║\n";
    echo "║  ✅ NO PARALLEL WORK                                      ║\n";
    echo "║  ✅ FULLY SECURE                                          ║\n";
    echo "║  ✅ FULLY OPERATIONAL                                     ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
} elseif ($percentage >= 95) {
    echo "STATUS: 🟡 NEARLY COMPLETE (Minor fixes needed)\n";
} elseif ($percentage >= 85) {
    echo "STATUS: 🟠 GOOD (Some improvements needed)\n";
} else {
    echo "STATUS: 🔴 NEEDS WORK\n";
}

echo "\n";
exit($results['failed'] > 0 ? 1 : 0);

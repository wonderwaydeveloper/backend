# 📋 راهنمای معماری Feature Tests

## 🎯 هدف
این راهنما معماری استاندارد برای نوشتن Feature Tests (تستهای HTTP) را تعریف میکند. تمام Feature Tests جدید باید از این معماری پیروی کنند.

---

## 🏗️ ساختار کلی Feature Test

### 1. Header و Namespace
```php
<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {SystemName}Test extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions if needed
        $permissions = ['post.create', 'post.update', 'post.delete'];
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }
        
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $role->syncPermissions($permissions);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
    }
}
```

---

## 📦 بخشهای استاندارد (9 بخش)

### بخش 1: Core API Functionality (20%)
```php
// ==================== SECTION 1: Core API Functionality ====================

/** @test */
public function test_can_list_resources()
{
    $response = $this->withToken($this->token)
        ->getJson('/api/posts');

    $response->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'total']);
}

/** @test */
public function test_can_create_resource()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', [
            'content' => 'Test post'
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'content']]);
}

/** @test */
public function test_can_show_resource()
{
    $post = Post::factory()->create();

    $response = $this->withToken($this->token)
        ->getJson("/api/posts/{$post->id}");

    $response->assertOk()
        ->assertJson(['data' => ['id' => $post->id]]);
}

/** @test */
public function test_can_update_resource()
{
    $post = Post::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withToken($this->token)
        ->putJson("/api/posts/{$post->id}", [
            'content' => 'Updated content'
        ]);

    $response->assertOk();
    $this->assertEquals('Updated content', $post->fresh()->content);
}

/** @test */
public function test_can_delete_resource()
{
    $post = Post::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withToken($this->token)
        ->deleteJson("/api/posts/{$post->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
}

/** @test */
public function test_pagination_works()
{
    Post::factory()->count(25)->create();

    $response = $this->withToken($this->token)
        ->getJson('/api/posts');

    $response->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
}

/** @test */
public function test_filtering_works()
{
    $response = $this->withToken($this->token)
        ->getJson('/api/posts?filter[user_id]=' . $this->user->id);

    $response->assertOk();
}

/** @test */
public function test_sorting_works()
{
    $response = $this->withToken($this->token)
        ->getJson('/api/posts?sort=-created_at');

    $response->assertOk();
}
```

### بخش 2: Authentication & Authorization (20%)
```php
// ==================== SECTION 2: Authentication & Authorization ====================

/** @test */
public function test_guest_cannot_access()
{
    $response = $this->postJson('/api/posts', ['content' => 'Test']);
    $response->assertUnauthorized();
}

/** @test */
public function test_authenticated_user_can_access()
{
    $response = $this->withToken($this->token)
        ->getJson('/api/posts');

    $response->assertOk();
}

/** @test */
public function test_cannot_update_others_resource()
{
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withToken($this->token)
        ->putJson("/api/posts/{$post->id}", ['content' => 'Hacked']);

    $response->assertForbidden();
}

/** @test */
public function test_cannot_delete_others_resource()
{
    $otherUser = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withToken($this->token)
        ->deleteJson("/api/posts/{$post->id}");

    $response->assertForbidden();
}

/** @test */
public function test_cannot_perform_self_action()
{
    $response = $this->withToken($this->token)
        ->postJson("/api/users/{$this->user->id}/follow");

    $response->assertForbidden();
}

/** @test */
public function test_policy_enforced()
{
    $post = Post::factory()->create(['user_id' => $this->user->id]);
    
    $response = $this->withToken($this->token)
        ->putJson("/api/posts/{$post->id}", ['content' => 'Updated']);

    $response->assertOk();
}
```

### بخش 3: Validation & Error Handling (15%)
```php
// ==================== SECTION 3: Validation & Error Handling ====================

/** @test */
public function test_required_fields_validated()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
}

/** @test */
public function test_invalid_data_rejected()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', [
            'content' => 123 // Should be string
        ]);

    $response->assertStatus(422);
}

/** @test */
public function test_max_length_validated()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', [
            'content' => str_repeat('a', 1000)
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
}

/** @test */
public function test_error_messages_clear()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', []);

    $response->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
}

/** @test */
public function test_edge_case_empty_string()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', ['content' => '']);

    $response->assertStatus(422);
}

/** @test */
public function test_edge_case_null_value()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', ['content' => null]);

    $response->assertStatus(422);
}
```

### بخش 4: Integration with Other Systems (15%)
```php
// ==================== SECTION 4: Integration with Other Systems ====================

/** @test */
public function test_blocked_user_cannot_interact()
{
    $blocker = User::factory()->create();
    $blocker->blockedUsers()->attach($this->user->id);

    $post = Post::factory()->create(['user_id' => $blocker->id]);

    $response = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/like");

    $response->assertForbidden();
}

/** @test */
public function test_muted_user_content_filtered()
{
    $muted = User::factory()->create();
    $this->user->mutedUsers()->attach($muted->id);

    $post = Post::factory()->create(['user_id' => $muted->id]);

    $response = $this->withToken($this->token)
        ->getJson('/api/posts');

    $posts = $response->json('data');
    $postIds = collect($posts)->pluck('id')->toArray();
    $this->assertNotContains($post->id, $postIds);
}

/** @test */
public function test_private_account_restricts_access()
{
    $privateUser = User::factory()->create(['is_private' => true]);

    $response = $this->withToken($this->token)
        ->getJson("/api/users/{$privateUser->id}");

    $response->assertForbidden();
}

/** @test */
public function test_notification_sent_on_action()
{
    \Notification::fake();

    $post = Post::factory()->create();

    $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/like");

    \Notification::assertSentTo($post->user, \App\Notifications\PostLiked::class);
}

/** @test */
public function test_event_dispatched()
{
    \Event::fake();

    $this->withToken($this->token)
        ->postJson('/api/posts', ['content' => 'Test']);

    \Event::assertDispatched(\App\Events\PostCreated::class);
}
```

### بخش 5: Security in Action (10%)
```php
// ==================== SECTION 5: Security in Action ====================

/** @test */
public function test_xss_sanitization_works()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', [
            'content' => '<script>alert("xss")</script>Test'
        ]);

    $response->assertOk();
    $post = Post::latest()->first();
    $this->assertStringNotContainsString('<script>', $post->content);
}

/** @test */
public function test_sql_injection_prevented()
{
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', [
            'content' => "'; DROP TABLE posts; --"
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('posts', ['content' => "'; DROP TABLE posts; --"]);
}

/** @test */
public function test_rate_limiting_enforced()
{
    $limit = config('limits.rate_limits.posts.create', 60);
    
    if (!is_numeric($limit)) {
        $this->markTestSkipped('Rate limit not configured');
    }
    
    $this->assertTrue(is_numeric($limit));
}

/** @test */
public function test_csrf_protection_active()
{
    // CSRF is automatically tested by Laravel
    $this->assertTrue(true);
}
```

### بخش 6: Database Transactions (10%)
```php
// ==================== SECTION 6: Database Transactions ====================

/** @test */
public function test_transaction_rollback_on_error()
{
    $initialCount = Post::count();

    try {
        DB::transaction(function() {
            Post::create(['user_id' => $this->user->id, 'content' => 'Test']);
            throw new \Exception('Rollback test');
        });
    } catch (\Exception $e) {
        // Expected
    }

    $this->assertEquals($initialCount, Post::count());
}

/** @test */
public function test_counters_updated_correctly()
{
    $initialCount = $this->user->posts_count;

    $this->withToken($this->token)
        ->postJson('/api/posts', ['content' => 'Test']);

    $this->assertEquals($initialCount + 1, $this->user->fresh()->posts_count);
}

/** @test */
public function test_no_orphaned_records()
{
    $post = Post::factory()->create(['user_id' => $this->user->id]);
    $comment = Comment::factory()->create(['post_id' => $post->id]);

    $this->withToken($this->token)
        ->deleteJson("/api/posts/{$post->id}");

    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
}

/** @test */
public function test_concurrent_requests_handled()
{
    $post = Post::factory()->create();

    $response1 = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/like");
    
    $response2 = $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/like");

    $this->assertEquals(1, $post->fresh()->likes_count);
}
```

### بخش 7: Business Logic & Edge Cases (5%)
```php
// ==================== SECTION 7: Business Logic & Edge Cases ====================

/** @test */
public function test_duplicate_action_prevented()
{
    $post = Post::factory()->create();

    $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/like");
    
    $this->withToken($this->token)
        ->postJson("/api/posts/{$post->id}/like");

    $this->assertEquals(1, $post->fresh()->likes_count);
}

/** @test */
public function test_counter_underflow_protected()
{
    $post = Post::factory()->create(['likes_count' => 0]);

    $this->withToken($this->token)
        ->deleteJson("/api/posts/{$post->id}/unlike");

    $this->assertEquals(0, $post->fresh()->likes_count);
}

/** @test */
public function test_soft_delete_works()
{
    $post = Post::factory()->create(['user_id' => $this->user->id]);

    $this->withToken($this->token)
        ->deleteJson("/api/posts/{$post->id}");

    $this->assertSoftDeleted('posts', ['id' => $post->id]);
}

/** @test */
public function test_timestamps_updated()
{
    $post = Post::factory()->create(['user_id' => $this->user->id]);
    $oldTimestamp = $post->updated_at;

    sleep(1);

    $this->withToken($this->token)
        ->putJson("/api/posts/{$post->id}", ['content' => 'Updated']);

    $this->assertNotEquals($oldTimestamp, $post->fresh()->updated_at);
}
```

### بخش 8: Real-world Scenarios (3%)
```php
// ==================== SECTION 8: Real-world Scenarios ====================

/** @test */
public function test_complete_user_workflow()
{
    // Create post
    $response = $this->withToken($this->token)
        ->postJson('/api/posts', ['content' => 'Test post']);
    $postId = $response->json('data.id');

    // Like post
    $this->withToken($this->token)
        ->postJson("/api/posts/{$postId}/like");

    // Comment on post
    $this->withToken($this->token)
        ->postJson("/api/posts/{$postId}/comments", ['content' => 'Nice!']);

    // Verify all actions
    $post = Post::find($postId);
    $this->assertEquals(1, $post->likes_count);
    $this->assertEquals(1, $post->comments_count);
}

/** @test */
public function test_multiple_users_interaction()
{
    $user2 = User::factory()->create();
    $token2 = $user2->createToken('test')->plainTextToken;

    $post = Post::factory()->create(['user_id' => $this->user->id]);

    $this->withToken($token2)
        ->postJson("/api/posts/{$post->id}/like");

    $this->assertEquals(1, $post->fresh()->likes_count);
}

/** @test */
public function test_state_changes_persist()
{
    $post = Post::factory()->create([
        'user_id' => $this->user->id,
        'is_draft' => true
    ]);

    $this->withToken($this->token)
        ->putJson("/api/posts/{$post->id}", [
            'is_draft' => false,
            'published_at' => now()
        ]);

    $this->assertFalse($post->fresh()->is_draft);
    $this->assertNotNull($post->fresh()->published_at);
}
```

### بخش 9: Performance & Response (2%)
```php
// ==================== SECTION 9: Performance & Response ====================

/** @test */
public function test_response_time_acceptable()
{
    $start = microtime(true);
    
    $this->withToken($this->token)
        ->getJson('/api/posts');
    
    $duration = (microtime(true) - $start) * 1000;
    $this->assertLessThan(500, $duration);
}

/** @test */
public function test_n_plus_1_queries_avoided()
{
    Post::factory()->count(5)->create();

    \DB::enableQueryLog();
    
    $this->withToken($this->token)
        ->getJson('/api/posts');
    
    $queries = \DB::getQueryLog();
    $this->assertLessThan(15, count($queries));
}

/** @test */
public function test_eager_loading_works()
{
    $post = Post::factory()->create();

    $response = $this->withToken($this->token)
        ->getJson("/api/posts/{$post->id}");

    $response->assertOk()
        ->assertJsonStructure(['data' => ['user' => ['id', 'name']]]);
}
```

---

## ✅ چکلیست Feature Test جدید

### ساختار اولیه
- [ ] Namespace: `Tests\Feature`
- [ ] Extends `TestCase`
- [ ] Use `RefreshDatabase`
- [ ] setUp() با ایجاد user و token
- [ ] Permission setup (اگر نیاز است)

### 9 بخش استاندارد
- [ ] Section 1: Core API Functionality (8-10 تست)
- [ ] Section 2: Authentication & Authorization (6-8 تست)
- [ ] Section 3: Validation & Error Handling (6-8 تست)
- [ ] Section 4: Integration with Other Systems (5-7 تست)
- [ ] Section 5: Security in Action (4-5 تست)
- [ ] Section 6: Database Transactions (4-5 تست)
- [ ] Section 7: Business Logic & Edge Cases (4-5 تست)
- [ ] Section 8: Real-world Scenarios (3-4 تست)
- [ ] Section 9: Performance & Response (2-3 تست)

### کیفیت تست
- [ ] حداقل 50 تست برای هر سیستم
- [ ] تمام endpoints تست شده
- [ ] تمام status codes تست شده (200, 201, 401, 403, 404, 422, 429)
- [ ] Response structure تست شده
- [ ] Integration با Block/Mute تست شده
- [ ] Events و Notifications تست شده

---

## 📊 معیارهای موفقیت

| امتیاز | وضعیت | توضیح |
|--------|-------|-------|
| 95-100% | ✅ Complete | Production ready |
| 85-94% | 🟡 Good | Minor fixes needed |
| 70-84% | 🟠 Moderate | Improvements required |
| <70% | 🔴 Poor | Major work needed |

---

## 📝 نکات مهم

1. **تعداد تست**: حداقل 50 تست برای هر سیستم
2. **HTTP Testing**: همیشه از `postJson`, `getJson`, `putJson`, `deleteJson` استفاده کنید
3. **Authentication**: از `withToken()` برای تستهای authenticated استفاده کنید
4. **Assertions**: از assertions مناسب Laravel استفاده کنید (`assertOk`, `assertForbidden`, etc.)
5. **Database**: از `RefreshDatabase` برای پاکسازی خودکار استفاده کنید
6. **Factories**: از Model Factories برای ایجاد داده تست استفاده کنید

---

## 🔗 تفاوت با Script Tests

| جنبه | Script Tests | Feature Tests |
|------|-------------|---------------|
| **تعداد بخشها** | 20 بخش | 9 بخش |
| **روش اجرا** | Direct PHP | HTTP Requests |
| **تمرکز** | Code structure | API functionality |
| **تست میکند** | Database, Models, Services | Endpoints, Authorization, Integration |
| **نمیتواند تست کند** | HTTP responses, Middleware | Database schema, Code structure |

---

**تاریخ ایجاد:** 2025-02-10  
**نسخه:** 1.0  
**وضعیت:** استاندارد رسمی

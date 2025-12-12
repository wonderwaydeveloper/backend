<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Follow;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PostPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        // ایجاد یک نمونه از PostPolicy برای تست
        $this->policy = new PostPolicy();
    }

    // تست: کاربر می‌تواند پست عمومی کاربر عمومی دیگر را ببیند
    #[Test]
    public function user_can_view_public_post_of_public_user()
    {
        // کاربر عمومی به پست عمومی کاربر عمومی دیگر دسترسی دارد
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
        ]);

        $this->assertTrue($this->policy->view($user, $post));
    }

    // تست: کاربر نمی‌تواند پست حذف شده را ببیند مگر اینکه نویسنده یا ادمین باشد
    #[Test]
    public function user_cannot_view_deleted_post_unless_author_or_admin()
    {
        $user = User::factory()->create(['is_private' => false]);
        $author = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create(['user_id' => $author->id]);
        $post->delete();

        // کاربر عادی نمی‌تواند پست حذف شده را ببیند
        $this->assertFalse($this->policy->view($user, $post));

        // نویسنده می‌تواند پست حذف شده خود را ببیند
        $this->assertTrue($this->policy->view($author, $post));

        // ادمین می‌تواند پست حذف شده را ببیند
        $admin = User::factory()->create(['username' => 'admin', 'is_private' => false]);
        $this->assertTrue($this->policy->view($admin, $post));
    }

    // تست: کاربر زیر سن نمی‌تواند محتوای حساس را ببیند
    #[Test]
    public function user_cannot_view_sensitive_content_if_underage()
    {
        $adult = User::factory()->create(['is_private' => false]);
        $child = User::factory()->underage()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);

        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_sensitive' => true,
            'is_private' => false,
        ]);

        $this->assertTrue($this->policy->view($adult, $post));
        $this->assertFalse($this->policy->view($child, $post));
    }

    // تست: پست‌های کاربر خصوصی فقط برای دنبال‌کنندگان تایید شده قابل مشاهده هستند
    #[Test]
    public function private_user_posts_only_visible_to_approved_followers()
    {
        // کاربر خصوصی
        $privateUser = User::factory()->create(['is_private' => true]);

        // یک دنبال‌کننده و یک غیر دنبال‌کننده
        $follower = User::factory()->create(['is_private' => false]);
        $nonFollower = User::factory()->create(['is_private' => false]);

        // پست عمومی کاربر خصوصی
        $publicPost = Post::factory()->create([
            'user_id' => $privateUser->id,
            'is_private' => false,
        ]);

        // پست خصوصی کاربر خصوصی
        $privatePost = Post::factory()->create([
            'user_id' => $privateUser->id,
            'is_private' => true,
        ]);

        // غیر دنبال‌کننده نمی‌تواند پست‌های کاربر خصوصی را ببیند
        $this->assertFalse($this->policy->view($nonFollower, $publicPost));
        $this->assertFalse($this->policy->view($nonFollower, $privatePost));

        // دنبال‌کننده بدون تایید نمی‌تواند ببیند
        $this->assertFalse($this->policy->view($follower, $publicPost));
        $this->assertFalse($this->policy->view($follower, $privatePost));

        // ایجاد دنبال‌کردن تایید شده
        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => now(),
        ]);

        // حالا دنبال‌کننده تایید شده می‌تواند هر دو پست را ببیند
        $this->assertTrue($this->policy->view($follower, $publicPost));
        $this->assertTrue($this->policy->view($follower, $privatePost));
    }

    // تست: پست‌های خصوصی کاربر عمومی فقط برای دنبال‌کنندگان تایید شده قابل مشاهده هستند
    #[Test]
    public function public_user_private_posts_only_visible_to_approved_followers()
    {
        // کاربر عمومی که یک پست خصوصی دارد
        $publicUser = User::factory()->create(['is_private' => false]);
        $follower = User::factory()->create(['is_private' => false]);
        $nonFollower = User::factory()->create(['is_private' => false]);

        // پست خصوصی کاربر عمومی
        $privatePost = Post::factory()->create([
            'user_id' => $publicUser->id,
            'is_private' => true,
        ]);

        // پست عمومی کاربر عمومی (کنترل)
        $publicPost = Post::factory()->create([
            'user_id' => $publicUser->id,
            'is_private' => false,
        ]);

        // غیر دنبال‌کننده نمی‌تواند پست خصوصی را ببیند اما می‌تواند پست عمومی را ببیند
        $this->assertFalse($this->policy->view($nonFollower, $privatePost));
        $this->assertTrue($this->policy->view($nonFollower, $publicPost));

        // دنبال‌کننده بدون تایید نمی‌تواند پست خصوصی را ببیند
        $this->assertFalse($this->policy->view($follower, $privatePost));

        // ایجاد دنبال‌کردن تایید شده
        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $publicUser->id,
            'approved_at' => now(),
        ]);

        // حالا دنبال‌کننده تایید شده می‌تواند پست خصوصی را ببیند
        $this->assertTrue($this->policy->view($follower, $privatePost));
        $this->assertTrue($this->policy->view($follower, $publicPost));
    }

    // تست: کاربر مسدود شده نمی‌تواند پست ایجاد کند
    #[Test]
    public function user_can_create_post_if_not_banned()
    {
        $normalUser = User::factory()->create(['is_banned' => false]);
        $bannedUser = User::factory()->create(['is_banned' => true]);

        $this->assertTrue($this->policy->create($normalUser));
        $this->assertFalse($this->policy->create($bannedUser));
    }

    // تست: فقط نویسنده می‌تواند پست را ویرایش کند
    #[Test]
    public function only_author_can_update_post()
    {
        $author = User::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($this->policy->update($author, $post));
        $this->assertFalse($this->policy->update($otherUser, $post));
    }

    // تست: نویسنده نمی‌تواند پست حذف شده را ویرایش کند
    #[Test]
    public function author_cannot_update_deleted_post()
    {
        $author = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create(['user_id' => $author->id]);
        $post->delete();

        $this->assertFalse($this->policy->update($author, $post));
    }

    // تست: نویسنده یا ادمین می‌توانند پست را حذف کنند
    #[Test]
    public function author_or_admin_can_delete_post()
    {
        $author = User::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create(['is_private' => false]);
        $admin = User::factory()->create(['username' => 'admin', 'is_private' => false]);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($this->policy->delete($author, $post));
        $this->assertFalse($this->policy->delete($otherUser, $post));
        $this->assertTrue($this->policy->delete($admin, $post));
    }

    // تست: کاربر نمی‌تواند پست خودش را لایک کند
    #[Test]
    public function user_cannot_like_own_post()
    {
        $user = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_private' => false,
        ]);

        $this->assertFalse($this->policy->like($user, $post));
    }

    // تست: کاربر می‌تواند پست عمومی کاربر دیگر را لایک کند
    #[Test]
    public function user_can_like_public_post_of_other_user()
    {
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
        ]);

        $this->assertTrue($this->policy->like($user, $post));
    }

    // تست: کاربر مسدود شده نمی‌تواند پست را لایک کند
    #[Test]
    public function banned_user_cannot_like_post()
    {
        $user = User::factory()->create(['is_banned' => true, 'is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
        ]);

        $this->assertFalse($this->policy->like($user, $post));
    }

    // تست: کاربر زیر سن نمی‌تواند محتوای حساس را لایک کند
    #[Test]
    public function underage_user_cannot_like_sensitive_content()
    {
        $child = User::factory()->underage()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_sensitive' => true,
            'is_private' => false,
        ]);

        $this->assertFalse($this->policy->like($child, $post));
    }

    // تست: کاربر زیر سن نمی‌تواند محتوای حساس ایجاد کند
    #[Test]
    public function underage_user_cannot_create_sensitive_content()
    {
        $adult = User::factory()->create(['is_private' => false]);
        $child = User::factory()->underage()->create(['is_private' => false]);

        $this->assertTrue($this->policy->createSensitiveContent($adult));
        $this->assertFalse($this->policy->createSensitiveContent($child));
    }

    // تست: کاربر عادی نمی‌تواند پست کاربر مسدود شده را ببیند (مگر ادمین)
    #[Test]
    public function user_cannot_view_posts_of_banned_user_unless_admin()
    {
        $normalUser = User::factory()->create(['is_private' => false]);
        $bannedUser = User::factory()->create(['is_banned' => true, 'is_private' => false]);
        $admin = User::factory()->create(['username' => 'admin', 'is_private' => false]);

        $post = Post::factory()->create([
            'user_id' => $bannedUser->id,
            'is_private' => false,
        ]);

        // بارگذاری مجدد رابطه user برای اطمینان
        $post->load('user');
        $bannedUser->refresh();

        // کاربر عادی نمی‌تواند پست کاربر مسدود شده را ببیند
        $this->assertFalse($this->policy->view($normalUser, $post));

        // ادمین می‌تواند ببیند
        $this->assertTrue($this->policy->view($admin, $post));

        // خود کاربر مسدود شده می‌تواند پست خودش را ببیند
        $this->assertTrue($this->policy->view($bannedUser, $post));
    }

    // تست: صاحب پست همیشه می‌تواند پست‌های خودش را ببیند
    #[Test]
    public function owner_can_always_view_their_own_posts()
    {
        $user = User::factory()->create(['is_private' => false]);

        // پست عمومی
        $publicPost = Post::factory()->create([
            'user_id' => $user->id,
            'is_private' => false,
        ]);

        // پست خصوصی
        $privatePost = Post::factory()->create([
            'user_id' => $user->id,
            'is_private' => true,
        ]);

        // محتوای حساس
        $sensitivePost = Post::factory()->create([
            'user_id' => $user->id,
            'is_sensitive' => true,
            'is_private' => false,
        ]);

        $this->assertTrue($this->policy->view($user, $publicPost));
        $this->assertTrue($this->policy->view($user, $privatePost));
        $this->assertTrue($this->policy->view($user, $sensitivePost));
    }

    // تست: کاربر می‌تواند روی پست عمومی کامنت بگذارد
    #[Test]
    public function user_can_comment_on_public_post()
    {
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
        ]);

        $this->assertTrue($this->policy->comment($user, $post));
    }

    // تست: کاربر مسدود شده نمی‌تواند کامنت بگذارد
    #[Test]
    public function banned_user_cannot_comment()
    {
        $user = User::factory()->create(['is_banned' => true, 'is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
        ]);

        $this->assertFalse($this->policy->comment($user, $post));
    }


    // تست: کاربر نمی‌تواند روی پستی که کامنت‌هایش غیرفعال است، کامنت بگذارد
    #[Test]
    public function user_cannot_comment_on_post_with_disabled_comments()
    {
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);

        // پست با کامنت غیرفعال
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
            'comments_disabled' => true, // کامنت غیرفعال
        ]);

        $this->assertFalse($this->policy->comment($user, $post));
    }

    // تست: کاربر می‌تواند روی پست با کامنت فعال کامنت بگذارد
    #[Test]
    public function user_can_comment_on_post_with_enabled_comments()
    {
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);

        // پست با کامنت فعال (پیش‌فرض)
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
            'comments_disabled' => false, // کامنت فعال
        ]);

        $this->assertTrue($this->policy->comment($user, $post));
    }

    // تست: کاربر نمی‌تواند روی پست حذف شده کامنت بگذارد (حتی اگر کامنت فعال باشد)
    #[Test]
    public function user_cannot_comment_on_deleted_post()
    {
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);

        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
            'comments_disabled' => false,
        ]);

        $post->delete();

        $this->assertFalse($this->policy->comment($user, $post));
    }

    // تست: صاحب پست می‌تواند روی پست خودش کامنت بگذارد (حتی اگر کامنت‌ها غیرفعال باشد)
    #[Test]
    public function owner_can_comment_on_own_post_even_with_disabled_comments()
    {
        $user = User::factory()->create(['is_private' => false]);

        // پست با کامنت غیرفعال
        $post = Post::factory()->create([
            'user_id' => $user->id, // همان کاربر
            'is_private' => false,
            'comments_disabled' => true,
        ]);

        $this->assertTrue($this->policy->comment($user, $post));
    }

    // تست: ادمین می‌تواند روی پست با کامنت غیرفعال کامنت بگذارد
    #[Test]
    public function admin_can_comment_on_post_with_disabled_comments()
    {
        $admin = User::factory()->create(['username' => 'admin', 'is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);

        // پست با کامنت غیرفعال
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
            'comments_disabled' => true,
        ]);

        // ادمین می‌تواند روی پست با کامنت غیرفعال کامنت بگذارد
        $this->assertTrue($this->policy->comment($admin, $post));
    }

    // تست: کاربر عادی نمی‌تواند روی پست با کامنت غیرفعال کامنت بگذارد
    #[Test]
    public function regular_user_cannot_comment_on_post_with_disabled_comments()
    {
        $regularUser = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);

        // پست با کامنت غیرفعال
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
            'comments_disabled' => true,
        ]);

        // کاربر عادی نمی‌تواند روی پست با کامنت غیرفعال کامنت بگذارد
        $this->assertFalse($this->policy->comment($regularUser, $post));
    }

    // تست: کاربر می‌تواند پست عمومی را بازنشر کند
    #[Test]
    public function user_can_repost_public_post()
    {
        $user = User::factory()->create(['is_private' => false]);
        $postOwner = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'is_private' => false,
        ]);

        $this->assertTrue($this->policy->repost($user, $post));
    }

    // تست: کاربر نمی‌تواند پست خودش را بازنشر کند
    #[Test]
    public function user_cannot_repost_own_post()
    {
        $user = User::factory()->create(['is_private' => false]);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_private' => false,
        ]);

        $this->assertFalse($this->policy->repost($user, $post));
    }

    // تست: فقط ادمین می‌تواند پست‌ها را مدیریت کند
    #[Test]
    public function admin_can_manage_posts()
    {
        $admin = User::factory()->create(['username' => 'admin', 'is_private' => false]);
        $normalUser = User::factory()->create(['is_private' => false]);

        $this->assertTrue($this->policy->managePosts($admin));
        $this->assertFalse($this->policy->managePosts($normalUser));
    }
}
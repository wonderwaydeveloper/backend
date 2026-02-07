# 🔐 Authorization System - مستندات کامل

## ✅ وضعیت: 70% عملیاتی

## 📊 خلاصه:

### ✅ پیاده شده:
- **5 Roles**: user, verified, premium, moderator, admin
- **37 Permissions**: تمام مجوزهای مورد نیاز
- **14 Policies**: PostPolicy, CommentPolicy, CommunityPolicy, MomentPolicy, NotificationPolicy, ScheduledPostPolicy, SpacePolicy, UserListPolicy, AuditLogPolicy, ProfilePolicy, MessagePolicy, BookmarkPolicy, FollowPolicy, ReportPolicy
- **2 Middleware**: CheckPermission, CheckRole
- **Seeders**: RoleSeeder, PermissionSeeder

### ⚠️ نیاز به تکمیل:
- استفاده از authorize() در همه Controllers
- استفاده از Middleware در Routes
- چک Permissions در کد
- تست واحد

## 🚀 نحوه استفاده:

### در Controller:
```php
// چک Policy
$this->authorize('update', $post);

// چک Permission
if (!auth()->user()->can('post.create')) {
    abort(403);
}

// چک Role
if (!auth()->user()->hasRole('admin')) {
    abort(403);
}
```

### در Route:
```php
Route::post('/posts', [PostController::class, 'store'])
    ->middleware(['auth:sanctum', 'permission:post.create']);

Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin']);
```

### در Blade:
```php
@can('update', $post)
    <button>Edit</button>
@endcan

@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole
```

## 📦 دستورات:

```bash
# اجرای Seeders
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder

# پاک کردن Cache
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

## 📋 لیست کامل Permissions:

**Posts:** post.create, post.edit.own, post.delete.own, post.delete.any, post.schedule
**Comments:** comment.create, comment.delete.own, comment.delete.any
**Messages:** message.send, message.delete.own
**Profile:** profile.edit.own, profile.view.private
**Follow:** user.follow, user.unfollow
**Interactions:** post.like, post.repost, post.bookmark
**Lists:** list.create, list.edit.own, list.delete.own
**Spaces:** space.create, space.host, space.speak
**Communities:** community.create, community.moderate.own, community.post
**Moderation:** user.ban, user.suspend, content.moderate, report.review, report.create
**Premium:** analytics.view, thread.create.long, media.upload.hd
**Admin:** admin.panel.access, admin.users.manage, admin.settings.manage

## 🎯 امتیاز: 70/100

**تکمیل با بررسی سیستمهای بعدی**

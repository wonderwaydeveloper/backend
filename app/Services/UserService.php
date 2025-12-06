<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * دریافت کاربران
     */
    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        $query = User::withCount(['followers', 'following', 'posts'])
            ->active()
            ->orderBy('created_at', 'desc');

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('username', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['verified']) && $filters['verified']) {
            $query->verified();
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * آپدیت پروفایل
     */
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = [];

            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }

            if (isset($data['username'])) {
                $updateData['username'] = $data['username'];
            }

            if (isset($data['bio'])) {
                $updateData['bio'] = $data['bio'];
            }

            if (isset($data['website'])) {
                $updateData['website'] = $data['website'];
            }

            if (isset($data['location'])) {
                $updateData['location'] = $data['location'];
            }

            if (isset($data['is_private'])) {
                $updateData['is_private'] = $data['is_private'];
            }

            // آپلود آواتار
            if (isset($data['avatar'])) {
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $updateData['avatar'] = $data['avatar']->store('users/avatars', 'public');
            }

            // آپلود کاور
            if (isset($data['cover_image'])) {
                if ($user->cover_image) {
                    Storage::disk('public')->delete($user->cover_image);
                }
                $updateData['cover_image'] = $data['cover_image']->store('users/covers', 'public');
            }

            $user->update($updateData);

            return $user->load('followers', 'following');
        });
    }

    /**
     * دنبال کردن کاربر
     */
    public function followUser(User $follower, User $following): array
    {
        return DB::transaction(function () use ($follower, $following) {
            // بررسی وجود دنبال‌کنی قبلی
            $existingFollow = Follow::where('follower_id', $follower->id)
                ->where('following_id', $following->id)
                ->first();

            if ($existingFollow) {
                throw new \Exception('Already following this user');
            }

            $requiresApproval = $following->is_private;

            $follow = Follow::create([
                'follower_id' => $follower->id,
                'following_id' => $following->id,
                'approved_at' => $requiresApproval ? null : now(),
            ]);

            // آپدیت شمارنده‌ها
            if (!$requiresApproval) {
                $following->increment('followers_count');
                $follower->increment('following_count');
            }

            // ارسال نوتیفیکیشن
            if ($requiresApproval) {
                // درخواست فالو برای حساب خصوصی
                $this->notificationService->sendFollowRequestNotification($following, $follower);
            } else {
                // فالوور جدید برای حساب عمومی
                $this->notificationService->sendNewFollowerNotification($following, $follower);
            }

            return [
                'following' => true,
                'requires_approval' => $requiresApproval,
                'message' => $requiresApproval ?
                    'Follow request sent' :
                    'Successfully followed user',
            ];
        });
    }

    /**
     * آنفالو کاربر
     */
    public function unfollowUser(User $follower, User $following): bool
    {
        return DB::transaction(function () use ($follower, $following) {
            $follow = Follow::where('follower_id', $follower->id)
                ->where('following_id', $following->id)
                ->first();

            if (!$follow) {
                throw new \Exception('Not following this user');
            }

            $wasApproved = $follow->isApproved();

            $follow->delete();

            // آپدیت شمارنده‌ها
            if ($wasApproved) {
                $following->decrement('followers_count');
                $follower->decrement('following_count');
            }

            return true;
        });
    }

    /**
     * دریافت دنبال‌کنندگان
     */
    public function getFollowers(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->followers()
            ->withCount(['followers', 'following', 'posts'])
            ->orderBy('pivot_created_at', 'desc');

        if (isset($filters['approved']) && $filters['approved']) {
            $query->wherePivotNotNull('approved_at');
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * دریافت کاربران دنبال شونده
     */
    public function getFollowing(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->following()
            ->withCount(['followers', 'following', 'posts'])
            ->orderBy('pivot_created_at', 'desc');

        if (isset($filters['approved']) && $filters['approved']) {
            $query->wherePivotNotNull('approved_at');
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * دریافت درخواست‌های دنبال کردن
     */
    public function getFollowRequests(User $user): LengthAwarePaginator
    {
        return $user->followers()
            ->wherePivotNull('approved_at')
            ->withCount(['followers', 'following', 'posts'])
            ->orderBy('pivot_created_at', 'desc')
            ->paginate(20);
    }

    /**
     * قبول درخواست دنبال کردن
     */
    public function acceptFollowRequest(User $user, User $follower): bool
    {
        return DB::transaction(function () use ($user, $follower) {
            $follow = Follow::where('follower_id', $follower->id)
                ->where('following_id', $user->id)
                ->whereNull('approved_at')
                ->firstOrFail();

            $follow->approve();

            // آپدیت شمارنده‌ها
            $user->increment('followers_count');
            $follower->increment('following_count');

            // ارسال نوتیفیکیشن جدید برای فالوور
            $this->notificationService->sendNewFollowerNotification($user, $follower);

            return true;
        });
    }

    /**
     * رد درخواست دنبال کردن
     */
    public function rejectFollowRequest(User $user, User $follower): bool
    {
        $follow = Follow::where('follower_id', $follower->id)
            ->where('following_id', $user->id)
            ->whereNull('approved_at')
            ->firstOrFail();

        return $follow->delete();
    }

    /**
     * مسدود کردن کاربر
     */
    public function banUser(User $user): bool
    {
        $user->update(['is_banned' => true]);
        return true;
    }

    /**
     * آزاد کردن کاربر
     */
    public function unbanUser(User $user): bool
    {
        $user->update(['is_banned' => false]);
        return true;
    }

    /**
     * جستجوی کاربران
     */
    public function searchUsers(string $query, ?User $currentUser = null): LengthAwarePaginator
    {
        $searchQuery = User::withCount(['followers', 'following', 'posts'])
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderByRaw("
                CASE 
                    WHEN username = ? THEN 1
                    WHEN username LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    ELSE 4
                END
            ", [$query, "{$query}%", "{$query}%"]);

        // فیلتر کاربران خصوصی برای کاربران لاگین نکرده
        if (!$currentUser) {
            $searchQuery->where('is_private', false);
        }

        return $searchQuery->paginate(15);
    }
}
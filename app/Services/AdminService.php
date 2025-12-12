<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\UploadLimit;
use App\Models\User;
use App\Models\UserSecurityLog;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class AdminService
{
    /**
     * دریافت آمار کلی پلتفرم
     */
    public function getPlatformStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('last_login_at', '>=', now()->subDays(30))->count();
        $bannedUsers = User::where('is_banned', true)->count();
        $underageUsers = User::where('is_underage', true)->count();

        $totalPosts = Post::count();
        $publishedPosts = Post::whereNotNull('published_at')->count();
        $sensitivePosts = Post::where('is_sensitive', true)->count();

        $todayRegistrations = User::whereDate('created_at', today())->count();
        $todayPosts = Post::whereDate('created_at', today())->count();

        $recentSecurityEvents = UserSecurityLog::where('created_at', '>=', now()->subDays(7))->count();

        return [
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'banned' => $bannedUsers,
                'underage' => $underageUsers,
                'today_registrations' => $todayRegistrations,
            ],
            'content' => [
                'posts_total' => $totalPosts,
                'posts_published' => $publishedPosts,
                'sensitive_posts' => $sensitivePosts,
                'today_posts' => $todayPosts,
            ],
            'security' => [
                'recent_events' => $recentSecurityEvents,
            ],
            'system' => [
                'phone_auth_enabled' => PlatformSetting::isPhoneAuthEnabled(),
                'social_auth_enabled' => true, // Assuming social auth is always enabled
            ],
        ];
    }

    /**
     * دریافت تنظیمات پلتفرم
     */
    public function getPlatformSettings(): array
    {
        $settings = PlatformSetting::all()->groupBy('group');

        return [
            'authentication' => $settings->get('authentication', collect())->pluck('value', 'key'),
            'limits' => $settings->get('limits', collect())->pluck('value', 'key'),
            'general' => $settings->get('general', collect())->pluck('value', 'key'),
            'privacy' => $settings->get('privacy', collect())->pluck('value', 'key'),
        ];
    }

    /**
     * آپدیت تنظیمات پلتفرم
     */
    public function updatePlatformSettings(array $settings): array
    {
        return DB::transaction(function () use ($settings) {
            $updated = [];

            foreach ($settings as $setting) {
                PlatformSetting::setValue($setting['key'], $setting['value']);
                $updated[] = $setting['key'];
            }

            return $updated;
        });
    }

    /**
     * دریافت محدودیت‌های آپلود
     */
    public function getUploadLimits(): array
    {
        return UploadLimit::all()->toArray();
    }

    /**
     * آپدیت محدودیت‌های آپلود
     */
    public function updateUploadLimits(string $type, array $data): UploadLimit
    {
        $limit = UploadLimit::getForType($type);

        $limit->update([
            'max_files' => $data['max_files'] ?? $limit->max_files,
            'max_file_size' => $data['max_file_size'] ?? $limit->max_file_size,
            'allowed_mimes' => $data['allowed_mimes'] ?? $limit->allowed_mimes,
            'max_total_size' => $data['max_total_size'] ?? $limit->max_total_size,
            'is_video_allowed' => $data['is_video_allowed'] ?? $limit->is_video_allowed,
            'max_video_duration' => $data['max_video_duration'] ?? $limit->max_video_duration,
            'max_video_size' => $data['max_video_size'] ?? $limit->max_video_size,
        ]);

        return $limit;
    }

    /**
     * فعال/غیرفعال کردن احراز هویت با موبایل
     */
    public function togglePhoneAuthentication(): bool
    {
        $currentValue = PlatformSetting::getValue('phone_auth_enabled', false);
        $newValue = !$currentValue;

        PlatformSetting::setValue('phone_auth_enabled', $newValue);

        return $newValue;
    }

    /**
     * دریافت کاربران زیر سن
     */
    public function getUnderageUsers(array $filters = []): array
    {
        $query = User::with(['parent'])
            ->where('is_underage', true)
            ->orderBy('created_at', 'desc');

        if (isset($filters['with_parental_controls'])) {
            $query->whereNotNull('parent_id');
        }

        return [
            'users' => $query->paginate($filters['per_page'] ?? 20),
            'stats' => [
                'total_underage' => User::where('is_underage', true)->count(),
                'with_parental_controls' => User::where('is_underage', true)->whereNotNull('parent_id')->count(),
                'without_parental_controls' => User::where('is_underage', true)->whereNull('parent_id')->count(),
            ],
        ];
    }

    /**
     * دریافت گزارش‌های امنیتی
     */
    public function getSecurityReports(array $filters = []): array
    {
        $query = UserSecurityLog::with(['user'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['action'])) {
            $query->where('action', 'like', "%{$filters['action']}%");
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        $logs = $query->paginate($filters['per_page'] ?? 50);

        // آمار کلی
        $stats = [
            'total_events' => UserSecurityLog::count(),
            'login_attempts' => UserSecurityLog::where('action', 'login')->count(),
            'recent_events' => UserSecurityLog::latest()->take(10)->get(),
            'top_actions' => UserSecurityLog::selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];

        return [
            'logs' => $logs,
            'stats' => $stats,
        ];
    }

    /**
     * دریافت لاگ‌های امنیتی کاربر خاص
     */
    public function getUserSecurityLogs(int $userId, array $filters = []): array
    {
        $user = User::findOrFail($userId);

        $query = UserSecurityLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $logs = $query->paginate($filters['per_page'] ?? 20);

        return [
            'user' => $user,
            'logs' => $logs,
            'summary' => [
                'total_events' => UserSecurityLog::where('user_id', $userId)->count(),
                'last_login' => $user->last_login_at,
                'account_created' => $user->created_at,
            ],
        ];
    }

    /**
     * بن کردن کاربر
     */
    public function banUser(User $user): void
    {
        $user->update(['is_banned' => true]);

        // ثبت لاگ امنیتی
        UserSecurityLog::logSecurityEvent($user, 'banned');
    }

    /**
     * رفع بن کردن کاربر
     */
    public function unbanUser(User $user): void
    {
        $user->update(['is_banned' => false]);

        // ثبت لاگ امنیتی
        UserSecurityLog::logSecurityEvent($user, 'unbanned');
    }

    /**
     * ویژه کردن پست
     */
    public function featurePost(Post $post): void
    {
        $post->update(['is_featured' => true]);
    }
}
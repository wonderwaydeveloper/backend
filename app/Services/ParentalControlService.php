<?php

namespace App\Services;

use App\Models\ParentalControl;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParentalControlService
{
    /**
     * ایجاد کنترل والدین
     */

    public function createParentalControl(User $parent, array $data): ParentalControl
    {
        return DB::transaction(function () use ($parent, $data) {
            $child = User::findOrFail($data['child_id']);

            // بررسی اینکه کودک واقعاً زیر سن است
            if (!$child->is_underage) {
                throw new \Exception('User is not underage');
            }

            // بررسی اینکه کودک قبلاً والد دارد یا خیر
            if (!is_null($child->parent_id) && $child->parent_id !== $parent->id) {
                throw new \Exception('Child already has a parent');
            }

            // تنظیم کنترل والدین برای کودک (اگر قبلاً والد نداشت)
            if (is_null($child->parent_id)) {
                $child->update(['parent_id' => $parent->id]);
            }

            // مقدار پیش‌فرض برای restrictions
            $defaultRestrictions = [
                'max_daily_usage' => 120,
                'content_filter' => true,
                'block_explicit_content' => true,
                'block_private_messages' => false,
            ];

            // ادغام restrictions ارسال شده با پیش‌فرض
            $restrictions = array_merge($defaultRestrictions, $data['restrictions'] ?? []);

            // اگر max_daily_usage به صورت جداگانه ارسال شده، آن را به restrictions اضافه کن
            if (isset($data['max_daily_usage'])) {
                $restrictions['max_daily_usage'] = $data['max_daily_usage'];
            }

            $control = ParentalControl::create([
                'parent_id' => $parent->id,
                'child_id' => $child->id,
                'restrictions' => $restrictions,
                'allowed_features' => $data['allowed_features'] ?? ['posts', 'comments', 'likes'],
                'daily_limit_start' => $data['daily_limit_start'] ?? null,
                'daily_limit_end' => $data['daily_limit_end'] ?? null,
                'max_daily_usage' => $restrictions['max_daily_usage'], // از restrictions می‌گیریم
                'is_active' => true,
            ]);

            return $control->load('child');
        });
    }

    /**
     * آپدیت کنترل والدین
     */

    public function updateParentalControl(User $parent, int $childId, array $data): ParentalControl
    {
        $control = ParentalControl::where('parent_id', $parent->id)
            ->where('child_id', $childId)
            ->firstOrFail();

        // اگر restrictions ارسال شده، آن را به‌روز کن
        if (isset($data['restrictions'])) {
            $restrictions = array_merge($control->restrictions ?? [], $data['restrictions']);

            // اگر max_daily_usage در restrictions به‌روز شده، آن را در فیلد جداگانه هم به‌روز کن
            if (isset($data['restrictions']['max_daily_usage'])) {
                $data['max_daily_usage'] = $data['restrictions']['max_daily_usage'];
            }
        } else {
            $restrictions = $control->restrictions;
        }

        // اگر max_daily_usage به صورت جداگانه ارسال شده، آن را به restrictions هم اضافه کن
        if (isset($data['max_daily_usage'])) {
            $restrictions['max_daily_usage'] = $data['max_daily_usage'];
        }

        $control->update([
            'restrictions' => $restrictions,
            'allowed_features' => $data['allowed_features'] ?? $control->allowed_features,
            'daily_limit_start' => $data['daily_limit_start'] ?? $control->daily_limit_start,
            'daily_limit_end' => $data['daily_limit_end'] ?? $control->daily_limit_end,
            'max_daily_usage' => $restrictions['max_daily_usage'] ?? $control->max_daily_usage,
            'is_active' => $data['is_active'] ?? $control->is_active,
        ]);

        return $control->load('child');
    }

    /**
     * دریافت کنترل‌های والدین
     */
    public function getParentalControls(User $parent)
    {
        return ParentalControl::with(['child'])
            ->where('parent_id', $parent->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * حذف کنترل والدین
     */
    public function deleteParentalControl(User $parent, int $childId): bool
    {
        return DB::transaction(function () use ($parent, $childId) {
            $control = ParentalControl::where('parent_id', $parent->id)
                ->where('child_id', $childId)
                ->firstOrFail();

            // حذف parent_id از کاربر کودک
            $child = User::find($childId);
            if ($child) {
                $child->parent_id = null;
                $child->save();
            }

            return $control->delete();
        });
    }

    /**
     * دریافت گزارش استفاده کودک
     */
    public function getUsageReport(int $childId, array $filters = []): array
    {
        $child = User::findOrFail($childId);
        $period = $filters['period'] ?? 'today';

        $report = [
            'child' => [
                'name' => $child->name,
                'username' => $child->username,
                'age' => $child->age,
            ],
            'period' => $period,
            'usage_stats' => $this->calculateUsageStats($child, $period),
            'activity_summary' => $this->getActivitySummary($child, $period),
            'restrictions' => $this->getActiveRestrictions($childId),
        ];

        return $report;
    }

    /**
     * محاسبه آمار استفاده
     */
    private function calculateUsageStats(User $child, string $period): array
    {
        // اینجا می‌توانید لاگ استفاده واقعی را محاسبه کنید
        // برای نمونه داده‌های نمونه برمی‌گردانیم
        return [
            'total_time_minutes' => rand(30, 180),
            'posts_created' => rand(0, 10),
            'comments_made' => rand(0, 20),
            'likes_given' => rand(0, 50),
            'messages_sent' => rand(0, 15),
        ];
    }

    /**
     * خلاصه فعالیت‌ها
     */
    private function getActivitySummary(User $child, string $period): array
    {
        return [
            'most_active_hours' => ['14:00-16:00', '19:00-21:00'],
            'frequent_actions' => ['like', 'comment', 'view'],
            'content_preferences' => ['entertainment', 'education', 'social'],
        ];
    }

    /**
     * دریافت محدودیت‌های فعال
     */
    private function getActiveRestrictions(int $childId): array
    {
        $control = ParentalControl::where('child_id', $childId)
            ->where('is_active', true)
            ->first();

        if (!$control) {
            return [];
        }

        return [
            'time_limits' => [
                'start' => $control->daily_limit_start,
                'end' => $control->daily_limit_end,
                'max_daily_usage' => $control->max_daily_usage,
            ],
            'content_restrictions' => $control->restrictions,
            'allowed_features' => $control->allowed_features,
        ];
    }

    /**
     * بررسی دسترسی کودک به یک ویژگی
     */
    public function canAccessFeature(User $child, string $feature): bool
    {
        $control = ParentalControl::where('child_id', $child->id)
            ->where('is_active', true)
            ->first();

        if (!$control) {
            return true; // اگر کنترل والدین فعال نباشد، دسترسی آزاد است
        }

        // بررسی محدودیت زمانی
        if (!$control->isWithinTimeLimit()) {
            return false;
        }

        // بررسی محدودیت استفاده روزانه
        if ($control->getRemainingUsageToday() <= 0) {
            return false;
        }

        // بررسی ویژگی‌های مجاز
        return $control->isFeatureAllowed($feature);
    }

    /**
     * لاگ کردن فعالیت کودک
     */
    public function logChildActivity(User $child, string $activity, int $duration = 0): void
    {
        // اینجا می‌توانید فعالیت کودک را در دیتابیس لاگ کنید
        // برای نمونه فقط در فایل لاگ می‌نویسیم
        \Log::info("Child activity logged", [
            'child_id' => $child->id,
            'activity' => $activity,
            'duration' => $duration,
            'timestamp' => now(),
        ]);
    }
}
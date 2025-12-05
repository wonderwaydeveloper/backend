<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $user->is_underage = $user->birth_date && $user->birth_date->age < 18;
        $user->saveQuietly(); // ذخیره بدون فعال کردن رویدادها برای جلوگیری از حلقه بی‌نهایت
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // فقط اگر تاریخ تولد تغییر کرده باشد، دوباره محاسبه کن
        if ($user->wasChanged('birth_date')) {
            $user->is_underage = $user->birth_date && $user->birth_date->age < 18;
            $user->saveQuietly();
        }
    }
}
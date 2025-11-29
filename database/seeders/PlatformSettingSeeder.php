<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'phone_auth_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'authentication',
                'description' => 'فعال سازی احراز هویت با شماره موبایل',
            ],
            [
                'key' => 'social_auth_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'authentication',
                'description' => 'فعال سازی احراز هویت اجتماعی (گوگل، فیسبوک و ...)',
            ],
            [
                'key' => 'user_registration_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'authentication',
                'description' => 'فعال سازی ثبت نام کاربران جدید',
            ],
            [
                'key' => 'max_posts_per_day',
                'value' => '50',
                'type' => 'integer',
                'group' => 'limits',
                'description' => 'حداکثر تعداد پست در روز برای هر کاربر',
            ],
            [
                'key' => 'max_follows_per_day',
                'value' => '100',
                'type' => 'integer',
                'group' => 'limits',
                'description' => 'حداکثر تعداد دنبال کردن در روز برای هر کاربر',
            ],
            [
                'key' => 'site_name',
                'value' => 'شبکه اجتماعی ما',
                'type' => 'string',
                'group' => 'general',
                'description' => 'نام سایت',
            ],
            [
                'key' => 'site_description',
                'value' => 'یک شبکه اجتماعی مدرن و پرامکانات',
                'type' => 'string',
                'group' => 'general',
                'description' => 'توضیحات سایت',
            ],
            [
                'key' => 'underage_age_threshold',
                'value' => '18',
                'type' => 'integer',
                'group' => 'privacy',
                'description' => 'سن قانونی برای تشخیص کاربران زیر سن',
            ],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::create($setting);
        }
    }
}
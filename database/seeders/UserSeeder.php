<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ParentalControl;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد کاربر ادمین
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'phone' => '+989111111111',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'is_verified' => true,
            'is_private' => false,
            'birth_date' => '1990-01-01',
            'is_underage' => false,
        ]);

        // ایجاد کاربر معمولی
        $user1 = User::create([
            'name' => 'Test User 1',
            'username' => 'user1',
            'email' => 'user1@example.com',
            'phone' => '+989122222222',
            'password' => Hash::make('password'),
            'email_verified_at' => null, 
            'phone_verified_at' => now(),
            'is_verified' => false,
            'is_private' => false,
            'birth_date' => '1995-05-15',
            'is_underage' => false,
        ]);

        $user2 = User::create([
            'name' => 'Test User 2',
            'username' => 'user2',
            'email' => 'user2@example.com',
            'phone' => '+989133333333',
            'password' => Hash::make('password'),
            'email_verified_at' => null, 
            'phone_verified_at' => now(),
            'is_verified' => false,
            'is_private' => true,
            'birth_date' => '1998-08-20',
            'is_underage' => false,
        ]);

        // ایجاد کاربر زیر سن قانونی
        $childUser = User::create([
            'name' => "Child User",
            'username' => 'childuser',
            'email' => 'child@example.com',
            'phone' => '+989144444444',
            'password' => Hash::make('password'),
            'email_verified_at' => null, 
            'phone_verified_at' => now(),
            'is_verified' => false,
            'is_private' => false,
            'birth_date' => now()->subYears(10)->format('Y-m-d'),
            'is_underage' => true,
        ]);

        // ایجاد کنترل والدین برای کاربر کودک
        ParentalControl::create([
            'parent_id' => $user1->id,
            'child_id' => $childUser->id,
            'restrictions' => [
                'max_daily_usage' => 60,
                'content_filter' => true,
                'block_explicit_content' => true,
            ],
            'allowed_features' => ['posts', 'comments', 'likes'],
            'daily_limit_start' => '08:00:00',
            'daily_limit_end' => '20:00:00',
            'max_daily_usage' => 60, // 1 hour
            'is_active' => true,
        ]);

        // ایجاد چند کاربر دیگر
        User::factory()->count(12)->create();
    }
}
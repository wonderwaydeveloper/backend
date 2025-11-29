<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_underage', false)->get();

        foreach ($users as $user) {
            // ایجاد پست‌های معمولی
            Post::factory()->count(5)->create([
                'user_id' => $user->id,
                'type' => 'post',
            ]);

            // ایجاد برخی پست‌های حساس
            Post::factory()->count(2)->create([
                'user_id' => $user->id,
                'type' => 'post',
                'is_sensitive' => true,
            ]);
        }

        // ایجاد برخی پاسخ‌ها به پست‌ها
        $posts = Post::where('type', 'post')->get()->take(10);

        foreach ($posts as $post) {
            Post::factory()->count(3)->create([
                'user_id' => $users->random()->id,
                'type' => 'reply',
                'parent_id' => $post->id,
            ]);
        }
    }
}
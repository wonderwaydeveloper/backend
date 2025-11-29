<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_underage', false)->get();

        foreach ($users as $user) {
            // ایجاد مقالات منتشر شده
            Article::factory()->count(3)->create([
                'user_id' => $user->id,
                'status' => 'published',
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            // ایجاد مقالات پیش‌نویس
            Article::factory()->count(2)->create([
                'user_id' => $user->id,
                'status' => 'draft',
            ]);

            // ایجاد مقالات زمان‌بندی شده
            Article::factory()->count(1)->create([
                'user_id' => $user->id,
                'status' => 'scheduled',
                'scheduled_at' => now()->addDays(rand(1, 7)),
            ]);
        }

        // تایید برخی مقالات توسط ادمین
        $admin = User::where('username', 'admin')->first();
        $articles = Article::where('status', 'published')->get()->take(5);

        foreach ($articles as $article) {
            $article->update([
                'is_approved' => true,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);
        }
    }
}
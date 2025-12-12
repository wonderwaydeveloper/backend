<?php

namespace Database\Seeders;

use App\Models\Follow;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Bookmark;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Seeder;

class InteractionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $posts = Post::all();
     

        // ایجاد دنبال‌کنندگان
        foreach ($users as $user) {
            $followings = $users->where('id', '!=', $user->id)->random(5);

            foreach ($followings as $following) {
                Follow::create([
                    'follower_id' => $user->id,
                    'following_id' => $following->id,
                    'approved_at' => $following->is_private ? null : now(),
                ]);
            }
        }

        // ایجاد لایک‌ها برای پست‌ها
        foreach ($posts as $post) {
            $likers = $users->random(rand(0, 10));

            foreach ($likers as $liker) {
                Like::create([
                    'user_id' => $liker->id,
                    'likeable_id' => $post->id,
                    'likeable_type' => Post::class,
                ]);
            }

            // آپدیت تعداد لایک‌ها
            $post->update(['like_count' => $post->likes()->count()]);
        }

        // ایجاد کامنت‌ها برای پست‌ها
        foreach ($posts->take(20) as $post) {
            $commenters = $users->random(rand(0, 5));

            foreach ($commenters as $commenter) {
                Comment::create([
                    'user_id' => $commenter->id,
                    'commentable_id' => $post->id,
                    'commentable_type' => Post::class,
                    'content' => 'این یک نظر تستی است.',
                ]);
            }

            // آپدیت تعداد کامنت‌ها
            $post->update(['reply_count' => $post->comments()->count()]);
        }

        // ایجاد بوکمارک‌ها
        foreach ($users as $user) {
            $bookmarkedPosts = $posts->random(3);
           

            foreach ($bookmarkedPosts as $post) {
                Bookmark::create([
                    'user_id' => $user->id,
                    'bookmarkable_id' => $post->id,
                    'bookmarkable_type' => Post::class,
                ]);
            }
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\{Comment, Post, User};
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(10)->get();
        $posts = Post::limit(20)->get();

        foreach ($posts as $post) {
            Comment::factory()->count(rand(0, 5))->create([
                'post_id' => $post->id,
                'user_id' => $users->random()->id,
            ]);
        }
    }
}

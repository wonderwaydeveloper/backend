<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'is_draft' => false,
            'published_at' => now(),
            'likes_count' => 0,
            'comments_count' => 0,
            'reply_settings' => 'everyone',
            'community_id' => null,
            'is_pinned' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_draft' => true,
            'published_at' => null,
        ]);
    }
}

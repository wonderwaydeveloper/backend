<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->realText(200),
            'type' => 'post',
            'is_sensitive' => fake()->boolean(10),
            'is_edited' => fake()->boolean(20),
            'is_featured' => fake()->boolean(5),
            'is_private' => false, // Default to public
            'comments_disabled' => false,
            'published_at' => now(),
            'like_count' => 0,
            'reply_count' => 0,
            'repost_count' => 0,
            'view_count' => fake()->numberBetween(0, 1000),
        ];
    }

    public function sensitive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_sensitive' => true,
        ]);
    }

    public function edited(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_edited' => true,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn(array $attributes) => [
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'published_at' => null,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_private' => true,
        ]);
    }

    public function withDisabledComments(): static
    {
        return $this->state(fn(array $attributes) => [
            'comments_disabled' => true,
        ]);
    }
}
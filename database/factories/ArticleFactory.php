<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence();
        
        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->text(1000),
            'featured_image' => null,
            'status' => 'published',
            'is_featured' => fake()->boolean(10),
            'is_approved' => false,
            'published_at' => now(),
            'view_count' => fake()->numberBetween(0, 5000),
            'like_count' => 0,
            'comment_count' => 0,
            'share_count' => 0,
            'reading_time' => fake()->numberBetween(1, 10) . ' min',
            'tags' => fake()->words(5),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+1 week'),
            'published_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }
}
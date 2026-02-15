<?php

namespace Database\Factories\Monetization\Models;

use App\Models\User;
use App\Monetization\Models\CreatorFund;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreatorFundFactory extends Factory
{
    protected $model = CreatorFund::class;

    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2024, 2026),
            'total_views' => fake()->numberBetween(10000, 1000000),
            'total_engagement' => fake()->numberBetween(1000, 100000),
            'quality_score' => fake()->randomFloat(2, 70, 100),
            'earnings' => fake()->randomFloat(2, 100, 10000),
            'status' => 'pending',
            'metrics' => [
                'posts_count' => fake()->numberBetween(10, 100),
                'avg_engagement_rate' => fake()->randomFloat(2, 1, 10),
                'follower_growth' => fake()->numberBetween(0, 1000),
            ],
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}

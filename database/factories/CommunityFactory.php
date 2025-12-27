<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommunityFactory extends Factory
{
    protected $model = Community::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->paragraph(),
            'slug' => fake()->unique()->slug(),
            'privacy' => fake()->randomElement(['public', 'private', 'restricted']),
            'created_by' => User::factory(),
            'member_count' => fake()->numberBetween(1, 1000),
            'post_count' => fake()->numberBetween(0, 500),
            'is_verified' => fake()->boolean(10),
            'rules' => fake()->boolean(70) ? [
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ] : null,
            'settings' => [
                'allow_posts' => true,
                'require_approval' => fake()->boolean(30),
                'auto_approve_members' => fake()->boolean(60),
            ],
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'public',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'private',
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
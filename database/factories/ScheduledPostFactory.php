<?php

namespace Database\Factories;

use App\Models\{ScheduledPost, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduledPostFactory extends Factory
{
    protected $model = ScheduledPost::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
            'status' => 'pending',
        ];
    }
}

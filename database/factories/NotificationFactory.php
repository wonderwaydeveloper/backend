<?php

namespace Database\Factories;

use App\Models\{Notification, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'from_user_id' => User::factory(),
            'type' => $this->faker->randomElement(['like', 'comment', 'follow', 'mention', 'repost']),
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => User::factory(),
            'data' => [
                'user_name' => $this->faker->name,
                'message' => $this->faker->sentence
            ],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}

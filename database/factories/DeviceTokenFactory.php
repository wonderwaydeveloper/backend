<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => 'device_' . $this->faker->sha256,
            'device_type' => $this->faker->randomElement(['ios', 'android', 'web']),
            'device_name' => $this->faker->randomElement(['iPhone 14', 'Samsung Galaxy', 'Chrome Browser', 'Firefox']),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'os' => $this->faker->randomElement(['iOS', 'Android', 'Windows', 'macOS', 'Linux']),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'fingerprint' => $this->faker->sha256,
            'is_trusted' => $this->faker->boolean(70),
            'active' => true,
            'last_used_at' => now(),
        ];
    }

    public function trusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trusted' => true,
        ]);
    }

    public function untrusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_trusted' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
            'last_used_at' => now()->subDays(35),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\{AuditLog, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'auth.login', 'auth.logout', 'post.create', 'post.delete',
                'user.profile_update', 'device.register', 'device.trust'
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'data' => [],
            'timestamp' => now(),
            'session_id' => $this->faker->uuid(),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high']),
        ];
    }
}

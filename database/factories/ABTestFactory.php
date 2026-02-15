<?php

namespace Database\Factories;

use App\Models\ABTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ABTestFactory extends Factory
{
    protected $model = ABTest::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'status' => 'draft',
            'traffic_percentage' => 50,
            'variants' => [
                'A' => ['name' => 'Control', 'description' => 'Original version'],
                'B' => ['name' => 'Variant', 'description' => 'New version'],
            ],
            'targeting_rules' => null,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'starts_at' => now()->subHour(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'starts_at' => now()->subDays(7),
            'ends_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\PremiumSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PremiumSubscriptionFactory extends Factory
{
    protected $model = PremiumSubscription::class;

    public function definition(): array
    {
        $startsAt = now();
        $billingCycle = fake()->randomElement(['monthly', 'yearly']);
        $endsAt = $billingCycle === 'monthly' ? $startsAt->copy()->addMonth() : $startsAt->copy()->addYear();

        return [
            'user_id' => User::factory(),
            'plan' => fake()->randomElement(['basic', 'premium', 'enterprise']),
            'price' => fake()->randomElement([4.99, 9.99, 19.99]),
            'billing_cycle' => $billingCycle,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'active',
            'payment_method' => fake()->randomElement(['credit_card', 'paypal', 'stripe']),
            'transaction_id' => fake()->uuid(),
            'features' => ['ad_free', 'hd_video', 'priority_support'],
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'ends_at' => now()->subDay(),
        ]);
    }
}

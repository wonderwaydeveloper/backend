<?php

namespace App\Monetization\Services;

use App\Models\PremiumSubscription;
use App\Models\User;

class PremiumService
{
    public function subscribe(User $user, array $data): PremiumSubscription
    {
        $startsAt = now();
        $endsAt = $data['billing_cycle'] === 'monthly' 
            ? $startsAt->copy()->addMonth() 
            : $startsAt->copy()->addYear();

        return PremiumSubscription::create([
            'user_id' => $user->id,
            'plan' => $data['plan'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'active',
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'],
            'features' => $this->getPlanFeatures($data['plan']),
        ]);
    }

    public function cancel(PremiumSubscription $subscription): void
    {
        $subscription->cancel();
    }

    public function getStatus(User $user): ?PremiumSubscription
    {
        return $user->premiumSubscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
    }

    public function getPlans(): array
    {
        return [
            [
                'id' => 'basic',
                'name' => 'Basic Premium',
                'price' => 4.99,
                'features' => ['ad_free', 'hd_video'],
            ],
            [
                'id' => 'premium',
                'name' => 'Premium',
                'price' => 9.99,
                'features' => ['ad_free', 'hd_video', 'priority_support', 'analytics'],
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 19.99,
                'features' => ['ad_free', 'hd_video', 'priority_support', 'analytics', 'api_access'],
            ],
        ];
    }

    private function getPlanFeatures(string $plan): array
    {
        return match ($plan) {
            'basic' => ['ad_free', 'hd_video'],
            'premium' => ['ad_free', 'hd_video', 'priority_support', 'analytics'],
            'enterprise' => ['ad_free', 'hd_video', 'priority_support', 'analytics', 'api_access'],
            default => [],
        };
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function plans()
    {
        return response()->json(Subscription::plans());
    }

    public function current(Request $request)
    {
        $subscription = $request->user()->activeSubscription;

        if (! $subscription) {
            return response()->json([
                'plan' => 'basic',
                'is_active' => false,
            ]);
        }

        return response()->json($subscription);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:premium,creator',
        ]);

        $user = $request->user();
        $plans = Subscription::plans();
        $plan = $plans[$request->plan];

        // Payment should be processed here
        // For MVP, we just activate the subscription

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan' => $request->plan,
            'status' => 'active',
            'amount' => $plan['price'],
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $user->update([
            'subscription_plan' => $request->plan,
            'is_premium' => true,
        ]);

        return response()->json([
            'message' => 'Subscription activated successfully',
            'subscription' => $subscription,
        ], 201);
    }

    public function cancel(Request $request)
    {
        $subscription = $request->user()->activeSubscription;

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }

        $subscription->cancel();

        $request->user()->update([
            'subscription_plan' => 'basic',
            'is_premium' => false,
        ]);

        return response()->json(['message' => 'Subscription cancelled']);
    }

    public function history(Request $request)
    {
        $subscriptions = $request->user()
            ->subscriptions()
            ->latest()
            ->paginate(20);

        return response()->json($subscriptions);
    }
}

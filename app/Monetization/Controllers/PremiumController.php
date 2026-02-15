<?php

namespace App\Monetization\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PremiumSubscriptionRequest;
use App\Http\Resources\PremiumResource;
use App\Models\PremiumSubscription;
use App\Monetization\Services\PremiumService;
use Illuminate\Http\JsonResponse;

class PremiumController extends Controller
{
    public function __construct(
        private PremiumService $premiumService
    ) {
    }

    public function getPlans(): JsonResponse
    {
        $plans = $this->premiumService->getPlans();

        return response()->json(['plans' => $plans]);
    }

    public function subscribe(PremiumSubscriptionRequest $request): JsonResponse
    {
        $this->authorize('create', PremiumSubscription::class);

        $subscription = $this->premiumService->subscribe(
            auth()->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Subscription successful',
            'data' => new PremiumResource($subscription),
        ], 201);
    }

    public function cancel(PremiumSubscription $subscription): JsonResponse
    {
        $this->authorize('cancel', $subscription);

        $this->premiumService->cancel($subscription);

        return response()->json(['message' => 'Subscription cancelled successfully']);
    }

    public function getStatus(): JsonResponse
    {
        $this->authorize('viewAny', PremiumSubscription::class);

        $subscription = $this->premiumService->getStatus(auth()->user());

        return response()->json([
            'data' => $subscription ? new PremiumResource($subscription) : null,
        ]);
    }
}
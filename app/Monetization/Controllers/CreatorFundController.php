<?php

namespace App\Monetization\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorFundRequest;
use App\Http\Resources\CreatorFundResource;
use App\Monetization\Models\CreatorFund;
use App\Monetization\Services\CreatorFundService;
use Illuminate\Http\JsonResponse;

class CreatorFundController extends Controller
{
    public function __construct(
        private CreatorFundService $creatorFundService
    ) {
    }

    public function getAnalytics(): JsonResponse
    {
        $this->authorize('viewAny', CreatorFund::class);

        $analytics = $this->creatorFundService->getCreatorAnalytics(auth()->user());

        return response()->json(['data' => $analytics]);
    }

    public function calculateEarnings(CreatorFundRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CreatorFund::class);

        $validated = $request->validated();

        $earnings = $this->creatorFundService->calculateMonthlyEarnings(
            auth()->user(),
            (int) $validated['month'],
            (int) $validated['year']
        );

        return response()->json([
            'message' => 'Earnings calculated successfully',
            'data' => [
                'month' => $validated['month'],
                'year' => $validated['year'],
                'earnings' => $earnings,
            ],
        ]);
    }

    public function getEarningsHistory(): JsonResponse
    {
        $this->authorize('viewAny', CreatorFund::class);

        $history = auth()->user()->creatorFunds()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return response()->json(['data' => CreatorFundResource::collection($history)]);
    }

    public function requestPayout(CreatorFundRequest $request): JsonResponse
    {
        $this->authorize('requestPayout', CreatorFund::class);

        $validated = $request->validated();
        $user = auth()->user();

        $user->update([
            'payout_method' => $validated['payout_method'],
            'payout_details' => [
                'bank_details' => $validated['bank_details'] ?? null,
                'paypal_email' => $validated['paypal_email'] ?? null,
                'crypto_wallet' => $validated['crypto_wallet'] ?? null,
            ],
        ]);

        return response()->json(['message' => 'Payout request submitted successfully']);
    }
}

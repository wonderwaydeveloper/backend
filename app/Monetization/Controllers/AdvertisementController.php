<?php

namespace App\Monetization\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdvertisementRequest;
use App\Http\Resources\AdvertisementResource;
use App\Monetization\Models\Advertisement;
use App\Monetization\Services\AdvertisementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function __construct(
        private AdvertisementService $advertisementService
    ) {
    }

    public function create(AdvertisementRequest $request): JsonResponse
    {
        $this->authorize('create', Advertisement::class);

        $ad = $this->advertisementService->createAdvertisement([
            'advertiser_id' => auth()->id(),
            ...$request->validated(),
        ]);

        return response()->json([
            'message' => 'Advertisement created successfully',
            'data' => new AdvertisementResource($ad),
        ], 201);
    }

    public function getTargetedAds(Request $request): JsonResponse
    {
        $ads = $this->advertisementService->getTargetedAds(
            auth()->user(),
            $request->get('limit', 3)
        );

        foreach ($ads as $ad) {
            $this->advertisementService->recordImpression($ad);
        }

        return response()->json([
            'data' => AdvertisementResource::collection($ads),
        ]);
    }

    public function recordClick(Request $request, Advertisement $ad): JsonResponse
    {
        $this->advertisementService->recordClick($ad);

        return response()->json(['message' => 'Click recorded']);
    }

    public function getAnalytics(): JsonResponse
    {
        $this->authorize('viewAny', Advertisement::class);

        $analytics = $this->advertisementService->getAdvertiserAnalytics(auth()->id());

        return response()->json(['data' => $analytics]);
    }

    public function pause(Advertisement $ad): JsonResponse
    {
        $this->authorize('manage', $ad);

        $this->advertisementService->pauseAdvertisement($ad->id);

        return response()->json(['message' => 'Advertisement paused']);
    }

    public function resume(Advertisement $ad): JsonResponse
    {
        $this->authorize('manage', $ad);

        $this->advertisementService->resumeAdvertisement($ad->id);

        return response()->json(['message' => 'Advertisement resumed']);
    }
}

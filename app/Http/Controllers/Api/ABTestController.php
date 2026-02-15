<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ABTestRequest;
use App\Http\Resources\ABTestResource;
use App\Models\ABTest;
use App\Services\ABTestingService;
use Illuminate\Http\{JsonResponse, Request};

class ABTestController extends Controller
{
    public function __construct(
        private ABTestingService $abTestingService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ABTest::class);
        
        $tests = ABTest::select(['id', 'name', 'description', 'status', 'traffic_percentage', 'starts_at', 'ends_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(ABTestResource::collection($tests));
    }

    public function store(ABTestRequest $request): JsonResponse
    {
        $this->authorize('create', ABTest::class);
        
        $test = $this->abTestingService->createTest($request->validated());

        return response()->json([
            'message' => 'A/B test created successfully',
            'data' => new ABTestResource($test),
        ], 201);
    }

    public function show(ABTest $test): JsonResponse
    {
        $this->authorize('view', $test);
        
        $results = $this->abTestingService->getTestResults($test);

        return response()->json($results);
    }

    public function start(ABTest $test): JsonResponse
    {
        $this->authorize('manage', $test);
        
        $this->abTestingService->startTest($test);

        return response()->json(['message' => 'Test started successfully']);
    }

    public function stop(ABTest $test): JsonResponse
    {
        $this->authorize('manage', $test);
        
        $this->abTestingService->stopTest($test);

        return response()->json(['message' => 'Test stopped successfully']);
    }

    public function assign(Request $request): JsonResponse
    {
        $request->validate(['test_name' => 'required|string']);

        $variant = $this->abTestingService->assignUserToTest(
            $request->test_name,
            $request->user()
        );

        return response()->json([
            'variant' => $variant,
            'in_test' => $variant !== null,
        ]);
    }

    public function track(Request $request): JsonResponse
    {
        $request->validate([
            'test_name' => 'required|string',
            'event_type' => 'required|string|in:view,click,conversion,signup,purchase',
            'event_data' => 'nullable|array',
        ]);

        $tracked = $this->abTestingService->trackEvent(
            $request->test_name,
            $request->user(),
            $request->event_type,
            $request->event_data
        );

        return response()->json([
            'tracked' => $tracked,
            'message' => $tracked ? 'Event tracked' : 'User not in test',
        ]);
    }
}

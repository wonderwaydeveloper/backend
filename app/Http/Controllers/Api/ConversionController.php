<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConversionTrackRequest;
use App\Services\ConversionTrackingService;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    public function __construct(private ConversionTrackingService $conversionService)
    {
    }

    public function track(ConversionTrackRequest $request)
    {
        $validated = $request->validated();

        $this->conversionService->track(
            $validated['event_type'],
            auth()->id(),
            $validated['event_data'] ?? $validated['properties'] ?? [],
            $validated['conversion_value'] ?? $validated['value'] ?? 0,
            $validated['currency'] ?? 'USD',
            $validated['source'] ?? null,
            $validated['campaign'] ?? null
        );

        return response()->json(['message' => 'Event tracked successfully']);
    }

    public function funnel(Request $request)
    {
        $dateRange = $request->integer('days', 7);
        $funnel = $this->conversionService->getConversionFunnel($dateRange);

        return response()->json($funnel);
    }

    public function bySource(Request $request)
    {
        $dateRange = $request->integer('days', 30);
        $conversions = $this->conversionService->getConversionsBySource($dateRange);

        return response()->json($conversions);
    }

    public function userJourney(Request $request)
    {
        $userId = $request->integer('user_id') ?? auth()->id();
        $journey = $this->conversionService->getUserJourney($userId);

        return response()->json($journey);
    }

    public function cohortAnalysis(Request $request)
    {
        $period = $request->string('period', 'weekly');
        $cohorts = $this->conversionService->getCohortAnalysis($period);

        return response()->json($cohorts);
    }
}

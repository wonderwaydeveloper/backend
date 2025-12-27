<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AutoScalingRequest;
use App\Services\AutoScalingService;
use Illuminate\Http\Request;

class AutoScalingController extends Controller
{
    public function __construct(private AutoScalingService $autoScalingService)
    {
    }

    public function status()
    {
        $status = $this->autoScalingService->checkAndScale();

        return response()->json($status);
    }

    public function metrics()
    {
        $metrics = $this->autoScalingService->getCurrentMetrics();

        return response()->json($metrics);
    }

    public function history(Request $request)
    {
        $days = $request->integer('days', 7);
        $history = $this->autoScalingService->getScalingHistory($days);

        return response()->json($history);
    }

    public function predict(Request $request)
    {
        $hours = $request->integer('hours', 24);
        $prediction = $this->autoScalingService->predictLoad($hours);

        return response()->json($prediction);
    }

    public function forceScale(AutoScalingRequest $request)
    {
        $validated = $request->validated();

        $recommendations = [[
            'type' => $validated['action'],
            'reason' => $validated['reason'] ?? 'Manual scaling',
            'priority' => 'manual',
            'instances' => $validated['instances'] ?? 1,
            'action' => match ($validated['action']) {
                'scale_up' => 'increase_workers',
                'scale_down' => 'decrease_workers',
            },
        ]];

        $this->autoScalingService->executeScalingActions($recommendations);

        return response()->json(['message' => 'Scaling action executed successfully']);
    }
}

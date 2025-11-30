<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenericResource;
use App\Models\User;
use App\Services\ParentalControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ParentalControlController extends Controller
{

    use AuthorizesRequests;


    public function __construct(private ParentalControlService $parentalControlService)
    {
    }

    /**
     * افزودن کنترل والدین
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|exists:users,id',
            'restrictions' => 'sometimes|array',
            'allowed_features' => 'sometimes|array',
            'daily_limit_start' => 'sometimes|date_format:H:i',
            'daily_limit_end' => 'sometimes|date_format:H:i|after:daily_limit_start',
            'max_daily_usage' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('manageParentalControls', [User::class, User::find($request->child_id)]);

            $control = $this->parentalControlService->createParentalControl(
                $request->user(),
                $request->all()
            );

            return GenericResource::success($control, 'Parental control created successfully', 201);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * آپدیت کنترل والدین
     */
    public function update(Request $request, $childId)
    {
        $validator = Validator::make($request->all(), [
            'restrictions' => 'sometimes|array',
            'allowed_features' => 'sometimes|array',
            'daily_limit_start' => 'sometimes|date_format:H:i',
            'daily_limit_end' => 'sometimes|date_format:H:i|after:daily_limit_start',
            'max_daily_usage' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('manageParentalControls', [User::class, User::find($childId)]);

            $control = $this->parentalControlService->updateParentalControl(
                $request->user(),
                $childId,
                $request->all()
            );

            return GenericResource::success($control, 'Parental control updated successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت کنترل‌های والدین
     */
    public function index(Request $request)
    {
        try {
            $controls = $this->parentalControlService->getParentalControls($request->user());

            return GenericResource::success($controls, 'Parental controls retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * حذف کنترل والدین
     */
    public function destroy(Request $request, $childId)
    {
        try {
            $this->authorize('manageParentalControls', [User::class, User::find($childId)]);

            $deleted = $this->parentalControlService->deleteParentalControl(
                $request->user(),
                $childId
            );

            return GenericResource::success([
                'deleted' => $deleted,
            ], 'Parental control deleted successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت گزارش استفاده کودک
     */
    public function usageReport(Request $request, $childId)
    {
        try {
            $this->authorize('manageParentalControls', [User::class, User::find($childId)]);

            $report = $this->parentalControlService->getUsageReport($childId, $request->all());

            return GenericResource::success($report, 'Usage report retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }
}
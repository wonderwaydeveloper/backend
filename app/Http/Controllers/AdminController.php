<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenericResource;
use App\Models\PlatformSetting;
use App\Models\UploadLimit;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function __construct(private AdminService $adminService) {}

    /**
     * آمار کلی پلتفرم
     */
    public function stats(Request $request)
    {
        try {
            $this->authorize('manageUsers', User::class);

            $stats = $this->adminService->getPlatformStats();

            return GenericResource::success($stats, 'Platform stats retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * دریافت تنظیمات پلتفرم
     */
    public function getSettings(Request $request)
    {
        try {
            $this->authorize('manageUsers', User::class);

            $settings = $this->adminService->getPlatformSettings();

            return GenericResource::success($settings, 'Settings retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * آپدیت تنظیمات پلتفرم
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('manageUsers', User::class);

            $updated = $this->adminService->updatePlatformSettings($request->settings);

            return GenericResource::success($updated, 'Settings updated successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت محدودیت‌های آپلود
     */
    public function getUploadLimits(Request $request)
    {
        try {
            $this->authorize('manageUsers', User::class);

            $limits = UploadLimit::all();

            return GenericResource::success($limits, 'Upload limits retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * آپدیت محدودیت‌های آپلود
     */
    public function updateUploadLimits(Request $request, $type)
    {
        $validator = Validator::make($request->all(), [
            'max_files' => 'sometimes|integer|min:1',
            'max_file_size' => 'sometimes|integer|min:1',
            'allowed_mimes' => 'sometimes|array',
            'max_total_size' => 'sometimes|integer|min:1',
            'is_video_allowed' => 'sometimes|boolean',
            'max_video_duration' => 'sometimes|integer|min:1',
            'max_video_size' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('manageUsers', User::class);

            $limit = $this->adminService->updateUploadLimits($type, $request->all());

            return GenericResource::success($limit, 'Upload limits updated successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * فعال/غیرفعال کردن احراز هویت با موبایل
     */
    public function togglePhoneAuth(Request $request)
    {
        try {
            $this->authorize('manageUsers', User::class);

            $enabled = $this->adminService->togglePhoneAuthentication();

            return GenericResource::success([
                'enabled' => $enabled,
            ], $enabled ? 'Phone authentication enabled' : 'Phone authentication disabled');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * مدیریت کاربران زیر سن
     */
    public function underageUsers(Request $request)
    {
        try {
            $this->authorize('viewUnderageUsers', User::class);

            $users = $this->adminService->getUnderageUsers($request->all());

            return GenericResource::success($users, 'Underage users retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * گزارش‌های امنیتی
     */
    public function securityReports(Request $request)
    {
        try {
            $this->authorize('manageUsers', User::class);

            $reports = $this->adminService->getSecurityReports($request->all());

            return GenericResource::success($reports, 'Security reports retrieved successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }
}
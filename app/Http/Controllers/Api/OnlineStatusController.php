<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStatusRequest;
use App\Http\Resources\OnlineUserResource;
use App\Services\RealtimeService;
use Illuminate\Http\Request;

class OnlineStatusController extends Controller
{
    public function __construct(
        private RealtimeService $realtimeService
    ) {}

    public function updateStatus(UpdateStatusRequest $request)
    {
        $result = $this->realtimeService->updateUserStatus(
            $request->user(),
            $request->status
        );

        return response()->json($result);
    }

    public function getOnlineUsers()
    {
        $users = $this->realtimeService->getOnlineUsers();

        return response()->json(['data' => $users]);
    }

    public function getUserStatus(Request $request, int $userId)
    {
        $status = $this->realtimeService->getUserStatus($userId);

        return response()->json($status);
    }
}

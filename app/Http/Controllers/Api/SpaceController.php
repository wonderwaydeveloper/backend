<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpaceRequest;
use App\Http\Resources\SpaceResource;
use App\Models\Space;
use App\Models\SpaceParticipant;
use App\Services\{SpaceService, SpaceParticipantService};
use App\Contracts\Repositories\SpaceRepositoryInterface;
use Illuminate\Http\Request;

class SpaceController extends Controller
{
    public function __construct(
        private SpaceService $spaceService,
        private SpaceParticipantService $participantService,
        private SpaceRepositoryInterface $spaceRepository
    ) {}

    public function index(Request $request)
    {
        try {
            $spaces = $this->spaceRepository->getLiveSpaces(20);
            return SpaceResource::collection($spaces);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch spaces'], 500);
        }
    }

    public function store(SpaceRequest $request)
    {
        try {
            $space = $this->spaceService->createSpace($request->user(), $request->validated());
            return new SpaceResource($space);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(Space $space)
    {
        try {
            $space = $this->spaceRepository->findById($space->id);
            return new SpaceResource($space);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Space not found'], 404);
        }
    }

    public function join(Request $request, Space $space)
    {
        try {
            $this->spaceService->joinSpace($space, $request->user());
            return response()->json(['message' => 'Joined space successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function leave(Request $request, Space $space)
    {
        try {
            $this->spaceService->leaveSpace($space, $request->user());
            return response()->json(['message' => 'Left space successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function updateRole(Request $request, Space $space, SpaceParticipant $participant)
    {
        $this->authorize('update', $space);

        $request->validate([
            'role' => 'required|in:co_host,speaker,listener',
        ]);

        try {
            if ($participant->space_id !== $space->id) {
                return response()->json(['message' => 'Participant not in this space'], 400);
            }

            $this->participantService->updateRole($participant, $request->role);
            return response()->json(['message' => 'Role updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function end(Request $request, Space $space)
    {
        $this->authorize('update', $space);

        try {
            $this->spaceService->endSpace($space);
            return response()->json(['message' => 'Space ended successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

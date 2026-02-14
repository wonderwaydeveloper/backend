<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Resources\ListResource;
use App\Models\User;
use App\Models\UserList;
use App\Services\{ListService, ListMemberService};
use App\Contracts\Repositories\ListRepositoryInterface;
use Illuminate\Http\Request;

class ListController extends Controller
{
    public function __construct(
        private ListService $listService,
        private ListMemberService $memberService,
        private ListRepositoryInterface $listRepository
    ) {}

    public function index(Request $request)
    {
        try {
            $lists = $this->listRepository->getUserLists($request->user()->id, 20);
            return ListResource::collection($lists);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch lists'], 500);
        }
    }

    public function store(ListRequest $request)
    {
        try {
            $list = $this->listService->createList($request->user(), $request->validated());
            return response()->json(['data' => new ListResource($list)], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(Request $request, UserList $list)
    {
        try {
            if (!$this->listService->canView($list, $request->user())) {
                return response()->json(['message' => 'List not found'], 404);
            }

            $list = $this->listRepository->findById($list->id);
            return new ListResource($list);
        } catch (\Exception $e) {
            return response()->json(['message' => 'List not found'], 404);
        }
    }

    public function update(ListRequest $request, UserList $list)
    {
        $this->authorize('update', $list);

        try {
            $list = $this->listService->updateList($list, $request->validated());
            return new ListResource($list);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy(UserList $list)
    {
        $this->authorize('delete', $list);

        try {
            $this->listService->deleteList($list);
            return response()->json(['message' => 'List deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function addMember(Request $request, UserList $list)
    {
        $this->authorize('update', $list);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $result = $this->memberService->addMember($list, $request->user(), $request->user_id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function removeMember(UserList $list, User $user)
    {
        $this->authorize('update', $list);

        try {
            $this->memberService->removeMember($list, $user->id);
            return response()->json(['message' => 'Member removed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function subscribe(Request $request, UserList $list)
    {
        try {
            $result = $this->listService->subscribe($list, $request->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
    }

    public function unsubscribe(Request $request, UserList $list)
    {
        try {
            $this->listService->unsubscribe($list, $request->user());
            return response()->json(['message' => 'Unsubscribed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function posts(Request $request, UserList $list)
    {
        try {
            if (!$this->listService->canView($list, $request->user())) {
                return response()->json(['message' => 'List not found'], 404);
            }

            $posts = $list->posts()
                ->with(['user:id,name,username,avatar', 'hashtags:id,name,slug'])
                ->withCount(['likes', 'comments', 'quotes'])
                ->paginate(20);

            return response()->json($posts);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch posts'], 500);
        }
    }

    public function discover(Request $request)
    {
        try {
            $lists = $this->listRepository->getPublicLists(20);
            return ListResource::collection($lists);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch lists'], 500);
        }
    }
}

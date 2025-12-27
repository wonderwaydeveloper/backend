<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommunityRequest;
use App\Http\Requests\UpdateCommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\PostResource;
use App\Models\Community;
use App\Models\CommunityJoinRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $communities = Community::query()
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->privacy, function($query, $privacy) {
                $query->where('privacy', $privacy);
            })
            ->when($request->verified, function($query) {
                $query->verified();
            })
            ->withCount('members', 'posts')
            ->orderBy('member_count', 'desc')
            ->paginate(20);

        return CommunityResource::collection($communities);
    }

    public function store(StoreCommunityRequest $request): JsonResponse
    {
        $community = Community::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'slug' => Str::slug($request->name),
        ]);

        // Add creator as owner
        $community->members()->attach(auth()->id(), [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $community->increment('member_count');

        return response()->json([
            'message' => 'Community created successfully',
            'community' => new CommunityResource($community->load('creator')),
        ], 201);
    }

    public function show(Community $community)
    {
        $community->load(['creator', 'members' => function($query) {
            $query->limit(10);
        }])->loadCount('members', 'posts');

        return new CommunityResource($community);
    }

    public function update(UpdateCommunityRequest $request, Community $community): JsonResponse
    {
        $this->authorize('update', $community);

        $community->update($request->validated());

        return response()->json([
            'message' => 'Community updated successfully',
            'community' => new CommunityResource($community),
        ]);
    }

    public function destroy(Community $community): JsonResponse
    {
        $this->authorize('delete', $community);

        $community->delete();

        return response()->json(['message' => 'Community deleted successfully']);
    }

    public function join(Community $community): JsonResponse
    {
        $user = auth()->user();

        if (!$community->canUserJoin($user)) {
            return response()->json(['message' => 'Already a member'], 400);
        }

        if ($community->privacy === 'private') {
            // Check if request already exists
            $existingRequest = CommunityJoinRequest::where([
                'community_id' => $community->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ])->first();

            if ($existingRequest) {
                return response()->json(['message' => 'Join request already sent'], 400);
            }

            CommunityJoinRequest::create([
                'community_id' => $community->id,
                'user_id' => $user->id,
            ]);

            return response()->json(['message' => 'Join request sent']);
        }

        $community->members()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $community->increment('member_count');

        return response()->json(['message' => 'Joined successfully']);
    }

    public function leave(Community $community): JsonResponse
    {
        $user = auth()->user();
        $role = $community->getUserRole($user);

        if (!$role) {
            return response()->json(['message' => 'Not a member'], 400);
        }

        if ($role === 'owner') {
            return response()->json(['message' => 'Owner cannot leave community'], 400);
        }

        $community->members()->detach($user->id);
        $community->decrement('member_count');

        return response()->json(['message' => 'Left community successfully']);
    }

    public function posts(Community $community, Request $request)
    {
        $posts = $community->posts()
            ->with(['user', 'community'])
            ->withCount('likes', 'comments')
            ->when($request->pinned, function($query) {
                $query->pinned();
            })
            ->latest()
            ->paginate(20);

        return PostResource::collection($posts);
    }

    public function members(Community $community, Request $request)
    {
        $members = $community->members()
            ->when($request->role, function($query, $role) {
                $query->wherePivot('role', $role);
            })
            ->paginate(20);

        return response()->json($members);
    }

    public function joinRequests(Community $community)
    {
        $this->authorize('moderate', $community);

        $requests = $community->joinRequests()
            ->with('user')
            ->pending()
            ->latest()
            ->paginate(20);

        return response()->json($requests);
    }

    public function approveJoinRequest(Community $community, CommunityJoinRequest $request): JsonResponse
    {
        $this->authorize('moderate', $community);

        if ($request->community_id !== $community->id) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $request->approve(auth()->user());

        return response()->json(['message' => 'Request approved']);
    }

    public function rejectJoinRequest(Community $community, CommunityJoinRequest $request): JsonResponse
    {
        $this->authorize('moderate', $community);

        if ($request->community_id !== $community->id) {
            return response()->json(['message' => 'Invalid request'], 400);
        }

        $request->reject(auth()->user());

        return response()->json(['message' => 'Request rejected']);
    }
}
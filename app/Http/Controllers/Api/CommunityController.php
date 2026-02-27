<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommunityRequest;
use App\Http\Requests\UpdateCommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\PostResource;
use App\Models\Community;
use App\Models\CommunityJoinRequest;
use App\Models\User;
use App\Models\Post;
use App\Models\CommunityInvite;
use App\Services\CommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CommunityController extends Controller
{
    public function __construct(
        private CommunityService $communityService
    ) {}
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Community::query()
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->privacy, function($query, $privacy) {
                $query->where('privacy', $privacy);
            })
            ->when($request->verified, function($query) {
                $query->verified();
            });
        
        // Filter blocked/muted users
        if ($user) {
            $blockedIds = $user->blockedUsers()->pluck('blocked_id');
            $mutedIds = $user->mutedUsers()->pluck('muted_id');
            $excludedIds = $blockedIds->merge($mutedIds)->unique();
            
            if ($excludedIds->isNotEmpty()) {
                $query->whereNotIn('created_by', $excludedIds);
            }
        }
        
        $communities = $query->withCount('members', 'posts')
            ->orderBy('member_count', 'desc')
            ->paginate(20);

        return CommunityResource::collection($communities);
    }

    public function store(StoreCommunityRequest $request): JsonResponse
    {
        $community = $this->communityService->createCommunity(
            $request->validated(),
            auth()->user()
        );

        return response()->json([
            'message' => 'Community created successfully',
            'community' => new CommunityResource($community->load('creator')),
        ], 201);
    }

    public function show(Community $community)
    {
        $community->load(['creator', 'members' => function($query) {
            $query->limit(config('limits.pagination.suggestions'));
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
        $result = $this->communityService->joinCommunity($community, auth()->user());

        return response()->json(
            ['message' => $result['message']],
            $result['status'] ?? Response::HTTP_OK
        );
    }

    public function leave(Community $community): JsonResponse
    {
        try {
            $this->communityService->leaveCommunity($community, auth()->user());
            return response()->json(['message' => 'Left community successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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
            ->paginate(config('limits.pagination.posts'));

        return PostResource::collection($posts);
    }

    public function members(Community $community, Request $request)
    {
        $this->authorize('view', $community);
        
        $user = $request->user();
        
        $query = $community->members()
            ->when($request->role, function($query, $role) {
                $query->wherePivot('role', $role);
            });
        
        // Filter blocked/muted users
        if ($user) {
            $blockedIds = $user->blockedUsers()->pluck('blocked_id');
            $mutedIds = $user->mutedUsers()->pluck('muted_id');
            $excludedIds = $blockedIds->merge($mutedIds)->unique();
            
            if ($excludedIds->isNotEmpty()) {
                $query->whereNotIn('users.id', $excludedIds);
            }
        }
        
        $members = $query->paginate(config('limits.pagination.users'));

        return response()->json($members);
    }

    public function joinRequests(Community $community)
    {
        $this->authorize('moderate', $community);

        $requests = $community->joinRequests()
            ->with('user')
            ->pending()
            ->latest()
            ->paginate(config('limits.pagination.default'));

        return response()->json($requests);
    }

    public function approveJoinRequest(Community $community, CommunityJoinRequest $request): JsonResponse
    {
        $this->authorize('moderate', $community);

        if ($request->community_id !== $community->id) {
            return response()->json(['message' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $this->communityService->approveJoinRequest($request, auth()->user());

        return response()->json(['message' => 'Request approved']);
    }

    public function rejectJoinRequest(Community $community, CommunityJoinRequest $request): JsonResponse
    {
        $this->authorize('moderate', $community);

        if ($request->community_id !== $community->id) {
            return response()->json(['message' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $this->communityService->rejectJoinRequest($request, auth()->user());

        return response()->json(['message' => 'Request rejected']);
    }

    public function removeMember(Community $community, User $user): JsonResponse
    {
        $this->authorize('removeMember', [$community, $user]);

        $this->communityService->removeMember($community, $user);

        return response()->json(['message' => 'Member removed successfully']);
    }

    public function updateMemberRole(Community $community, User $user, Request $request): JsonResponse
    {
        $this->authorize('updateRole', [$community, $user]);

        $validated = $request->validate([
            'role' => 'required|in:member,moderator,admin'
        ]);

        $this->communityService->updateMemberRole($community, $user, $validated['role']);

        return response()->json(['message' => 'Role updated successfully']);
    }

    public function banMember(Community $community, User $user, Request $request): JsonResponse
    {
        $this->authorize('banMember', [$community, $user]);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'duration' => 'nullable|integer|min:1|max:365',
        ]);

        $this->communityService->banMember(
            $community,
            $user,
            $validated['reason'] ?? null,
            $validated['duration'] ?? null
        );

        return response()->json(['message' => 'Member banned successfully']);
    }

    public function unbanMember(Community $community, User $user): JsonResponse
    {
        $this->authorize('banMember', [$community, $user]);

        $this->communityService->unbanMember($community, $user);

        return response()->json(['message' => 'Member unbanned successfully']);
    }

    public function transferOwnership(Community $community, Request $request): JsonResponse
    {
        $this->authorize('transferOwnership', $community);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'confirm' => 'required|accepted'
        ]);

        try {
            $newOwner = User::findOrFail($validated['user_id']);
            $this->communityService->transferOwnership($community, $newOwner, auth()->user());
            return response()->json(['message' => 'Ownership transferred successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function pinPost(Community $community, Post $post): JsonResponse
    {
        $this->authorize('pin', $community);

        try {
            $this->communityService->pinPost($community, $post);
            return response()->json(['message' => 'Post pinned successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function unpinPost(Community $community, Post $post): JsonResponse
    {
        $this->authorize('pin', $community);

        try {
            $this->communityService->unpinPost($community, $post);
            return response()->json(['message' => 'Post unpinned successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function removePost(Community $community, Post $post): JsonResponse
    {
        $this->authorize('removePost', [$community, $post]);

        try {
            $this->communityService->removePost($community, $post);
            return response()->json(['message' => 'Post removed from community']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function muteCommunity(Community $community): JsonResponse
    {
        $this->communityService->muteCommunity($community, auth()->user());

        return response()->json(['message' => 'Community muted successfully']);
    }

    public function unmuteCommunity(Community $community): JsonResponse
    {
        $this->communityService->unmuteCommunity($community, auth()->user());

        return response()->json(['message' => 'Community unmuted successfully']);
    }

    public function getNotificationSettings(Community $community): JsonResponse
    {
        try {
            $settings = $this->communityService->getNotificationSettings($community, auth()->user());
            return response()->json(['settings' => $settings]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    public function updateNotificationSettings(Community $community, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'new_posts' => 'boolean',
            'new_members' => 'boolean',
            'role_changes' => 'boolean',
            'mentions' => 'boolean',
            'announcements' => 'boolean',
        ]);

        try {
            $this->communityService->updateNotificationSettings($community, auth()->user(), $validated);
            return response()->json(['message' => 'Settings updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    public function createInvite(Community $community, Request $request): JsonResponse
    {
        $this->authorize('invite', $community);

        $validated = $request->validate([
            'max_uses' => 'integer|min:1|max:100',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
        ]);

        $invite = $this->communityService->createInvite(
            $community,
            $validated['max_uses'] ?? 1,
            $validated['expires_in_days'] ?? null
        );

        return response()->json(['invite' => $invite], Response::HTTP_CREATED);
    }

    public function getInvites(Community $community): JsonResponse
    {
        $this->authorize('invite', $community);

        $invites = $community->invites()
            ->where('invited_by', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['invites' => $invites]);
    }

    public function deleteInvite(Community $community, string $code): JsonResponse
    {
        $this->authorize('invite', $community);

        $this->communityService->deleteInvite($community, $code, auth()->user());

        return response()->json(['message' => 'Invite deleted successfully']);
    }

    public function joinWithCode(string $code): JsonResponse
    {
        try {
            $community = $this->communityService->joinWithCode($code, auth()->user());
            return response()->json(['message' => 'Joined successfully', 'community' => $community]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
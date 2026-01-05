<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParentalControlRequest;
use App\Http\Resources\UserResource;
use App\Models\ParentalControl;
use App\Models\ParentalLink;
use App\Models\User;
use App\Services\ParentalControlService;
use Illuminate\Http\Request;

class ParentalControlController extends Controller
{
    private $service;

    public function __construct(ParentalControlService $service)
    {
        $this->service = $service;
    }

    public function linkChild(ParentalControlRequest $request)
    {
        $parent = $request->user();
        
        // Check if user is trying to link themselves
        if ($parent->is_child) {
            return response()->json(['message' => 'Children cannot create parental links'], 403);
        }
        
        $validated = $request->validated();
        $child = User::where('email', $validated['child_email'])->first();

        if (! $child->is_child) {
            return response()->json(['message' => 'User is not a child'], 400);
        }

        $link = ParentalLink::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Link request sent successfully', 'link' => $link], 201);
    }

    public function approveLink(Request $request)
    {
        $request->validate(['parent_id' => 'required|exists:users,id']);
        
        $child = $request->user();
        
        if (!$child->is_child) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $link = ParentalLink::where('parent_id', $request->parent_id)
            ->where('child_id', $child->id)
            ->where('status', 'pending')
            ->first();
            
        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        $link->update(['status' => 'approved']);

        return response()->json(['message' => 'Parental link approved']);
    }

    public function rejectLink(Request $request, ParentalLink $link)
    {
        if ($link->child_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $link->update(['status' => 'rejected']);

        return response()->json(['message' => 'Link rejected']);
    }

    public function getSettings(Request $request)
    {
        $control = $request->user()->parentalControl;

        if (! $control) {
            return response()->json(['message' => 'Parental control not active'], 404);
        }

        return response()->json($control);
    }

    public function updateSettings(ParentalControlRequest $request, User $child)
    {
        $parent = $request->user();
        $validated = $request->validated();

        $link = ParentalLink::where('parent_id', $parent->id)
            ->where('child_id', $child->id)
            ->where('status', 'approved')
            ->first();

        if (! $link) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $control = ParentalControl::updateOrCreate(
            ['child_id' => $child->id],
            $validated['settings'] ?? []
        );

        return response()->json($control);
    }

    public function getChildren(Request $request)
    {
        $children = $request->user()->children()
            ->wherePivot('status', 'approved')
            ->with('parentalControl')
            ->get();

        return UserResource::collection($children);
    }

    public function getParents(Request $request)
    {
        $parents = $request->user()->parents()
            ->wherePivot('status', 'approved')
            ->get();

        return UserResource::collection($parents);
    }

    public function childActivity(Request $request, User $child)
    {
        $parent = $request->user();

        $link = ParentalLink::where('parent_id', $parent->id)
            ->where('child_id', $child->id)
            ->where('status', 'approved')
            ->first();

        if (!$link) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'child' => $child,
            'activity' => [
                'posts_count' => $child->posts_count,
                'followers_count' => $child->followers_count,
                'following_count' => $child->following_count,
                'last_active' => $child->last_active_at
            ]
        ]);
    }

    public function blockContent(ParentalControlRequest $request, User $child)
    {
        $parent = $request->user();
        $validated = $request->validated();

        $link = ParentalLink::where('parent_id', $parent->id)
            ->where('child_id', $child->id)
            ->where('status', 'approved')
            ->first();

        if (! $link) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $control = ParentalControl::firstOrCreate(['child_id' => $child->id]);
        $this->service->blockContent($control->id, $validated['content_type'], $validated['content_id']);

        return response()->json(['message' => 'Content blocked']);
    }
}

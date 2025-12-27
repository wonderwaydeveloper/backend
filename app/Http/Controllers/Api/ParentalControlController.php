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
        $validated = $request->validated();
        $child = User::where('email', $validated['child_email'])->first();

        if (! $child->is_child) {
            return response()->json(['message' => 'این کاربر کودک نیست'], 400);
        }

        $link = ParentalLink::create([
            'parent_id' => $request->user()->id,
            'child_id' => $child->id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'درخواست ارسال شد', 'link' => $link], 201);
    }

    public function approveLink(Request $request, ParentalLink $link)
    {
        if ($link->child_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $link->update(['status' => 'approved']);

        ParentalControl::firstOrCreate(
            ['child_id' => $link->child_id],
            [
                'require_follow_approval' => true,
                'restrict_dm' => true,
                'content_filter' => true,
                'daily_post_limit' => 10,
            ]
        );

        return response()->json(['message' => 'لینک تایید شد', 'link' => $link]);
    }

    public function rejectLink(Request $request, ParentalLink $link)
    {
        if ($link->child_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $link->update(['status' => 'rejected']);

        return response()->json(['message' => 'لینک رد شد']);
    }

    public function getSettings(Request $request)
    {
        $control = $request->user()->parentalControl;

        if (! $control) {
            return response()->json(['message' => 'کنترل والدین فعال نیست'], 404);
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

        if (! $link) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $activity = $this->service->getChildActivity($child->id);

        return response()->json($activity);
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

        return response()->json(['message' => 'محتوا مسدود شد']);
    }
}

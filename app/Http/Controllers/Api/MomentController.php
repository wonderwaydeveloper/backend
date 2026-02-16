<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MomentRequest;
use App\Http\Resources\MomentResource;
use App\Models\Moment;
use App\Models\Post;
use App\Services\{MomentService, MediaService};
use Illuminate\Http\Request;

class MomentController extends Controller
{
    public function __construct(
        private MomentService $momentService,
        private MediaService $mediaService
    ) {}
    public function index(Request $request)
    {
        $moments = $this->momentService->getPublicMoments($request->boolean('featured'));
        return MomentResource::collection($moments);
    }

    public function store(MomentRequest $request)
    {
        $data = $request->validated();
        
        $moment = $this->momentService->createMoment($request->user(), $data);
        
        if ($request->hasFile('cover_image')) {
            $media = $this->mediaService->uploadImage(
                $request->file('cover_image'),
                $request->user()
            );
            $this->mediaService->attachToModel($media, $moment);
        }
        
        return new MomentResource($moment->load('media'));
    }

    public function show(Moment $moment)
    {
        try {
            $moment = $this->momentService->getMoment($moment, auth()->user());
            return new MomentResource($moment);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Moment not found'], 404);
        }
    }

    public function update(MomentRequest $request, Moment $moment)
    {
        $this->authorize('update', $moment);
        $moment = $this->momentService->updateMoment($moment, $request->validated());
        return new MomentResource($moment);
    }

    public function destroy(Moment $moment)
    {
        $this->authorize('delete', $moment);
        $this->momentService->deleteMoment($moment);
        return response()->json(['message' => 'Moment deleted successfully']);
    }

    public function addPost(Request $request, Moment $moment)
    {
        $this->authorize('update', $moment);

        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'position' => 'nullable|integer|min:0',
        ]);

        try {
            $this->momentService->addPostToMoment($moment, $request->post_id, $request->position);
            return response()->json(['message' => 'Post added to moment']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function removePost(Request $request, Moment $moment, Post $post)
    {
        $this->authorize('update', $moment);

        try {
            $this->momentService->removePostFromMoment($moment, $post->id);
            return response()->json(['message' => 'Post removed from moment']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function myMoments(Request $request)
    {
        $moments = $this->momentService->getUserMoments($request->user());
        return MomentResource::collection($moments);
    }

    public function featured()
    {
        $moments = $this->momentService->getFeaturedMoments();
        return MomentResource::collection($moments);
    }
}

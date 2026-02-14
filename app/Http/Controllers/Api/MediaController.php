<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaUploadRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Media::class);

        $type = $request->query('type');
        $media = $this->mediaService->getUserMedia($request->user(), $type);

        return MediaResource::collection($media);
    }

    public function show(Media $media)
    {
        $this->authorize('view', $media);

        return new MediaResource($media);
    }

    public function uploadImage(MediaUploadRequest $request)
    {
        $this->authorize('create', Media::class);

        $media = $this->mediaService->uploadImage(
            $request->file('image'),
            $request->user(),
            $request->input('alt_text'),
            $request->input('type', 'post')
        );

        return new MediaResource($media);
    }

    public function uploadVideo(Request $request)
    {
        $this->authorize('create', Media::class);

        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi|max:524288',
            'type' => 'in:post,story',
        ]);

        $media = $this->mediaService->uploadVideo(
            $request->file('video'),
            $request->user(),
            $request->input('type', 'post')
        );

        return new MediaResource($media);
    }

    public function uploadDocument(Request $request)
    {
        $this->authorize('create', Media::class);

        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $media = $this->mediaService->uploadDocument(
            $request->file('document'),
            $request->user()
        );

        return new MediaResource($media);
    }

    public function destroy(Media $media)
    {
        $this->authorize('delete', $media);

        $this->mediaService->deleteMedia($media);

        return response()->json(['message' => 'Media deleted successfully']);
    }
}

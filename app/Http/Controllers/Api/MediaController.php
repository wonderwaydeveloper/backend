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

        $maxSize = config('content.media.max_file_size.video') / 1024;
        $maxDuration = config('content.media.video_dimensions.max_duration');
        
        $request->validate([
            'video' => "required|file|mimes:mp4,mov,avi|max:{$maxSize}",
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

        $maxSize = config('content.media.max_file_size.document') / 1024; // Convert to KB
        $request->validate([
            'document' => "required|file|mimes:pdf,doc,docx|max:{$maxSize}",
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

    public function status(Media $media)
    {
        $this->authorize('view', $media);

        $response = [
            'id' => $media->id,
            'type' => $media->type,
            'encoding_status' => $media->encoding_status,
            'processing_progress' => $media->processing_progress,
        ];

        if ($media->isVideo()) {
            $response['duration'] = $media->duration;
            $response['thumbnail_url'] = $media->thumbnail_url;
            $response['video_urls'] = $media->isProcessed() ? [
                '240p' => $media->getVideoUrl('240p'),
                '360p' => $media->getVideoUrl('360p'),
                '480p' => $media->getVideoUrl('480p'),
                '720p' => $media->getVideoUrl('720p'),
                '1080p' => $media->getVideoUrl('1080p'),
            ] : null;
        }

        if ($media->isImage()) {
            $response['image_urls'] = [
                'small' => $media->getImageUrl('small'),
                'medium' => $media->getImageUrl('medium'),
                'large' => $media->getImageUrl('large'),
                'original' => $media->getImageUrl('original'),
            ];
        }

        return response()->json($response);
    }
}

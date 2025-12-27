<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StreamRequest;
use App\Http\Resources\StreamResource;
use App\Models\Stream;
use App\Models\LiveStream;
use App\Services\StreamingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreamingController extends Controller
{
    private StreamingService $streamingService;

    public function __construct(StreamingService $streamingService)
    {
        $this->streamingService = $streamingService;
    }

    public function create(StreamRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $stream = $this->streamingService->createStream(auth()->user(), $validated);

        return response()->json([
            'success' => true,
            'stream' => new StreamResource($stream)
        ], 201);
    }

    public function startById(Stream $stream): JsonResponse
    {
        if ($stream->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stream->update([
            'status' => 'live',
            'started_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function endById(Stream $stream): JsonResponse
    {
        if ($stream->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stream->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function joinById(Stream $stream): JsonResponse
    {
        if ($stream->status !== 'live') {
            return response()->json(['message' => 'Stream is not live'], 404);
        }

        if ($stream->is_private && !auth()->user()->following->contains($stream->user_id)) {
            return response()->json(['message' => 'Cannot join private stream'], 403);
        }

        return response()->json(['success' => true]);
    }

    public function leaveById(Stream $stream): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    public function statsById(Stream $stream): JsonResponse
    {
        return response()->json([
            'viewer_count' => 0,
            'duration' => $stream->started_at ? now()->diffInSeconds($stream->started_at) : 0,
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'stream_key' => 'required|string',
        ]);

        $success = $this->streamingService->startStream($request->stream_key);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found or cannot be started',
            ], 404);
        }

        return response()->json(['success' => true]);
    }

    public function end(Request $request): JsonResponse
    {
        $request->validate([
            'stream_key' => 'required|string',
        ]);

        $success = $this->streamingService->endStream($request->stream_key);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Stream not found',
            ], 404);
        }

        return response()->json(['success' => true]);
    }

    public function join(Request $request, string $streamKey): JsonResponse
    {
        $result = $this->streamingService->joinStream($streamKey, auth()->user());

        if (! $result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    public function leave(Request $request, string $streamKey): JsonResponse
    {
        $this->streamingService->leaveStream($streamKey, auth()->user());

        return response()->json(['success' => true]);
    }

    public function stats(string $streamKey): JsonResponse
    {
        $stats = $this->streamingService->getStreamStats($streamKey);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    public function live(): JsonResponse
    {
        $streams = $this->streamingService->getLiveStreams();

        return response()->json([
            'success' => true,
            'streams' => $streams,
        ]);
    }

    public function show(Stream $stream): JsonResponse
    {
        $stream->load('user:id,name,username,avatar');

        return response()->json([
            'success' => true,
            'stream' => new StreamResource($stream)
        ]);
    }

    public function myStreams(): JsonResponse
    {
        $streams = Stream::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'streams' => $streams,
        ]);
    }

    public function delete(Stream $stream): JsonResponse
    {
        if ($stream->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($stream->status === 'live') {
            $this->streamingService->endStream($stream->stream_key);
        }

        $stream->delete();

        return response()->json(['success' => true]);
    }

    // Webhook endpoints for Nginx RTMP
    public function auth(Request $request): JsonResponse
    {
        $streamKey = $request->input('name');

        if (! $streamKey) {
            return response()->json(['success' => false], 403);
        }

        $authenticated = $this->streamingService->authenticateStream($streamKey);

        return response()->json(['success' => $authenticated], $authenticated ? 200 : 403);
    }

    public function publishDone(Request $request): JsonResponse
    {
        $streamKey = $request->input('name');

        if ($streamKey) {
            $this->streamingService->endStream($streamKey);
        }

        return response()->json(['success' => true]);
    }

    public function play(Request $request): JsonResponse
    {
        $streamKey = $request->input('name');

        if ($streamKey) {
            $this->streamingService->joinStream($streamKey);
        }

        return response()->json(['success' => true]);
    }

    public function playDone(Request $request): JsonResponse
    {
        $streamKey = $request->input('name');

        if ($streamKey) {
            $this->streamingService->leaveStream($streamKey);
        }

        return response()->json(['success' => true]);
    }
}

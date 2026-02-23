<?php

namespace App\Http\Controllers\Api;

use App\Events\CommentCreated;
use App\Events\PostInteraction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}

    public function index(Post $post)
    {
        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name,username,avatar', 'replies.user:id,name,username,avatar'])
            ->withCount(['likes', 'replies'])
            ->visible()
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->paginate(config('limits.pagination.comments'));

        return response()->json($comments);
    }

    public function store(CreateCommentRequest $request, Post $post)
    {
        $this->authorize('create', Comment::class);

        try {
            $mediaFile = $request->hasFile('media') ? $request->file('media') : null;
            $parentId = $request->input('parent_id');
            
            $comment = $this->commentService->createComment(
                $post,
                $request->user(),
                $request->input('content'),
                $mediaFile,
                $parentId
            );

            // Process mentions
            $comment->processMentions($comment->content);

            // Fire event (queued notification)
            event(new CommentCreated($comment, $request->user()));

            // Broadcast real-time
            broadcast(new PostInteraction($post, 'comment', $request->user(), [
                'comment' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => $comment->user->only(['id', 'name', 'username', 'avatar']),
                ],
            ]));

            $comment->load('user:id,name,username,avatar', 'media');

            return response()->json($comment, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        try {
            $this->commentService->deleteComment($comment, auth()->user());
            return response()->json(['message' => 'Comment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function like(Comment $comment)
    {
        try {
            $result = $this->commentService->toggleLike($comment, auth()->user());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $request->validate([
            'content' => 'required|string|max:' . config('content.validation.content.comment.max_length'),
        ]);

        try {
            $updated = $this->commentService->updateComment($comment, auth()->user(), $request->input('content'));
            return response()->json($updated);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pin(Comment $comment)
    {
        try {
            $this->commentService->pinComment($comment, auth()->user());
            return response()->json(['message' => 'Comment pinned successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function hide(Comment $comment)
    {
        try {
            $this->commentService->hideComment($comment, auth()->user());
            return response()->json(['message' => 'Comment hidden successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Http\Resources\GenericResource;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private CommentService $commentService)
    {
    }

    /**
     * ایجاد کامنت جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'commentable_type' => 'required|in:post,article',
            'commentable_id' => 'required|integer',
            'parent_id' => 'sometimes|exists:comments,id',
            'media' => 'sometimes|file|image|max:2048',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $commentable = $this->commentService->getCommentable(
                $request->commentable_type,
                $request->commentable_id
            );

            $this->authorize('create', [Comment::class, $commentable]);

            $comment = $this->commentService->createComment(
                $request->user(),
                $commentable,
                $request->all()
            );

            return GenericResource::success(
                new CommentResource($comment->load('user', 'media')),
                'Comment created successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * آپدیت کامنت
     */
    public function update(Request $request, Comment $comment)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('update', $comment);

            $comment = $this->commentService->updateComment($comment, $request->all());

            return GenericResource::success(
                new CommentResource($comment->load('user', 'media')),
                'Comment updated successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * حذف کامنت
     */
    public function destroy(Request $request, Comment $comment)
    {
        try {
            $this->authorize('delete', $comment);

            $this->commentService->deleteComment($comment);

            return GenericResource::success(null, 'Comment deleted successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * لایک کردن کامنت
     */
    public function like(Request $request, Comment $comment)
    {
        try {
            $this->authorize('like', $comment);

            $liked = $this->commentService->toggleLike($request->user(), $comment);

            return GenericResource::success([
                'liked' => $liked,
                'like_count' => $comment->fresh()->like_count,
            ], $liked ? 'Comment liked' : 'Comment unliked');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * پاسخ به کامنت
     */
    public function reply(Request $request, Comment $comment)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('reply', $comment);

            $reply = $this->commentService->createReply(
                $request->user(),
                $comment,
                $request->all()
            );

            return GenericResource::success(
                new CommentResource($reply->load('user', 'parent')),
                'Reply created successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت کامنت‌های یک محتوا
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commentable_type' => 'required|in:post,article',
            'commentable_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $commentable = $this->commentService->getCommentable(
                $request->commentable_type,
                $request->commentable_id
            );

            $this->authorize('view', $commentable);

            $comments = $this->commentService->getComments($commentable, $request->all());

            return GenericResource::success(
                CommentResource::collection($comments),
                'Comments retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }
}
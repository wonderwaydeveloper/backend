<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post)
    {
        $comments = $post->comments()
            ->with('user:id,name,username,avatar')
            ->withCount('likes')
            ->latest()
            ->paginate(20);

        return response()->json($comments);
    }

    public function store(Request $request, Post $post)
    {
        $request->validate([
            'content' => 'required|string|max:280',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        // Process mentions in comment
        $mentionedUsers = $comment->processMentions($comment->content);
        
        // Send mention notifications
        foreach ($mentionedUsers as $mentionedUser) {
            $mentionedUser->notify(new \App\Notifications\MentionNotification(auth()->user(), $comment));
        }

        $post->increment('comments_count');
        $comment->load('user:id,name,username,avatar');

        if ($post->user_id !== $request->user()->id) {
            \App\Models\Notification::create([
                'user_id' => $post->user_id,
                'from_user_id' => $request->user()->id,
                'type' => 'comment',
                'notifiable_id' => $comment->id,
                'notifiable_type' => 'App\\Models\\Comment',
            ]);
        }

        return response()->json($comment, 201);
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        if ($comment->post->comments_count > 0) {
            $comment->post->decrement('comments_count');
        }
        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    public function like(Comment $comment)
    {
        $user = auth()->user();

        if ($comment->isLikedBy($user->id)) {
            $comment->likes()->where('user_id', $user->id)->delete();
            if ($comment->likes_count > 0) {
                $comment->decrement('likes_count');
            }
            $liked = false;
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            $comment->increment('likes_count');
            $liked = true;
        }

        return response()->json(['liked' => $liked, 'likes_count' => $comment->likes_count]);
    }
}

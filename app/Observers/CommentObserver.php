<?php

namespace App\Observers;

use App\Models\Comment;
use App\Events\{CommentCreated, CommentDeleted};

class CommentObserver
{
    public function created(Comment $comment): void
    {
        $comment->post()->increment('comments_count');
        event(new CommentCreated($comment, $comment->user));
    }

    public function deleted(Comment $comment): void
    {
        if ($comment->post && $comment->post->comments_count > 0) {
            $comment->post()->decrement('comments_count');
        }
        event(new CommentDeleted($comment));
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckReplyPermission
{
    public function handle(Request $request, Closure $next)
    {
        $post = $request->route('post');
        $user = $request->user();

        if (!$post) {
            return $next($request);
        }

        // Post owner can always comment
        if ($post->user_id === $user->id) {
            return $next($request);
        }

        $replySettings = $post->reply_settings ?? 'everyone';

        switch ($replySettings) {
            case 'none':
                return response()->json([
                    'message' => 'Replies are disabled for this post',
                ], Response::HTTP_FORBIDDEN);

            case 'following':
                if (! $post->user->followers()->where('follower_id', $user->id)->exists()) {
                    return response()->json([
                        'message' => 'Only followers can reply to this post',
                    ], Response::HTTP_FORBIDDEN);
                }

                break;

            case 'mentioned':
                $content = $post->content;
                $username = $user->username;
                if (! str_contains($content, '@' . $username)) {
                    return response()->json([
                        'message' => 'Only mentioned users can reply to this post',
                    ], Response::HTTP_FORBIDDEN);
                }

                break;

            case 'everyone':
            default:
                break;
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Redis;

class TrackOnlineUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // اگر کاربر احراز هویت شده است، وضعیت آنلاین را در Redis ثبت کن
        if ($user = $request->user()) {
            $key = "user:online:{$user->id}";
            Redis::setex($key, 300, 'online'); // 5 minutes
        }

        return $response;
    }
}
<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PostNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => 'Post not found',
            'error' => 'POST_NOT_FOUND',
        ], 404);
    }
}

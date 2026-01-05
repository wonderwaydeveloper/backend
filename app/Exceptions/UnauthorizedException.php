<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class UnauthorizedException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message ?: 'You are not authorized to perform this operation',
            'error' => 'UNAUTHORIZED',
        ], 403);
    }
}

<?php

namespace App\Exceptions;

use Exception;

class BusinessLogicException extends Exception
{
    public function __construct(
        string $message = 'Business logic error occurred',
        public readonly string $errorCode = 'BUSINESS_LOGIC_ERROR',
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->getCode());
    }
}
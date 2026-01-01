<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected array $errors;

    public function __construct(array $errors, string $message = 'The submitted data is invalid', int $code = 422)
    {
        $this->errors = $errors;
        parent::__construct($message, $code);
    }

    public function render()
    {
        return response()->json([
            'error' => 'Validation failed',
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], $this->getCode());
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

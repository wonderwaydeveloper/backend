<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GenericResource extends JsonResource
{
    private $message;
    private $status;

    public function __construct($resource, $message = null, $status = 200)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->status = $status;
    }

    public function toArray(Request $request): array
    {
        // اگر وضعیت خطا است، داده‌های خطا را برگردان
        if ($this->status >= 400) {
            return [
                'success' => false,
                'message' => $this->message,
                'errors' => $this->resource,
            ];
        }

        // در غیر این صورت، داده‌های موفقیت را برگردان
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }

    public function with(Request $request): array
    {
        return [
            'meta' => [
                'message' => $this->message,
                'status' => $this->status,
                'timestamp' => now()->toISOString(),
            ],
        ];
    }

    /**
     * این متد، کد وضعیت HTTP را که در constructor تنظیم شده، به پاسخ نهایی اعمال می‌کند.
     */
    public function toResponse($request)
    {
        return parent::toResponse($request)->setStatusCode($this->status);
    }

    public static function success($data = null, $message = 'Operation completed successfully', $status = 200)
    {
        return new self($data, $message, $status);
    }

    public static function error($message = 'An error occurred', $status = 400, $errors = null)
    {
        return new self($errors, $message, $status);
    }
}
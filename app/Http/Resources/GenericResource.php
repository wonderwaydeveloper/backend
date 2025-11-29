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

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->resource,
        ];
    }

    /**
     * داده‌های اضافی
     */
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
     * ایجاد پاسخ موفق
     */
    public static function success($data = null, $message = 'Operation completed successfully', $status = 200)
    {
        return new self($data, $message, $status);
    }

    /**
     * ایجاد پاسخ خطا
     */
    public static function error($message = 'An error occurred', $status = 400, $errors = null)
    {
        return new self($errors, $message, $status);
    }
}
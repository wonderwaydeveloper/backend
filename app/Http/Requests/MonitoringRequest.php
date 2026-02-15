<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metric_name' => 'sometimes|string|max:255',
            'time_range' => 'sometimes|string|in:1h,6h,24h,7d,30d',
            'limit' => 'sometimes|integer|min:1|max:100',
        ];
    }
}

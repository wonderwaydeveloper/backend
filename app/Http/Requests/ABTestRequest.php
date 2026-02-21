<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ABTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:' . config('content.validation.max.name') . '|unique:ab_tests,name',
            'description' => 'nullable|string|max:' . config('content.validation.max.description'),
            'variants' => 'required|array|min:' . config('content.validation.min.poll_options') . '|max:' . config('content.validation.max.array_small'),
            'variants.A' => 'required|array',
            'variants.B' => 'required|array',
            'variants.C' => 'sometimes|array',
            'variants.D' => 'sometimes|array',
            'traffic_percentage' => 'integer|min:' . config('content.validation.min.instances') . '|max:' . config('content.validation.max.percentage'),
            'targeting_rules' => 'nullable|array',
            'starts_at' => 'nullable|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
        ];
    }
}

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
            'name' => 'required|string|max:100|unique:ab_tests,name',
            'description' => 'nullable|string|max:500',
            'variants' => 'required|array|min:2|max:4',
            'variants.A' => 'required|array',
            'variants.B' => 'required|array',
            'variants.C' => 'sometimes|array',
            'variants.D' => 'sometimes|array',
            'traffic_percentage' => 'integer|min:1|max:100',
            'targeting_rules' => 'nullable|array',
            'starts_at' => 'nullable|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
        ];
    }
}

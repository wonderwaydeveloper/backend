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
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:300',
            'variants' => 'required|array|min:2|max:5',
            'variants.*.name' => 'required|string|max:50',
            'variants.*.weight' => 'required|integer|min:1|max:100',
            'target_audience' => 'nullable|array',
            'start_date' => 'nullable|date|after:now',
            'end_date' => 'nullable|date|after:start_date',
            'success_metric' => 'required|string|max:50'
        ];
    }
}
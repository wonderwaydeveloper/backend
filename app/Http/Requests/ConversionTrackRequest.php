<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversionTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => 'required|string|max:' . config('validation.max.text_medium'),
            'event_data' => 'nullable|array',
            'conversion_value' => 'nullable|numeric|min:0',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'source' => 'nullable|string|max:' . config('validation.max.text_short'),
            'campaign' => 'nullable|string|max:' . config('validation.max.text_medium'),
            'properties' => 'nullable|array'
        ];
    }
}
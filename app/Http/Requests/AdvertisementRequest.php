<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'content' => 'required|string|max:300',
            'image' => 'nullable|image|max:2048',
            'target_url' => 'required|url|max:255',
            'budget' => 'required|numeric|min:10',
            'target_audience' => 'nullable|array',
            'target_audience.age_min' => 'integer|min:13|max:100',
            'target_audience.age_max' => 'integer|min:13|max:100',
            'target_audience.interests' => 'array|max:10',
            'start_date' => 'nullable|date|after:now',
            'end_date' => 'nullable|date|after:start_date'
        ];
    }
}
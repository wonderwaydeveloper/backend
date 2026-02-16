<?php

namespace App\Http\Requests;

use App\Rules\FileUpload;
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
            'title' => 'required|string|max:' . config('validation.max.title'),
            'content' => 'required|string|max:' . config('validation.max.content'),
            'image' => ['nullable', new FileUpload('avatar')],
            'target_url' => 'required|url|max:' . config('validation.max.url'),
            'budget' => 'required|numeric|min:10',
            'target_audience' => 'nullable|array',
            'target_audience.age_min' => 'integer|min:' . config('validation.min.age') . '|max:' . config('validation.max.age'),
            'target_audience.age_max' => 'integer|min:' . config('validation.min.age') . '|max:' . config('validation.max.age'),
            'target_audience.interests' => 'array|max:' . config('validation.max.interests'),
            'start_date' => 'nullable|date|after:now',
            'end_date' => 'nullable|date|after:start_date'
        ];
    }
}
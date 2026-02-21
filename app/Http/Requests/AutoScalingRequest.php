<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AutoScalingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string|in:scale_up,scale_down',
            'instances' => 'nullable|integer|min:' . config('content.validation.min.instances') . '|max:' . config('content.validation.max.instances'),
            'reason' => 'nullable|string|max:' . config('content.validation.max.reason')
        ];
    }
}
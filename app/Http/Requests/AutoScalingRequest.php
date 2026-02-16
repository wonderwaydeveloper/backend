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
            'instances' => 'nullable|integer|min:' . config('validation.min.instances') . '|max:' . config('validation.max.instances'),
            'reason' => 'nullable|string|max:' . config('validation.max.reason')
        ];
    }
}
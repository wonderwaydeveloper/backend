<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:100',
            'participant_ids' => 'required|array|min:2|max:49',
            'participant_ids.*' => 'required|integer|exists:users,id|distinct',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Group name is required',
            'name.min' => 'Group name must be at least 3 characters',
            'name.max' => 'Group name cannot exceed 100 characters',
            'participant_ids.required' => 'At least 2 participants are required',
            'participant_ids.min' => 'Group must have at least 2 participants',
            'participant_ids.max' => 'Group cannot have more than 49 participants',
            'participant_ids.*.exists' => 'One or more participants do not exist',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:200',
            'avatar' => 'nullable|image|max:512',
            'is_private' => 'boolean',
            'members' => 'nullable|array|max:100',
            'members.*' => 'integer|exists:users,id'
        ];
    }
}
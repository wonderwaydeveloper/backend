<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|in:spam,harassment,hate_speech,violence,nudity,other',
            'description' => 'nullable|string|max:500',
        ];
    }
}
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
            'reportable_type' => 'required|string|in:post,comment,user,message',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|in:spam,harassment,inappropriate,copyright,other',
            'description' => 'nullable|string|max:500',
            'evidence' => 'nullable|array|max:3',
            'evidence.*' => 'image|max:2048'
        ];
    }
}
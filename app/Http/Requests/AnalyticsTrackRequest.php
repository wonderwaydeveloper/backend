<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalyticsTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => 'required|string|in:post_view,post_like,post_share,post_comment,profile_view,follow,unfollow,search,click',
            'entity_type' => 'nullable|string|max:100',
            'entity_id' => 'nullable|integer',
            'properties' => 'nullable|array',
            'user_id' => 'nullable|integer|exists:users,id',
            'session_id' => 'nullable|string|max:100'
        ];
    }
}
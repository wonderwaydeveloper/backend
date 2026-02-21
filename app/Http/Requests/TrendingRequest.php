<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrendingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer|min:1|max:' . config('content.validation.trending.limit.max'),
            'timeframe' => 'nullable|integer|min:1|max:' . config('content.validation.trending.timeframe.max'),
        ];
    }
}

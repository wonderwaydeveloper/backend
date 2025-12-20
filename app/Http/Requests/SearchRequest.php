<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:1|max:100',
            'filter' => 'nullable|in:latest,popular,media',
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'عبارت جستجو الزامی است',
            'q.min' => 'عبارت جستجو باید حداقل 1 کاراکتر باشد',
            'q.max' => 'عبارت جستجو نباید بیشتر از 100 کاراکتر باشد',
        ];
    }
}

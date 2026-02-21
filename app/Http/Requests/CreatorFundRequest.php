<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatorFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => 'sometimes|required|integer|min:' . config('content.validation.min.month') . '|max:12',
            'year' => 'sometimes|required|integer|min:2020|max:' . (date('Y') + 1),
            'period' => 'sometimes|required|string|in:daily,weekly,monthly',
            'payout_method' => 'sometimes|required|string|in:bank_transfer,paypal,crypto',
            'bank_details' => 'required_if:payout_method,bank_transfer|array',
            'bank_details.account_number' => 'required_with:bank_details|string|max:' . config('content.validation.max.account_number'),
            'bank_details.routing_number' => 'required_with:bank_details|string|max:' . config('content.validation.max.routing_number'),
            'paypal_email' => 'required_if:payout_method,paypal|email',
            'crypto_wallet' => 'required_if:payout_method,crypto|string|max:' . config('content.validation.max.text_medium')
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PremiumSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|string|in:basic,pro,enterprise',
            'payment_method' => 'required|string|in:card,paypal,bank_transfer',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'coupon_code' => 'nullable|string|max:20',
            'auto_renew' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Plan selection is required',
            'plan_id.in' => 'Selected plan is invalid',
            'payment_method.required' => 'Payment method is required',
            'billing_cycle.required' => 'Billing cycle is required'
        ];
    }
}
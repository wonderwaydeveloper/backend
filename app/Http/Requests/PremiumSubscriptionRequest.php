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
            'plan_id.required' => 'انتخاب پلن الزامی است',
            'plan_id.in' => 'پلن انتخابی معتبر نیست',
            'payment_method.required' => 'روش پرداخت الزامی است',
            'billing_cycle.required' => 'دوره پرداخت الزامی است'
        ];
    }
}
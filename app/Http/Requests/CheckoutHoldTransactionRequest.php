<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutHoldTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_money' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'customer_money' => [
                'description' => 'Amount of money paid by customer for checkout.',
                'example' => '25000',
            ],
        ];
    }
}

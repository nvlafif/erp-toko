<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'quantity' => [
                'description' => 'Quantity to adjust (can be positive or negative).',
                'example' => '-2',
            ],
            'reason' => [
                'description' => 'Reason for the stock adjustment.',
                'example' => 'Damaged item',
            ],
        ];
    }
}

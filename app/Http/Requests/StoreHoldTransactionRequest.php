<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHoldTransactionRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
    public function bodyParameters(): array
    {
        return [
            'items' => [
                'description' => 'Array of items to hold.',
                'example' => [
                    ['product_id' => 1, 'quantity' => 2],
                    ['product_id' => 3, 'quantity' => 5],
                ],
            ],
        ];
    }}

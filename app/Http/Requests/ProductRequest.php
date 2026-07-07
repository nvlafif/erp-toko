<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : $product;

        return [
            'barcode' => [
                'nullable',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],

            'product_name' => 'required|string|max:255',

            'category_id' => 'required|exists:categories,id',

            'supplier_id' => 'required|exists:suppliers,id',

            'stock' => 'required|integer|min:0',

            'expired_date' => 'nullable|date',

            'unit_id' => 'required|exists:units,id',

            'purchase_price' => 'required|numeric|min:0',

            'selling_price' => 'required|numeric|min:0',

            'is_active' => 'boolean',
        ];
    }
}

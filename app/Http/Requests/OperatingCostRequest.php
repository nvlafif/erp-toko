<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OperatingCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
        ];
    }
    public function bodyParameters(): array
    {
        return [
            'cost_name' => [
                'description' => 'Name of the operating cost.',
                'example' => 'Rent',
            ],
            'amount' => [
                'description' => 'Amount of the operating cost.',
                'example' => '5000000',
            ],
            'cost_date' => [
                'description' => 'Date of the operating cost.',
                'example' => '2026-07-01',
            ],
        ];
    }}

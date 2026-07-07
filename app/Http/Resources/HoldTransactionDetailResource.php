<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HoldTransactionDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'barcode' => $this->product->barcode,
                    'product_name' => $this->product->product_name,
                ];
            }),
            'quantity' => $this->quantity,
            'selling_price' => $this->selling_price,
            'subtotal' => $this->subtotal,
        ];
    }
}

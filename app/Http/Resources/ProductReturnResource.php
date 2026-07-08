<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction' => $this->whenLoaded('transaction', function () {
                return [
                    'id' => $this->transaction->id,
                    'transaction_date' => $this->transaction->transaction_date,
                    'total_payment' => $this->transaction->total_payment,
                ];
            }),
            'processed_by' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'role' => $this->user->role,
                ];
            }),
            'return_date' => $this->return_date,
            'return_total' => $this->return_total,
            'items' => ProductReturnDetailResource::collection($this->whenLoaded('returnDetails')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

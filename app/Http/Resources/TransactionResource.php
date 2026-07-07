<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cashier' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'username' => $this->user->username,
                    'role' => $this->user->role,
                ];
            }),
            'transaction_date' => $this->transaction_date,
            'total_payment' => $this->total_payment,
            'customer_money' => $this->customer_money,
            'change_money' => $this->change_money,
            'items' => TransactionDetailResource::collection($this->whenLoaded('transactionDetails')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

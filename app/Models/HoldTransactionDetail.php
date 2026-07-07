<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldTransactionDetail extends Model
{
    protected $fillable = [
        'hold_transaction_id',
        'product_id',
        'quantity',
        'selling_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relationships of Entities
    public function holdTransaction()
    {
        return $this->belongsTo(HoldTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

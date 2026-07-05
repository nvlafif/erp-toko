<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldTransactionDetail extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
    ];

     // Relationships of Entities
    public function transaction()
    {
        return $this->belongsTo(HoldTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

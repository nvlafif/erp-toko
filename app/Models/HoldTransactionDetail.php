<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldTransactionDetail extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
    ];
}

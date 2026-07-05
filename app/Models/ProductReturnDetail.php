<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReturnDetail extends Model
{
    protected $table = 'return_details';
    protected $fillable = [
        'product_id',
        'quantity',
    ];
}

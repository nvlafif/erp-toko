<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'barcode',
        'product_name',
        'category_id',
        'supplier_id',
        'stock',
        'expired_date',
        'unit_id',
        'purchase_price',
        'selling_price',
    ];
}

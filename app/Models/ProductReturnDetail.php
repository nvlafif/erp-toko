<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReturnDetail extends Model
{
    protected $table = 'return_details';

    protected $fillable = [
        'return_id',
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

    public function productReturn()
    {
        return $this->belongsTo(ProductReturn::class, 'return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

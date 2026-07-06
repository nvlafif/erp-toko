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
        'is_active',
    ];

    // Relationships of Entities
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

}

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

    protected $casts = [
        'expired_date' => 'date',
        'stock' => 'integer',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
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

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function returnDetails()
    {
        return $this->hasMany(ProductReturnDetail::class);
    }

    public function holdTransactionDetails()
    {
        return $this->hasMany(HoldTransactionDetail::class);
    }
}

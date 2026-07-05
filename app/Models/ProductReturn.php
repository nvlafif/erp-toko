<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    protected $table = 'returns';
    protected $fillable = [
        'transaction_id',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Retur diproses oleh satu user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Satu retur memiliki banyak detail retur.
     */
    public function returnDetails()
    {
        return $this->hasMany(ProductReturnDetail::class, 'return_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

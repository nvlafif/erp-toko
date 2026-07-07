<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'transaction_id',
        'user_id',
        'return_date',
        'return_total',
    ];

    protected $casts = [
        'return_date' => 'datetime',
        'return_total' => 'decimal:2',
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
}

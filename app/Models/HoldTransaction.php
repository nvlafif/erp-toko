<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_date',
        'total_payment',
        'customer_money',
        'change_money',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'total_payment' => 'decimal:2',
        'customer_money' => 'decimal:2',
        'change_money' => 'decimal:2',
    ];

    // Relationships of Entities
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function holdTransactionDetails()
    {
        return $this->hasMany(HoldTransactionDetail::class);
    }

    public function transactionDetails()
    {
        return $this->holdTransactionDetails();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoldTransaction extends Model
{
    protected $fillable = [
        'customer_money',
    ];

    // Relationships of Entities
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(HoldTransactionDetail::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatingCost extends Model
{
    protected $fillable = [
        'expense_name',
        'amount',
        'expense_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReturn extends Model
{
    protected $table = 'returns';
    protected $fillable = [
        'transaction_id',
    ];
}

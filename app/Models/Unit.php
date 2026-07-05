<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'unit_name',
    ];

    // Relationships of Entities
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

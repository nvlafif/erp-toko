<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAudit extends Model
{
    protected $table = 'product_audits';

    protected $fillable = [
        'product_id',
        'user_id',
        'action',
        'changes',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

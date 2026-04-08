<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'min_orders',
        'min_total',
        'discount_percentage',
        'max_discount',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'min_orders' => 'integer',
        'min_total' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}

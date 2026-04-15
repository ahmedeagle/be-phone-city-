<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsTier extends Model
{
    protected $fillable = [
        'min_amount',
        'max_amount',
        'points_awarded',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'points_awarded' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Find the matching tier for a given invoice total
     */
    public static function findTierForAmount(float $amount): ?self
    {
        return static::where('is_active', true)
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->orderBy('min_amount', 'desc')
            ->first();
    }

    /**
     * Get all active tiers ordered by min_amount
     */
    public static function getActiveTiers()
    {
        return static::where('is_active', true)
            ->orderBy('min_amount', 'asc')
            ->get();
    }
}

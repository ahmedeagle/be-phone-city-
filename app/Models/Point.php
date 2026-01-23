<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'order_id',
        'product_id',
        'points_count',
        'status',
        'expire_at',
        'used_at',
        'description',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the points
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that generated these points (if any)
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product that generated these points (if any)
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for available points
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
            ->where(function ($q) {
                $q->whereNull('expire_at')
                    ->orWhere('expire_at', '>', now());
            });
    }

    /**
     * Scope for expired points
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere(function ($subQ) {
                    $subQ->where('status', self::STATUS_AVAILABLE)
                        ->whereNotNull('expire_at')
                        ->where('expire_at', '<=', now());
                });
        });
    }

    /**
     * Scope for used points
     */
    public function scopeUsed($query)
    {
        return $query->where('status', self::STATUS_USED);
    }

    /**
     * Check if points are expired
     */
    public function isExpired(): bool
    {
        return $this->expire_at && $this->expire_at->isPast();
    }

    /**
     * Check if points are available (not used and not expired)
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && !$this->isExpired();
    }

    /**
     * Mark points as used
     */
    public function markAsUsed(): bool
    {
        return $this->update([
            'status' => self::STATUS_USED,
            'used_at' => now(),
        ]);
    }

    /**
     * Mark points as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Get total available points for a user
     */
    public static function getAvailablePoints(int $userId): int
    {
        return static::where('user_id', $userId)
            ->available()
            ->sum('points_count');
    }

    /**
     * Get total used points for a user
     */
    public static function getUsedPoints(int $userId): int
    {
        return static::where('user_id', $userId)
            ->used()
            ->sum('points_count');
    }

    /**
     * Get total expired points for a user
     */
    public static function getExpiredPoints(int $userId): int
    {
        return static::where('user_id', $userId)
            ->expired()
            ->sum('points_count');
    }

    /**
     * Consume points for a user (use available points)
     * Returns the amount of points actually consumed
     *
     * @param int $userId
     * @param int $pointsToConsume
     * @param int|null $orderId Optional order ID to associate with
     * @return int Points actually consumed
     */
    public static function consumePoints(int $userId, int $pointsToConsume, ?int $orderId = null): int
    {
        if ($pointsToConsume <= 0) {
            return 0;
        }

        $pointsConsumed = 0;
        $remainingToConsume = $pointsToConsume;

        // Get available points ordered by oldest first (FIFO)
        $availablePoints = static::where('user_id', $userId)
            ->available()
            ->orderBy('expire_at', 'asc') // Points expiring soonest first
            ->orderBy('created_at', 'asc') // Then oldest first
            ->get();

        foreach ($availablePoints as $point) {
            if ($remainingToConsume <= 0) {
                break;
            }

            $pointsInThisRecord = $point->points_count;

            if ($pointsInThisRecord <= $remainingToConsume) {
                // Use all points from this record
                $point->update([
                    'status' => self::STATUS_USED,
                    'used_at' => now(),
                    'order_id' => $orderId,
                ]);
                $pointsConsumed += $pointsInThisRecord;
                $remainingToConsume -= $pointsInThisRecord;
            } else {
                // Need to split: use part of this record
                // Create a new record for the remaining points
                $remainingPoints = $pointsInThisRecord - $remainingToConsume;

                // Update current record to used with consumed amount
                $point->update([
                    'points_count' => $remainingToConsume,
                    'status' => self::STATUS_USED,
                    'used_at' => now(),
                    'order_id' => $orderId,
                ]);

                // Create new record for remaining points
                static::create([
                    'user_id' => $userId,
                    'order_id' => null,
                    'product_id' => $point->product_id,
                    'points_count' => $remainingPoints,
                    'status' => self::STATUS_AVAILABLE,
                    'expire_at' => $point->expire_at,
                    'description' => $point->description,
                ]);

                $pointsConsumed += $remainingToConsume;
                $remainingToConsume = 0;
            }
        }

        return $pointsConsumed;
    }
}

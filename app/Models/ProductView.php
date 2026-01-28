<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'viewed_at',
        'offer_sent',
        'offer_sent_at',
        'purchased',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'offer_sent' => 'boolean',
        'offer_sent_at' => 'datetime',
        'purchased' => 'boolean',
    ];

    /**
     * Get the user that viewed the product
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product that was viewed
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for views that haven't received an offer yet
     */
    public function scopePendingOffer($query)
    {
        return $query->where('offer_sent', false)
            ->where('purchased', false);
    }

    /**
     * Scope for views that haven't been purchased
     */
    public function scopeNotPurchased($query)
    {
        return $query->where('purchased', false);
    }

    /**
     * Mark offer as sent
     */
    public function markOfferSent(): bool
    {
        return $this->update([
            'offer_sent' => true,
            'offer_sent_at' => now(),
        ]);
    }

    /**
     * Mark as purchased
     */
    public function markAsPurchased(): bool
    {
        return $this->update(['purchased' => true]);
    }
}

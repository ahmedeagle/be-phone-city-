<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokPurchase extends Model
{
    // Keep the acronym's table name aligned with the migration.
    protected $table = 'tiktok_purchases';

    protected $guarded = [];
    protected $casts = [
        'context' => 'array', 'payload' => 'array', 'paid_at' => 'datetime',
        'sent_at' => 'datetime', 'first_attempt_at' => 'datetime', 'next_attempt_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

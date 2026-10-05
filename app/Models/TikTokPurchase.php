<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokPurchase extends Model
{
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

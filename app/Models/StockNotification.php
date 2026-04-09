<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_option_id',
        'email',
        'user_id',
        'notified',
        'notified_at',
    ];

    protected $casts = [
        'notified' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

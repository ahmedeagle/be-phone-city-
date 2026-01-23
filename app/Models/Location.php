<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'country',
        'city_id',
        'street_address',
        'national_address',
        'phone',
        'email',
        'label',
    ];

    /**
     * Get the user that owns the location
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the city for this location
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}

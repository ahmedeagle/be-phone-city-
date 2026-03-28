<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'address_ar',
        'address_en',
        'city_ar',
        'city_en',
        'latitude',
        'longitude',
        'google_maps_url',
        'phone',
        'phone2',
        'whatsapp',
        'working_hours_ar',
        'working_hours_en',
        'is_active',
        'sort_order',
        'oto_warehouse_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name_ar');
    }

    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getAddressAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->address_ar : $this->address_en;
    }

    public function getCityAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->city_ar : $this->city_en;
    }

    public function getWorkingHoursAttribute(): ?string
    {
        return app()->getLocale() === 'ar' ? $this->working_hours_ar : $this->working_hours_en;
    }
}

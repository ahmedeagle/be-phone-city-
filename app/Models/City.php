<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'status',
        'shipping_fee',
        'order',
    ];

    protected $casts = [
        'status' => 'boolean',
        'shipping_fee' => 'decimal:2',
    ];

    protected $appends = ['name'];

    /**
     * Get the field name to use for slug generation.
     */
    protected function getSlugSourceField(): string
    {
        return 'name_en';
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('name')
        );
    }

    /**
     * Scope for active cities
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for ordering
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name_en');
    }

    /**
     * Get all active cities simplified
     */
    public static function getAllActive()
    {
        return static::active()->ordered()->get(['id', 'name_en', 'name_ar', 'slug', 'shipping_fee']);
    }
}

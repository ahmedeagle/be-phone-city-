<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'value',
        'type',
        'status',
        'apply_to',
        'start_at',
        'end_at',
        'image',
        'show_in_home',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'show_in_home' => 'boolean',
    ];

    protected $appends = ['name'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('title')
        );
    }


    public function offerables()
    {
        return $this->hasMany(Offerable::class);
    }

    public function products()
    {
        return $this->morphedByMany(Product::class, 'offerable', 'offerables');
    }

    public function categories()
    {
        return $this->morphedByMany(Category::class, 'offerable', 'offerables');
    }

    /**
     * Check if this offer applies to a specific product
     */
    public function appliesToProduct(Product $product): bool
    {
        // Global offers
        if ($this->apply_to === 'all') {
            return true;
        }

        // Direct product offers
        if ($this->apply_to === 'product' && $this->products()->where('products.id', $product->id)->exists()) {
            return true;
        }

        // Category offers
        if ($this->apply_to === 'category') {
            return $this->categories()->whereIn('categories.id', $product->categories()->pluck('categories.id'))->exists();
        }

        return false;
    }

    /**
     * Scope for active offers
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }
}

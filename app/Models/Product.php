<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasSlug, HasTranslations;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'main_image',
        'description_en',
        'description_ar',
        'details_en',
        'details_ar',
        'about_en',
        'about_ar',
        'capacity',
        'points',
        'main_price',
        'purchase_price',
        'discounted_price',
        'quantity',
        'is_new',
        'is_new_arrival',
        'is_featured',
        'is_installment',
    ];

    protected $casts = [
        'main_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_new' => 'boolean',
        'is_new_arrival' => 'boolean',
        'is_featured' => 'boolean',
        'is_installment' => 'boolean',
        'details_en' => 'array',
        'details_ar' => 'array',
    ];

    protected $appends = ['name', 'description', 'details', 'about'];

    /**
     * Get the field name to use for slug generation.
     * The trait will use this method to determine which field to use.
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

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('description')
        );
    }

    protected function details(): Attribute
    {
        return Attribute::make(
            get: function () {
                $details = $this->translate('details');
                // If details is already an array, return it; otherwise try to decode JSON
                if (is_array($details)) {
                    return $details;
                }
                // Try to decode if it's a JSON string
                if (is_string($details)) {
                    $decoded = json_decode($details, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                }

                // Return empty array if details is null or invalid
                return $details ?: [];
            }
        );
    }

    protected function about(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('about')
        );
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * Check if product is in a category that requires bank transfer only
     */
    public function isInBankTransferCategory(): bool
    {
        return $this->categories()->where('is_bank_transfer', true)->exists();
    }

    /**
     * Check if product is in a category that requires installment only
     */
    public function isInInstallmentCategory(): bool
    {
        return $this->categories()->where('is_installment', true)->exists();
    }

    /**
     * Check if product is in a category that requires Madfu only
     */
    public function isInMadfuCategory(): bool
    {
        return $this->categories()->where('is_madfu', true)->exists();
    }

    /**
     * Whether the product supports installment payments.
     * Returns true when either the product flag is on OR the product is in
     * an installment-enabled category.
     */
    public function supportsInstallment(): bool
    {
        return (bool) $this->is_installment || $this->isInInstallmentCategory();
    }

    /**
     * Whether the product supports Madfu BNPL.
     * Returns true when the product is in a Madfu-enabled category, or when
     * the product itself is marked installment-eligible.
     */
    public function supportsMadfu(): bool
    {
        return $this->isInMadfuCategory() || (bool) $this->is_installment;
    }

    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function offers()
    {
        return $this->morphToMany(Offer::class, 'offerable', 'offerables');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function views()
    {
        return $this->hasMany(ProductView::class);
    }

    /**
     * Get all applicable offers for this product
     */
    public function getApplicableOffers()
    {
        $now = now();

        // Get direct product offers
        $productOffers = $this->offers()
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('start_at')
                    ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>=', $now);
            })
            ->get();

        // Get category offers (if product has categories)
        $categoryOffers = collect();
        if ($this->categories()->exists()) {
            $categoryOffers = Offer::whereHas('categories', function ($query) {
                $query->whereIn('categories.id', $this->categories()->pluck('categories.id'));
            })
                ->where('status', 'active')
                ->where(function ($query) use ($now) {
                    $query->whereNull('start_at')
                        ->orWhere('start_at', '<=', $now);
                })
                ->where(function ($query) use ($now) {
                    $query->whereNull('end_at')
                        ->orWhere('end_at', '>=', $now);
                })
                ->get();
        }

        // Get global offers
        $globalOffers = Offer::where('apply_to', 'all')
            ->where('status', 'active')
            ->where(function ($query) use ($now) {
                $query->whereNull('start_at')
                    ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>=', $now);
            })
            ->get();

        return $productOffers->merge($categoryOffers)->merge($globalOffers)->unique('id');
    }

    /**
     * Get the best offer (maximum discount)
     */
    public function getBestOffer(): ?Offer
    {
        $offers = $this->getApplicableOffers();

        if ($offers->isEmpty()) {
            return null;
        }

        $bestOffer = null;
        $maxDiscount = 0;
        $basePrice = $this->getBaseSellingPrice();

        foreach ($offers as $offer) {
            $discount = $offer->type === 'percentage'
                ? $basePrice * ($offer->value / 100)
                : $offer->value;

            if ($discount > $maxDiscount) {
                $maxDiscount = $discount;
                $bestOffer = $offer;
            }
        }

        return $bestOffer;
    }

    /**
     * Get base selling price before offers
     */
    public function getBaseSellingPrice(): float
    {
        return (float) ($this->discounted_price ?? $this->main_price);
    }

    /**
     * Calculate final price after applying best offer
     */
    public function getFinalPrice(): float
    {
        $basePrice = $this->getBaseSellingPrice();
        $bestOffer = $this->getBestOffer();

        if (! $bestOffer) {
            return $basePrice;
        }

        $discount = $bestOffer->type === 'percentage'
            ? $basePrice * ($bestOffer->value / 100)
            : $bestOffer->value;

        return max(0, $basePrice - $discount);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->quantity <= 10) {
            return 'limited';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Get points earned from this product
     */
    public function points()
    {
        return $this->hasMany(Point::class);
    }
}

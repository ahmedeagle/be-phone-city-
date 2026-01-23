<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'product_id',
        'type',
        'value_en',
        'value_ar',
        'price',
        'purchase_price',
        'discounted_price',
        'quantity',
        'sku',
    ];

    const TYPE_COLOR = 'color';
    const TYPE_SIZE = 'size';

    const TYPES = [
        self::TYPE_COLOR,
        self::TYPE_SIZE,
    ];

    protected $appends = ['value', 'stock_status'];


    protected $casts = [
        'price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('value')
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function getOriginalPrice(): float
    {
        return (float) ($this->price ?? $this->product->main_price);
    }

    public function getBaseSellingPrice(): float
    {
        return (float) ($this->discounted_price ?? $this->getOriginalPrice());
    }

    public function getFinalPrice(): float
    {
        $basePrice = $this->getBaseSellingPrice();
        $bestOffer = $this->getBestOffer();

        if (!$bestOffer) return $basePrice;

        $discount = $bestOffer->type === 'percentage'
            ? $basePrice * ($bestOffer->value / 100)
            : $bestOffer->value;

        return max(0, $basePrice - $discount);
    }

    public function getApplicableOffers()
    {
        $now = now();
        $product = $this->product;

        $productOffers = $product->offers()
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
        if ($product->categories()->exists()) {
            $categoryOffers = Offer::whereHas('categories', function ($query) use ($product) {
                $query->whereIn('categories.id', $product->categories()->pluck('categories.id'));
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

    public function getBestOffer(): ?Offer
    {
        $offers = $this->getApplicableOffers();

        if ($offers->isEmpty()) return null;

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
}

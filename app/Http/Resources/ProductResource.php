<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProductResource extends JsonResource
{
    protected $simple = false;

    public function simple()
    {
        $this->simple = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $bestOffer = $this->getBestOffer();
        $finalPrice = $this->getFinalPrice();
        $isSimple = $this->simple || $request->input('simple') === 'true';

        // Simple mode - return only essential data
        if ($isSimple) {
            return $this->getSimpleData($finalPrice);
        }

        // Full mode
        return $this->getFullData($bestOffer, $finalPrice);
    }

    /**
     * Get simple mode data
     */
    protected function getSimpleData(float $finalPrice): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'main_image' => $this->getMainImageUrl(),
            'original_price' => number_format($this->main_price, 2),
            'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
            'final_price' => number_format($finalPrice, 2),
            'stock_status' => $this->stock_status,
            'is_new' => $this->is_new,
            'is_new_arrival' => $this->is_new_arrival,
            'is_featured' => $this->is_featured,
            'options' => $this->getOptionsData(),
            'options_count' => $this->options_count ?? 0,
            'is_favorite' => $this->isFavorite(),
            'in_cart' => $this->inCart(),
        ];
    }

    /**
     * Get minimal data for order items (includes images and pricing for display)
     */
    public function forOrderItem(): array
    {
        $finalPrice = $this->getFinalPrice();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'main_image' => $this->getMainImageUrl(),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'original_price' => number_format($this->main_price, 2),
            'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
            'final_price' => number_format($finalPrice, 2),
        ];
    }

    /**
     * Get full mode data
     */
    protected function getFullData(?object $bestOffer, float $finalPrice): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'details' => $this->getDetailsArray(),
            'about' => $this->about,
            'capacity' => $this->capacity,
            'points' => $this->points,
            'is_new' => $this->is_new,
            'is_new_arrival' => $this->is_new_arrival,
            'is_featured' => $this->is_featured,
            'original_price' => number_format($this->main_price, 2),
            'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
            'final_price' => number_format($finalPrice, 2),
            'discount_amount' => $bestOffer || $this->discounted_price ? number_format($this->main_price - $finalPrice, 2) : null,
            'quantity' => $this->quantity,
            'stock_status' => $this->stock_status,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'options' => $this->getOptionsData(),
            'options_count' => $this->options_count ?? 0,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'main_image' => $this->getMainImageUrl(),
            'applied_offer' => $bestOffer ? new OfferResource($bestOffer) : null,
            'payment_methods' => $this->getPaymentMethods($finalPrice),
            'is_favorite' => $this->isFavorite(),
            'in_cart' => $this->inCart(),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'reviews_count' => $this->reviews_count ?? 0,
            'average_rating' => $this->reviews_avg_rating ? number_format((float) $this->reviews_avg_rating, 2) : null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Get details as array of key-value pairs
     */
    protected function getDetailsArray(): array
    {
        $details = $this->details;

        // If details is already an array, return it
        if (is_array($details)) {
            return $details;
        }

        // If details is null or empty, return empty array
        if (empty($details)) {
            return [];
        }

        // Try to decode if it's a JSON string
        if (is_string($details)) {
            $decoded = json_decode($details, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Get options data - if no options, create a default option with product data
     */
    protected function getOptionsData()
    {
        // Check if options relationship is loaded
        if (! $this->relationLoaded('options')) {
            // If not loaded, create default option
            return $this->createDefaultOption();
        }

        $options = $this->options;

        // If options exist, return them
        if ($options && $options->isNotEmpty()) {
            return ProductOptionResource::collection($options);
        }

        // If no options, create a default option using product data
        return $this->createDefaultOption();
    }

    /**
     * Create a default option when product has no options
     * This duplicates product data (images, payment methods, etc.) as an option
     */
    protected function createDefaultOption(): array
    {
        $bestOffer = $this->getBestOffer();
        $finalPrice = $this->getFinalPrice();
        $isSimple = $this->simple || request()->input('simple') === 'true';

        $defaultOption = [
            'id' => null,
            'type' => null,
            'value' => null,
            'original_price' => number_format($this->main_price, 2),
            'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
            'final_price' => number_format($finalPrice, 2),
            'discount_amount' => $bestOffer || $this->discounted_price ? number_format($this->main_price - $finalPrice, 2) : null,
            'sku' => null,
        ];

        // In full mode, include images and payment methods
        if (! $isSimple) {
            $defaultOption['images'] = ImageResource::collection($this->whenLoaded('images'));
            $defaultOption['applied_offer'] = $bestOffer ? new OfferResource($bestOffer) : null;
            $defaultOption['payment_methods'] = $this->getPaymentMethods($finalPrice);
        }

        return [$defaultOption];
    }

    /**
     * Get payment methods with calculated fees
     * When product is in a bank transfer category, only bank transfer payment methods are shown
     */
    protected function getPaymentMethods(float $finalPrice): array
    {
        $query = PaymentMethod::active();

        // If product is in a bank transfer category, show only bank transfer payment methods
        if ($this->isInBankTransferCategory()) {
            $query->bankTransfer();
        } elseif ($this->isInInstallmentCategory()) {
            // If product is in an installment-only category, show only installment payment methods
            $query->installmentOnly();
        } else {
            // If product does not support installment, exclude installment payment methods
            if (! $this->is_installment) {
                $query->where('is_installment', false);
            }
        }

        return $query->get()->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'image' => $method->image
                    ? asset('storage/'.$method->image)
                    : asset('images/payment-placeholder.jpg'),
                'is_bank_transfer' => (bool) $method->is_bank_transfer,
                'is_installment' => (bool) $method->is_installment,
                'gateway' => $method->gateway ?? null,
            ];
        })->toArray();
    }

    /**
     * Get main image URL
     */
    protected function getMainImageUrl(): ?string
    {
        return $this->main_image ? asset('storage/'.$this->main_image) : null;
    }

    /**
     * Check if product is favorite for authenticated user
     */
    protected function isFavorite(): bool
    {
        if (! Auth::guard('sanctum')->check()) {
            return false;
        }

        return $this->favorites
            ->where('user_id', Auth::guard('sanctum')->id())
            ->isNotEmpty();
    }

    /**
     * Check if product is in cart for authenticated user
     */
    protected function inCart(): bool
    {
        if (! Auth::guard('sanctum')->check()) {
            return false;
        }

        return $this->carts
            ->where('user_id', Auth::guard('sanctum')->id())
            ->isNotEmpty();
    }
}

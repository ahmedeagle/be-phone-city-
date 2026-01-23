<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Offer;

class OfferService
{
    /**
     * Get the best offer for a product
     */
    public function getBestOfferForProduct(Product $product): ?Offer
    {
        $offers = $product->getApplicableOffers();

        if ($offers->isEmpty()) {
            return null;
        }

        $bestOffer = null;
        $maxDiscount = 0;

        foreach ($offers as $offer) {
            $discount = $this->calculateDiscount($offer, $product->main_price);

            if ($discount > $maxDiscount) {
                $maxDiscount = $discount;
                $bestOffer = $offer;
            }
        }

        return $bestOffer;
    }

    /**
     * Calculate discount amount for an offer
     */
    public function calculateDiscount(Offer $offer, float $price): float
    {
        if ($offer->type === 'percentage') {
            return $price * ($offer->value / 100);
        }

        return (float) $offer->value;
    }

    /**
     * Calculate final price after applying offer
     */
    public function calculateFinalPrice(Product $product, ?Offer $offer = null): float
    {
        if (!$offer) {
            $offer = $this->getBestOfferForProduct($product);
        }

        if (!$offer) {
            return (float) $product->main_price;
        }

        $discount = $this->calculateDiscount($offer, $product->main_price);
        return max(0, $product->main_price - $discount);
    }
}

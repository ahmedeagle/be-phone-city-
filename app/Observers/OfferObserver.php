<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;

class OfferObserver
{
    /**
     * Handle the Offer "saved" event.
     * Remove offerables that don't match apply_to so switching between
     * product/category/all doesn't leave stale pivot records.
     */
    public function saved(Offer $offer): void
    {
        match ($offer->apply_to) {
            'product' => $offer->offerables()->where('offerable_type', Category::class)->delete(),
            'category' => $offer->offerables()->where('offerable_type', Product::class)->delete(),
            'all' => $offer->offerables()->delete(),
            default => null,
        };
    }
}

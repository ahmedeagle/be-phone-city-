<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

/**
 * Service class for handling shipping calculations and validation
 */
class ShippingService
{
    /**
     * Validate location for home delivery
     *
     * @param int $locationId Location ID to validate
     * @param int|null $userId User ID (defaults to authenticated user)
     * @return array ['valid' => bool, 'location' => Location|null, 'error' => string|null]
     */
    public function validateLocation(int $locationId, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        $location = Location::with('city')
            ->where('id', $locationId)
            ->where('user_id', $userId)
            ->first();

        if (!$location) {
            return [
                'valid' => false,
                'location' => null,
                'error' => __('Invalid location'),
            ];
        }

        if (!$location->city_id || !$location->city) {
            return [
                'valid' => false,
                'location' => $location,
                'error' => __('Location must have a valid city'),
            ];
        }

        if (!$location->city->status) {
            return [
                'valid' => false,
                'location' => $location,
                'error' => __('Selected city is not available for delivery'),
            ];
        }

        return [
            'valid' => true,
            'location' => $location,
            'error' => null,
        ];
    }

    /**
     * Calculate shipping cost based on delivery method and location
     *
     * @param string $deliveryMethod Delivery method (home/pickup)
     * @param Location|null $location Location model with city relation loaded
     * @param float $subtotal Order subtotal for free shipping calculation
     * @param float $freeShippingThreshold Minimum amount for free shipping
     * @param int $minItemsForFreeShipping Minimum number of items in cart for free shipping
     * @param int $cartItemsCount Number of items currently in cart
     * @param float|null $shippingCompanyCost Optional cost from selected shipping company
     * @return array ['amount' => float, 'qualifies_for_free' => bool]
     */
    public function calculateShipping(
        string $deliveryMethod,
        ?Location $location,
        float $subtotal,
        float $freeShippingThreshold = 0,
        int $minItemsForFreeShipping = 0,
        int $cartItemsCount = 0,
        ?float $shippingCompanyCost = null
    ): array {
        // Store pickup - no shipping
        if ($deliveryMethod !== Order::DELIVERY_HOME) {
            return [
                'amount' => 0,
                'qualifies_for_free' => false,
            ];
        }

        // Home delivery without valid location
        if (!$location || !$location->city) {
            return [
                'amount' => 0,
                'qualifies_for_free' => false,
            ];
        }

        // Check for free shipping: both conditions must be met
        $meetsAmountCondition = $freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold;
        $meetsItemsCondition = $minItemsForFreeShipping <= 0 || $cartItemsCount >= $minItemsForFreeShipping;

        if ($meetsAmountCondition && $meetsItemsCondition) {
            return [
                'amount' => 0,
                'qualifies_for_free' => true,
            ];
        }

        // Use shipping company cost if provided, otherwise fall back to city shipping fee
        $amount = $shippingCompanyCost !== null ? $shippingCompanyCost : ($location->city->shipping_fee ?? 0);

        return [
            'amount' => $amount,
            'qualifies_for_free' => false,
        ];
    }
}

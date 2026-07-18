<?php

namespace App\Services;

use App\Models\Location;
use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Service class for calculating order totals and components
 */
class OrderCalculationService
{
    public function __construct(
        protected DiscountService $discountService,
        protected ShippingService $shippingService,
        protected PointsService $pointsService,
        protected VipTierService $vipTierService
    ) {}

    /**
     * Calculate order subtotal from cart items
     *
     * @param Collection $cartItems Cart items collection
     * @return float Subtotal amount
     */
    public function calculateSubtotal(Collection $cartItems): float
    {
        return $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    /**
     * Calculate tax as a straight percentage of the inclusive amount
     *
     * @param float $amount Amount that includes tax (tax-inclusive pricing)
     * @param float|null $taxPercentage Tax percentage (defaults to setting value)
     * @return float Tax amount
     */
    public function calculateTax(float $amount, ?float $taxPercentage = null): float
    {
        if ($taxPercentage === null) {
            $settings = Setting::getSettings();
            $taxPercentage = $settings->tax_percentage ?? 0;
        }

        if ($taxPercentage <= 0) {
            return 0;
        }

        // Extract the embedded tax from a tax-inclusive price.
        // Formula: tax = amount × rate / (1 + rate)   where rate = taxPercentage / 100
        return $amount * ($taxPercentage / (100 + $taxPercentage));
    }

    /**
     * Calculate complete order totals
     * Returns all calculated components and final total
     *
     * @param array $params Calculation parameters
     * @return array Complete calculation breakdown
     */
    public function calculateOrderTotals(array $params): array
    {
        // Extract parameters with defaults
        $cartItems = $params['cart_items'];
        $discountCode = $params['discount_code'] ?? null;
        $deliveryMethod = $params['delivery_method'];
        $location = $params['location'] ?? null;
        $paymentMethod = $params['payment_method'];
        $usePoints = $params['use_points'] ?? false;
        $requestedPointsDiscount = $params['requested_points_discount'] ?? null;
        $userId = $params['user_id'];
        $shippingCompanyId = $params['shipping_company_id'] ?? null;

        // Get settings
        $settings = Setting::getSettings();
        $taxPercentage = $settings->tax_percentage ?? 0;
        $freeShippingThreshold = $settings->free_shipping_threshold ?? 0;
        $minItemsForFreeShipping = $settings->min_items_for_free_shipping ?? 0;
        $pointValue = $settings->point_value ?? 1.00;

        // Calculate subtotal
        $subtotal = $this->calculateSubtotal($cartItems);

        // Process discount (with condition validation)
        $cartItemsCount = $cartItems->sum('quantity');
        $discountData = $this->discountService->processDiscount($discountCode, $subtotal, $userId, $cartItemsCount);
        $discount = $discountData['discount'];
        $discountAmount = $discountData['amount'];

        // Look up shipping company cost if selected
        $shippingCompanyCost = null;
        if ($shippingCompanyId) {
            $shippingCompany = \App\Models\ShippingCompany::where('id', $shippingCompanyId)
                ->where('is_active', true)
                ->first();
            if ($shippingCompany) {
                $shippingCompanyCost = (float) $shippingCompany->cost;
            }
        }

        // Calculate shipping
        $shippingData = $this->shippingService->calculateShipping(
            $deliveryMethod,
            $location,
            $subtotal,
            $freeShippingThreshold,
            $minItemsForFreeShipping,
            $cartItemsCount,
            $shippingCompanyCost
        );
        $shippingAmount = $shippingData['amount'];
        $qualifiesForFreeShipping = $shippingData['qualifies_for_free'];

        // Calculate points discount
        $pointsData = ['discount_amount' => 0, 'points_to_consume' => 0, 'available_points' => 0];
        if ($usePoints) {
            $pointsData = $this->pointsService->calculatePointsDiscount($userId, $subtotal, $pointValue, $requestedPointsDiscount);
        }
        $pointsDiscount = $pointsData['discount_amount'];

        // Calculate VIP tier discount (with max cap)
        $vipDiscountData = $this->vipTierService->calculateVipDiscount(
            \App\Models\User::find($userId),
            $subtotal
        );
        $vipDiscount = $vipDiscountData['amount'];
        $vipTier = $vipDiscountData['tier'];
        $vipTierDiscountPercentage = $vipDiscountData['percentage'];
        $vipMaxDiscount = $vipDiscountData['max_discount'];
        $vipTierLabelAr = $vipDiscountData['tier_label_ar'];
        $vipTierLabelEn = $vipDiscountData['tier_label_en'];

        // Calculate tax (inclusive)
        // Tax is already included in product prices, so it's part of the subtotal
        // We calculate it from the total items amount after discounts for reporting
        $taxableItemsAmount = max(0, $subtotal - $discountAmount - $vipDiscount - $pointsDiscount);
        $taxAmount = $this->calculateTax($taxableItemsAmount, $taxPercentage);

        // Calculate final total (tax is already in subtotal)
        $total = $subtotal - $discountAmount - $vipDiscount + $shippingAmount - $pointsDiscount;
        $total = max(0, $total); // Ensure total is not negative

        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'discount_model' => $discount,
            'vip_discount' => $vipDiscount,
            'vip_tier' => $vipTier,
            'vip_tier_discount_percentage' => $vipTierDiscountPercentage,
            'vip_max_discount' => $vipMaxDiscount,
            'vip_tier_label_ar' => $vipTierLabelAr,
            'vip_tier_label_en' => $vipTierLabelEn,
            'shipping' => $shippingAmount,
            'qualifies_for_free_shipping' => $qualifiesForFreeShipping,
            'tax' => $taxAmount,
            'tax_percentage' => $taxPercentage,
            'payment_method_fee' => 0,
            'points_discount' => $pointsDiscount,
            'points_to_consume' => $pointsData['points_to_consume'],
            'available_points' => $pointsData['available_points'],
            'total' => $total,
            'settings' => $settings,
        ];
    }

    /**
     * Check stock availability for cart items
     *
     * @param Collection $cartItems Cart items collection
     * @return array Stock issues found
     */
    public function checkStockAvailability(Collection $cartItems): array
    {
        $stockIssues = [];

        foreach ($cartItems as $cartItem) {
            $available = $cartItem->productOption ? $cartItem->productOption->quantity : $cartItem->product->quantity;
            if ($available < $cartItem->quantity) {
                $stockIssues[] = [
                    'product_id' => $cartItem->product->id,
                    'product_name' => $cartItem->product->name,
                    'product_option_id' => $cartItem->product_option_id,
                    'requested' => $cartItem->quantity,
                    'available' => $available,
                ];
            }
        }

        return $stockIssues;
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Point;
use App\Models\PointsTier;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

/**
 * Service class for handling points calculations and operations
 */
class PointsService
{
    /**
     * Calculate points discount based on available points
     *
     * @param int $userId User ID
     * @param float $subtotal Order subtotal
     * @param float|null $pointValue Value of one point (defaults to setting value)
     * @return array ['discount_amount' => float, 'points_to_consume' => int, 'available_points' => int]
     */
    public function calculatePointsDiscount(int $userId, float $subtotal, ?float $pointValue = null, ?float $requestedAmount = null): array
    {
        // Get point value from settings if not provided
        if ($pointValue === null) {
            $settings = Setting::getSettings();
            $pointValue = $settings->point_value ?? 1.00;
        }

        // Get user's available points
        $availablePoints = Point::getAvailablePoints($userId);

        if ($availablePoints <= 0) {
            return [
                'discount_amount' => 0,
                'points_to_consume' => 0,
                'available_points' => 0,
            ];
        }

        // Calculate maximum discount amount from available points
        $maxDiscountFromPoints = $availablePoints * $pointValue;

        // Calculate discount (can't exceed subtotal)
        $discountAmount = min($maxDiscountFromPoints, $subtotal);

        // If a specific amount was requested, cap to that (but never exceed max)
        if ($requestedAmount !== null && $requestedAmount > 0) {
            $discountAmount = min($requestedAmount, $discountAmount);
        }

        // Calculate how many points are needed for this discount
        $pointsToConsume = (int) ceil($discountAmount / $pointValue);

        // Recalculate discount based on points to consume (ensure whole points)
        $discountAmount = $pointsToConsume * $pointValue;

        // Ensure discount doesn't exceed subtotal
        $discountAmount = min($discountAmount, $subtotal);

        // Final recalculation of points needed based on final discount
        $pointsToConsume = (int) ceil($discountAmount / $pointValue);

        return [
            'discount_amount' => $discountAmount,
            'points_to_consume' => $pointsToConsume,
            'available_points' => $availablePoints,
        ];
    }

    /**
     * Award points to user based on invoice total tier
     *
     * @param int $userId User ID
     * @param int $orderId Order ID
     * @param \Illuminate\Support\Collection $cartItems Cart items collection
     * @return int Total points awarded
     */
    public function awardPointsFromCart(int $userId, int $orderId, $cartItems): int
    {
        $settings = Setting::getSettings();
        $pointsDaysExpired = $settings->points_days_expired ?? 365;
        $expireAt = now()->addDays($pointsDaysExpired);

        // Calculate total invoice amount from cart items
        $invoiceTotal = 0;
        foreach ($cartItems as $cartItem) {
            $invoiceTotal += ($cartItem->product->price ?? 0) * ($cartItem->quantity ?? 1);
        }

        // Find matching tier
        $tier = PointsTier::findTierForAmount($invoiceTotal);

        if (!$tier || $tier->points_awarded <= 0) {
            return 0;
        }

        // Award single points record based on tier
        Point::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'product_id' => null,
            'points_count' => $tier->points_awarded,
            'status' => Point::STATUS_AVAILABLE,
            'expire_at' => $expireAt,
            'description' => __('Points earned from order total: :amount SAR', [
                'amount' => number_format($invoiceTotal, 2),
            ]),
        ]);

        return $tier->points_awarded;
    }

    /**
     * Award points to user based on order total tier (when order is paid)
     *
     * @param Order $order Paid order
     * @return int Total points awarded
     */
    public function awardPointsFromOrder(Order $order): int
    {
        if (!$order->user_id) {
            return 0;
        }

        // Avoid double-awarding if points already exist for this order
        if (Point::where('order_id', $order->id)->exists()) {
            return 0;
        }

        $settings = Setting::getSettings();
        $pointsDaysExpired = $settings->points_days_expired ?? 365;
        $expireAt = now()->addDays($pointsDaysExpired);

        // Use order total for tier matching
        $invoiceTotal = (float) $order->total;

        // Find matching tier
        $tier = PointsTier::findTierForAmount($invoiceTotal);

        if (!$tier || $tier->points_awarded <= 0) {
            return 0;
        }

        // Award single points record based on tier
        Point::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'product_id' => null,
            'points_count' => $tier->points_awarded,
            'status' => Point::STATUS_AVAILABLE,
            'expire_at' => $expireAt,
            'description' => __('Points earned from order total: :amount SAR', [
                'amount' => number_format($invoiceTotal, 2),
            ]),
        ]);

        return $tier->points_awarded;
    }

    /**
     * Consume points for order
     *
     * @param int $userId User ID
     * @param int $pointsToConsume Number of points to consume
     * @param int $orderId Order ID
     * @return bool Success status
     */
    public function consumePoints(int $userId, int $pointsToConsume, int $orderId): bool
    {
        if ($pointsToConsume <= 0) {
            return false;
        }

        return Point::consumePoints($userId, $pointsToConsume, $orderId);
    }
}

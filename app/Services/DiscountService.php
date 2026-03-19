<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

/**
 * Service class for handling discount validation and calculations
 */
class DiscountService
{
    /**
     * Validate and retrieve discount code
     *
     * @param string|null $code Discount code to validate
     * @return Discount|null Returns discount model if valid, null otherwise
     */
    public function validateDiscountCode(?string $code): ?Discount
    {
        if (empty($code)) {
            return null;
        }

        return Discount::where('code', $code)
            ->where('status', true)
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->first();
    }

    /**
     * Validate discount conditions against order context
     *
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validateConditions(Discount $discount, float $subtotal, ?int $userId = null, int $cartItemsCount = 0): array
    {
        $condition = $discount->condition;

        // No condition set — discount applies unconditionally
        if (empty($condition) || !is_array($condition) || !isset($condition['type'])) {
            return ['valid' => true, 'error' => null];
        }

        $type = $condition['type'];
        $value = $condition['value'] ?? null;
        $userId = $userId ?? Auth::id();

        switch ($type) {
            case Discount::CONDITION_FIRST_ORDER:
                $hasOrders = Order::where('user_id', $userId)
                    ->whereNotIn('status', [Order::STATUS_CANCELLED])
                    ->exists();
                if ($hasOrders) {
                    return ['valid' => false, 'error' => __('This discount is only available for your first order')];
                }
                break;

            case Discount::CONDITION_MIN_AMOUNT:
                if ($value && $subtotal < (float) $value) {
                    return ['valid' => false, 'error' => __('Minimum order amount for this discount is') . ' ' . number_format($value, 2) . ' ' . __('SAR')];
                }
                break;

            case Discount::CONDITION_MIN_QUANTITY:
                if ($value && $cartItemsCount < (int) $value) {
                    return ['valid' => false, 'error' => __('Minimum number of items for this discount is') . ' ' . (int) $value];
                }
                break;

            case Discount::CONDITION_NEW_CUSTOMER:
                $hasOrders = Order::where('user_id', $userId)
                    ->whereNotIn('status', [Order::STATUS_CANCELLED])
                    ->exists();
                if ($hasOrders) {
                    return ['valid' => false, 'error' => __('This discount is only available for new customers')];
                }
                break;
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Calculate discount amount based on discount model and subtotal
     *
     * @param Discount $discount Discount model
     * @param float $subtotal Order subtotal amount
     * @return float Calculated discount amount
     */
    public function calculateDiscountAmount(Discount $discount, float $subtotal): float
    {
        if ($discount->type === Discount::TYPE_PERCENTAGE) {
            return $subtotal * ($discount->value / 100);
        }

        // Fixed amount - cannot exceed subtotal
        return min($discount->value, $subtotal);
    }

    /**
     * Validate discount code, check conditions, and calculate discount amount
     *
     * @param string|null $code Discount code
     * @param float $subtotal Order subtotal
     * @param int|null $userId User ID for condition validation
     * @param int $cartItemsCount Number of items in cart
     * @return array ['discount' => Discount|null, 'amount' => float, 'error' => string|null]
     */
    public function processDiscount(?string $code, float $subtotal, ?int $userId = null, int $cartItemsCount = 0): array
    {
        $discount = $this->validateDiscountCode($code);

        if (!$discount) {
            return [
                'discount' => null,
                'amount' => 0,
                'error' => null,
            ];
        }

        // Validate conditions
        $conditionResult = $this->validateConditions($discount, $subtotal, $userId, $cartItemsCount);
        if (!$conditionResult['valid']) {
            return [
                'discount' => null,
                'amount' => 0,
                'error' => $conditionResult['error'],
            ];
        }

        $amount = $this->calculateDiscountAmount($discount, $subtotal);

        return [
            'discount' => $discount,
            'amount' => $amount,
            'error' => null,
        ];
    }
}

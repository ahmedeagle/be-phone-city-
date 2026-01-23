<?php

namespace App\Services;

use App\Models\Discount;

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
     * Validate discount code and calculate discount amount
     * Returns both the discount model and calculated amount
     *
     * @param string|null $code Discount code
     * @param float $subtotal Order subtotal
     * @return array ['discount' => Discount|null, 'amount' => float]
     */
    public function processDiscount(?string $code, float $subtotal): array
    {
        $discount = $this->validateDiscountCode($code);

        if (!$discount) {
            return [
                'discount' => null,
                'amount' => 0,
            ];
        }

        $amount = $this->calculateDiscountAmount($discount, $subtotal);

        return [
            'discount' => $discount,
            'amount' => $amount,
        ];
    }
}

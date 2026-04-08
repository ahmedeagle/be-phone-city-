<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\VipTier;
use Illuminate\Support\Facades\Cache;

class VipTierService
{
    /**
     * Fallback labels for the 'regular' (no-tier) state.
     */
    public const REGULAR_LABEL = ['ar' => 'عادي', 'en' => 'Regular'];

    /**
     * Get all active tiers from DB, cached for 10 minutes.
     * Ordered from highest sort_order desc so we match the best tier first.
     *
     * @return \Illuminate\Support\Collection<VipTier>
     */
    public function getActiveTiers(): \Illuminate\Support\Collection
    {
        return Cache::remember('vip_tiers_active', 600, function () {
            return VipTier::where('is_active', true)
                ->orderByDesc('sort_order')
                ->get();
        });
    }

    /**
     * Find a tier by key.
     */
    public function findTierByKey(string $key): ?VipTier
    {
        return $this->getActiveTiers()->firstWhere('key', $key);
    }

    /**
     * Calculate the best matching tier based on completed order count and total.
     * Both conditions (min_orders AND min_total) must be met.
     */
    public function calculateTier(int $ordersCount, float $ordersTotal): string
    {
        foreach ($this->getActiveTiers() as $tier) {
            if ($ordersCount >= $tier->min_orders && $ordersTotal >= $tier->min_total) {
                return $tier->key;
            }
        }

        return 'regular';
    }

    /**
     * Calculate the VIP discount amount for a given subtotal.
     * Applies the discount percentage capped by max_discount.
     */
    public function calculateVipDiscount(User $user, float $subtotal): array
    {
        $tierKey = $user->vip_tier ?? 'regular';

        if ($tierKey === 'regular') {
            return [
                'amount' => 0,
                'percentage' => 0,
                'max_discount' => 0,
                'tier' => 'regular',
                'tier_label_ar' => self::REGULAR_LABEL['ar'],
                'tier_label_en' => self::REGULAR_LABEL['en'],
            ];
        }

        $tier = $this->findTierByKey($tierKey);
        if (!$tier) {
            return [
                'amount' => 0,
                'percentage' => 0,
                'max_discount' => 0,
                'tier' => 'regular',
                'tier_label_ar' => self::REGULAR_LABEL['ar'],
                'tier_label_en' => self::REGULAR_LABEL['en'],
            ];
        }

        $rawDiscount = round($subtotal * ($tier->discount_percentage / 100), 2);
        $cappedDiscount = min($rawDiscount, (float) $tier->max_discount);

        return [
            'amount' => $cappedDiscount,
            'percentage' => (float) $tier->discount_percentage,
            'max_discount' => (float) $tier->max_discount,
            'tier' => $tier->key,
            'tier_label_ar' => $tier->name_ar,
            'tier_label_en' => $tier->name_en,
        ];
    }

    /**
     * Recalculate and update a user's VIP tier based on their completed orders.
     */
    public function recalculate(User $user): void
    {
        // Count all paid orders that are not cancelled
        $stats = $user->orders()
            ->where('payment_status', Order::PAYMENT_STATUS_PAID)
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->selectRaw('COUNT(*) as orders_count, COALESCE(SUM(total), 0) as orders_total')
            ->first();

        $ordersCount = (int) $stats->orders_count;
        $ordersTotal = (float) $stats->orders_total;
        $newTierKey = $this->calculateTier($ordersCount, $ordersTotal);

        $newDiscount = 0;
        $newMaxDiscount = 0;
        $tier = $this->findTierByKey($newTierKey);
        if ($tier) {
            $newDiscount = (float) $tier->discount_percentage;
            $newMaxDiscount = (float) $tier->max_discount;
        }

        $user->update([
            'completed_orders_count' => $ordersCount,
            'completed_orders_total' => $ordersTotal,
            'vip_tier' => $newTierKey,
            'vip_tier_discount' => $newDiscount,
            'vip_max_discount' => $newMaxDiscount,
            'vip_tier_updated_at' => now(),
        ]);
    }

    /**
     * Get the discount percentage for a user's current tier.
     */
    public function getTierDiscount(User $user): float
    {
        return (float) ($user->vip_tier_discount ?? 0);
    }

    /**
     * Get all tier definitions (for API / frontend display).
     */
    public function getAllTiers(): array
    {
        $tiers = [];

        foreach ($this->getActiveTiers()->sortBy('sort_order') as $tier) {
            $tiers[] = [
                'key' => $tier->key,
                'label_ar' => $tier->name_ar,
                'label_en' => $tier->name_en,
                'min_orders' => $tier->min_orders,
                'min_total' => (float) $tier->min_total,
                'discount_percentage' => (float) $tier->discount_percentage,
                'max_discount' => (float) $tier->max_discount,
            ];
        }

        return $tiers;
    }

    /**
     * Get progress info toward the next tier.
     */
    public function getNextTierProgress(User $user): ?array
    {
        $currentTierKey = $user->vip_tier ?? 'regular';
        $allTiers = $this->getActiveTiers()->sortBy('sort_order')->values();

        // Find next tier
        $nextTier = null;
        if ($currentTierKey === 'regular') {
            $nextTier = $allTiers->first();
        } else {
            $currentTier = $allTiers->firstWhere('key', $currentTierKey);
            if ($currentTier) {
                $nextTier = $allTiers->first(fn ($t) => $t->sort_order > $currentTier->sort_order);
            }
        }

        if (!$nextTier) {
            return null; // Already at max tier
        }

        $ordersCount = (int) ($user->completed_orders_count ?? 0);
        $ordersTotal = (float) ($user->completed_orders_total ?? 0);

        return [
            'next_tier' => $nextTier->key,
            'next_tier_label_ar' => $nextTier->name_ar,
            'next_tier_label_en' => $nextTier->name_en,
            'next_tier_discount' => (float) $nextTier->discount_percentage,
            'next_tier_max_discount' => (float) $nextTier->max_discount,
            'orders_needed' => max(0, $nextTier->min_orders - $ordersCount),
            'amount_needed' => max(0, (float) $nextTier->min_total - $ordersTotal),
            'orders_progress' => min(100, round(($ordersCount / max(1, $nextTier->min_orders)) * 100, 1)),
            'amount_progress' => min(100, round(($ordersTotal / max(1, (float) $nextTier->min_total)) * 100, 1)),
        ];
    }

    /**
     * Clear the cached tiers (call after admin updates tier settings).
     */
    public static function clearCache(): void
    {
        Cache::forget('vip_tiers_active');
    }
}

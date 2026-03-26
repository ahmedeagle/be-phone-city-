<?php

namespace App\Services\Shipping\Oto;

use App\Models\Order;

/**
 * Maps OTO shipment statuses to Order statuses
 */
class OtoStatusMapper
{
    /**
     * Map OTO shipment status to Order status
     * 
     * @param string $otoStatus Raw OTO status
     * @return string|null Order status constant or null if no mapping
     */
    public static function mapToOrderStatus(string $otoStatus): ?string
    {
        $normalized = self::normalize($otoStatus);

        // Map OTO statuses to Order statuses
        return match ($normalized) {
            // Created/Picked up -> Shipped
            'created', 'picked_up', 'in_transit', 'shipped', 'at_warehouse' => Order::STATUS_SHIPPED,
            
            // Out for delivery -> Delivery is in progress
            'out_for_delivery', 'on_delivery', 'in_delivery', 'delivering' => Order::STATUS_IN_PROGRESS,
            
            // Delivered -> Delivered
            'delivered', 'completed', 'success' => Order::STATUS_DELIVERED,
            
            // Cancelled/Failed -> keep current status (don't auto-cancel order)
            'cancelled', 'failed', 'returned', 'return_to_sender' => null,
            
            // Pending/Processing/Assigned -> keep as processing
            'pending', 'processing', 'awaiting_pickup', 'assigned_to_warehouse' => Order::STATUS_PROCESSING,
            
            default => null,
        };
    }

    /**
     * Status progression order — higher number = more advanced.
     * Sync should never regress an order to a lower level.
     */
    private const STATUS_WEIGHT = [
        Order::STATUS_PROCESSING   => 1,
        Order::STATUS_SHIPPED      => 2,
        Order::STATUS_IN_PROGRESS  => 3,
        Order::STATUS_DELIVERED    => 4,
        Order::STATUS_COMPLETED    => 5,
    ];

    /**
     * Check if transitioning from current to new status is a progression (not regression).
     */
    public static function isProgression(?string $currentStatus, ?string $newStatus): bool
    {
        if (!$currentStatus || !$newStatus) {
            return true;
        }

        $currentWeight = self::STATUS_WEIGHT[strtolower($currentStatus)] ?? 0;
        $newWeight     = self::STATUS_WEIGHT[strtolower($newStatus)] ?? 0;

        return $newWeight >= $currentWeight;
    }

    /**
     * Get badge color for OTO status display
     */
    public static function getBadgeColor(string $otoStatus): string
    {
        $normalized = self::normalize($otoStatus);

        return match ($normalized) {
            'delivered', 'completed', 'success' => 'success',
            'out_for_delivery', 'on_delivery', 'in_delivery', 'delivering' => 'warning',
            'created', 'picked_up', 'in_transit', 'shipped', 'at_warehouse' => 'info',
            'cancelled', 'failed', 'returned', 'return_to_sender' => 'danger',
            'pending', 'processing', 'awaiting_pickup', 'assigned_to_warehouse' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status label
     */
    public static function getStatusLabel(string $otoStatus): string
    {
        $normalized = self::normalize($otoStatus);

        return match ($normalized) {
            'created' => 'تم إنشاء الشحنة',
            'picked_up' => 'تم استلام الشحنة',
            'in_transit' => 'في الطريق',
            'shipped' => 'تم الشحن',
            'at_warehouse' => 'في المستودع',
            'assigned_to_warehouse' => 'جاري التجهيز في المستودع',
            'out_for_delivery' => 'المندوب في الطريق إليك',
            'on_delivery' => 'جاري التوصيل',
            'in_delivery' => 'جاري التوصيل',
            'delivering' => 'جاري التوصيل',
            'delivered' => 'تم التوصيل',
            'completed' => 'مكتمل',
            'success' => 'تم التوصيل بنجاح',
            'cancelled' => 'ملغي',
            'failed' => 'فشل التوصيل',
            'returned' => 'مرتجع',
            'return_to_sender' => 'مرتجع للمرسل',
            'pending' => 'بانتظار المعالجة',
            'processing' => 'جاري تجهيز الطلب',
            'awaiting_pickup' => 'بانتظار استلام المندوب',
            default => ucfirst(str_replace('_', ' ', $otoStatus)),
        };
    }

    /**
     * Check if status indicates shipment is in transit
     */
    /**
     * Normalize OTO status: handle camelCase, spaces, dashes → snake_case lowercase
     */
    private static function normalize(string $status): string
    {
        // Convert camelCase to snake_case first (e.g. assignedToWarehouse → assigned_to_warehouse)
        $snaked = preg_replace('/([a-z])([A-Z])/', '$1_$2', $status);
        // Replace spaces/dashes with underscore, then lowercase
        return strtolower(str_replace([' ', '-'], '_', $snaked));
    }

    public static function isInTransit(string $otoStatus): bool
    {
        $normalized = self::normalize($otoStatus);
        
        return in_array($normalized, [
            'picked_up', 'in_transit', 'shipped', 'at_warehouse', 
            'out_for_delivery', 'on_delivery', 'in_delivery', 'delivering'
        ]);
    }

    /**
     * Check if status indicates shipment is complete
     */
    public static function isComplete(string $otoStatus): bool
    {
        $normalized = self::normalize($otoStatus);
        
        return in_array($normalized, ['delivered', 'completed', 'success']);
    }

    /**
     * Check if status indicates failure/cancellation
     */
    public static function isFailed(string $otoStatus): bool
    {
        $normalized = self::normalize($otoStatus);
        
        return in_array($normalized, ['cancelled', 'failed', 'returned', 'return_to_sender', 'delivery_failed', 'attempted_delivery']);
    }

    /**
     * Check if status indicates a temporary delivery failure (can be retried)
     */
    public static function isTemporaryFailure(string $otoStatus): bool
    {
        $normalized = self::normalize($otoStatus);

        return in_array($normalized, ['failed', 'delivery_failed', 'attempted_delivery']);
    }

    /**
     * Check if status indicates a permanent failure (order won't be delivered)
     */
    public static function isPermanentFailure(string $otoStatus): bool
    {
        $normalized = self::normalize($otoStatus);

        return in_array($normalized, ['cancelled', 'returned', 'return_to_sender']);
    }

    /**
     * Get failure label for display
     */
    public static function getFailureLabel(string $otoStatus): string
    {
        $normalized = self::normalize($otoStatus);

        return match ($normalized) {
            'failed', 'delivery_failed', 'attempted_delivery' => 'محاولة توصيل فاشلة',
            'cancelled' => 'تم إلغاء الشحنة',
            'returned' => 'مرتجع',
            'return_to_sender' => 'مرتجع للمرسل',
            default => 'فشل التوصيل',
        };
    }
}

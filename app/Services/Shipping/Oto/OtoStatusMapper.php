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
            'picked_up' => 'تم الاستلام من المخزن',
            'in_transit' => 'في الطريق',
            'shipped' => 'تم الشحن',
            'at_warehouse' => 'في المستودع',
            'assigned_to_warehouse' => 'تم التسليم للمستودع',
            'out_for_delivery' => 'خارج للتوصيل',
            'on_delivery' => 'قيد التوصيل',
            'in_delivery' => 'قيد التوصيل',
            'delivering' => 'قيد التوصيل',
            'delivered' => 'تم التسليم',
            'completed' => 'مكتمل',
            'success' => 'نجح التسليم',
            'cancelled' => 'ملغي',
            'failed' => 'فشل',
            'returned' => 'مرتجع',
            'return_to_sender' => 'مرتجع للمرسل',
            'pending' => 'قيد الانتظار',
            'processing' => 'قيد المعالجة',
            'awaiting_pickup' => 'بانتظار الاستلام',
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
        
        return in_array($normalized, ['cancelled', 'failed', 'returned', 'return_to_sender']);
    }
}

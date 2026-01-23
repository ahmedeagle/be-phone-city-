<?php

namespace App\Services\Shipping\Oto\Exceptions;

use App\Models\Order;
use Exception;

/**
 * Exception thrown when order validation fails for shipment creation
 */
class OtoValidationException extends Exception
{
    /**
     * Order is not eligible for shipment
     */
    public static function notEligible(Order $order, string $reason): self
    {
        return new self(
            "Order #{$order->order_number} is not eligible for shipment: {$reason}"
        );
    }

    /**
     * Order already has an active shipment
     */
    public static function alreadyShipped(Order $order): self
    {
        return new self(
            "Order #{$order->order_number} already has an active shipment (tracking: {$order->tracking_number})"
        );
    }

    /**
     * Order status is not valid for shipment
     */
    public static function invalidStatus(Order $order): self
    {
        return new self(
            "Order #{$order->order_number} must be in 'processing' status. Current status: {$order->status}"
        );
    }

    /**
     * Order is not for home delivery
     */
    public static function notHomeDelivery(Order $order): self
    {
        return new self(
            "Order #{$order->order_number} is not set for home delivery. Current method: {$order->delivery_method}"
        );
    }

    /**
     * Order location/address is invalid
     */
    public static function invalidLocation(Order $order, string $reason): self
    {
        return new self(
            "Order #{$order->order_number} has invalid delivery location: {$reason}"
        );
    }

    /**
     * Missing required order data
     */
    public static function missingData(Order $order, string $field): self
    {
        return new self(
            "Order #{$order->order_number} is missing required field: {$field}"
        );
    }

    /**
     * Order does not have OTO Order ID
     */
    public static function missingOtoOrderId(Order $order): self
    {
        return new self(
            "Order #{$order->order_number} does not have OTO Order ID. " .
            "Please create the order in OTO Dashboard first and add the Order ID."
    );
    }
}

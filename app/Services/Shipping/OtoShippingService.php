<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Services\Shipping\Oto\Dto\OtoShipmentDto;
use App\Services\Shipping\Oto\Dto\OtoShipmentStatusDto;
use App\Services\Shipping\Oto\Exceptions\OtoApiException;
use App\Services\Shipping\Oto\Exceptions\OtoConfigurationException;
use App\Services\Shipping\Oto\Exceptions\OtoValidationException;
use App\Services\Shipping\Oto\OtoHttpClient;
use App\Services\Shipping\Oto\OtoStatusMapper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing OTO shipment integration
 */
class OtoShippingService
{
    protected OtoHttpClient $client;

    public function __construct(OtoHttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new order in OTO without creating a shipment
     *
     * @throws OtoValidationException
     * @throws OtoConfigurationException
     */
    public function createOtoOrder(Order $order, ?string $notes = null): string
    {
        // Validate order eligibility
        $this->validateOrderForShipment($order);

        // Load required relationships
        $order->load(['location.city', 'items.product', 'items.productOption', 'user']);

        return DB::transaction(function () use ($order, $notes) {
            // Step 1: Create order in OTO
            $orderPayload = $this->buildOrderPayload($order, $notes);
            $orderPayload['createShipment'] = false; // explicitly disable auto-shipment

            Log::info('Creating OTO order (no shipment)', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            $orderResponse = $this->client->createOrder($orderPayload);

            Log::info('OTO order API response', [
                'response' => $orderResponse,
                'order_id' => $order->id,
            ]);

            // Extract OTO order ID
            $otoOrderId = $orderResponse['otoId'] ??
                         $orderResponse['data']['otoId'] ??
                         $orderResponse['id'] ??
                         $orderResponse['orderId'] ??
                         $orderResponse['order_id'] ??
                         $orderResponse['data']['id'] ??
                         null;

            if (!$otoOrderId) {
                Log::error('No order ID in OTO response', ['full_response' => $orderResponse]);
                throw new \RuntimeException('Failed to get order ID from OTO API response. Check logs for details.');
            }

            // Store OTO order ID
            $order->update(['oto_order_id' => $otoOrderId]);

            Log::info('OTO order created', [
                'order_id' => $order->id,
                'oto_order_id' => $otoOrderId,
            ]);

            return (string) $otoOrderId;
        });
    }

    /**
     * Check delivery options for an order
     */
    public function checkDeliveryOptions(Order $order): array
    {
        if (!$order->oto_order_id) {
            throw new \RuntimeException('Order must be created in OTO first.');
        }

        $payload = [
            'orderId' => $order->order_number,
        ];

        Log::info('Checking OTO delivery options', [
            'order_id' => $order->id,
            'oto_order_id' => $order->oto_order_id,
        ]);

        $response = $this->client->checkDelivery($payload);

        return $response['deliveryOptions'] ??
               $response['data']['deliveryOptions'] ??
               $response['options'] ??
               $response['shipments'] ??
               [];
    }

    /**
     * Create order and shipment in OTO automatically (one-step process)
     * This is used when order is paid and should be shipped automatically
     *
     * @throws OtoValidationException
     * @throws OtoConfigurationException
     */
    public function createOrderAndShipment(Order $order, ?string $notes = null, ?int $deliveryOptionId = null): OtoShipmentDto
    {
        // Validate order eligibility
        $this->validateOrderForShipment($order);

        // Load required relationships
        $order->load(['location.city', 'items.product', 'items.productOption', 'user']);

        return DB::transaction(function () use ($order, $notes, $deliveryOptionId) {
            // Step 1: Create order in OTO with auto-shipment enabled
            $orderPayload = $this->buildOrderPayload($order, $notes);
            $orderPayload['createShipment'] = true; // Enable auto-shipment

            Log::info('Creating OTO order with automatic shipment', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            $orderResponse = $this->client->createOrder($orderPayload);

            Log::info('OTO order API response', [
                'response' => $orderResponse,
                'order_id' => $order->id,
            ]);

            // Extract OTO order ID
            $otoOrderId = $orderResponse['otoId'] ??
                         $orderResponse['data']['otoId'] ??
                         $orderResponse['id'] ??
                         $orderResponse['orderId'] ??
                         $orderResponse['order_id'] ??
                         $orderResponse['data']['id'] ??
                         null;

            if (!$otoOrderId) {
                Log::error('No order ID in OTO response', ['full_response' => $orderResponse]);
                throw new \RuntimeException('Failed to get order ID from OTO API response. Check logs for details.');
            }

            // Store OTO order ID
            $order->update(['oto_order_id' => $otoOrderId]);

            // Step 2: Check if shipment was created automatically or needs to be created manually
            $shipmentDto = null;

            // Try to extract shipment info from order response
            if (isset($orderResponse['shipment']) || isset($orderResponse['data']['shipment'])) {
                $shipmentData = $orderResponse['shipment'] ?? $orderResponse['data']['shipment'];
                $shipmentDto = OtoShipmentDto::fromApiResponse($shipmentData);
            } elseif (isset($orderResponse['trackingNumber']) || isset($orderResponse['data']['trackingNumber'])) {
                // Shipment info might be at root level
                $shipmentDto = OtoShipmentDto::fromApiResponse($orderResponse);
            }

            // If no shipment in response, create it manually
            if (!$shipmentDto || !$shipmentDto->isValid()) {
                Log::info('No shipment in order response, creating shipment manually', [
                    'order_id' => $order->id,
                    'oto_order_id' => $otoOrderId,
                ]);

                $shipmentPayload = [
                    'orderId' => $order->order_number,
                ];

                if ($deliveryOptionId) {
                    $shipmentPayload['deliveryOptionId'] = $deliveryOptionId;
                }

                $shipmentResponse = $this->client->createShipment($shipmentPayload);
                $shipmentDto = OtoShipmentDto::fromApiResponse($shipmentResponse);
            }

            if (!$shipmentDto->isValid()) {
                Log::error('Invalid OTO shipment response', [
                    'order_id' => $order->id,
                    'response' => $orderResponse,
                ]);
                throw new \RuntimeException('Invalid shipment response from OTO API');
            }

            // Update order with shipment data
            $this->updateOrderWithShipment($order, $shipmentDto);

            $logData = [
                'order_id' => $order->id,
                'oto_order_id' => $otoOrderId,
            ];

            if ($shipmentDto->hasTrackingInfo()) {
                $logData['tracking_number'] = $shipmentDto->trackingNumber;
                $logData['shipment_reference'] = $shipmentDto->shipmentReference;
                Log::info('OTO order and shipment created successfully', $logData);
            } else {
                Log::info('OTO order created, shipment request received (tracking pending)', $logData);
            }

            return $shipmentDto;
        });
    }

    /**
     * Create a shipment for an existing OTO order
     *
     * @throws OtoValidationException
     * @throws OtoConfigurationException
     */
    public function createShipment(Order $order, ?int $deliveryOptionId = null): OtoShipmentDto
    {
        if (!$order->oto_order_id) {
            throw new \RuntimeException('Order must be created in OTO first.');
        }

        return DB::transaction(function () use ($order, $deliveryOptionId) {
            // Step: Create shipment for the order
            $shipmentPayload = [
                'orderId' => $order->order_number,
            ];

            if ($deliveryOptionId) {
                $shipmentPayload['deliveryOptionId'] = $deliveryOptionId;
            }

            Log::info('Creating OTO shipment', [
                'order_id' => $order->id,
                'oto_order_id' => $order->oto_order_id,
                'delivery_option_id' => $deliveryOptionId,
            ]);

            $shipmentResponse = $this->client->createShipment($shipmentPayload);

            // Create DTO from response
            $shipmentDto = OtoShipmentDto::fromApiResponse($shipmentResponse);

            if (!$shipmentDto->isValid()) {
                Log::error('Invalid OTO shipment response', [
                    'order_id' => $order->id,
                    'response' => $shipmentResponse,
                ]);
                throw new \RuntimeException('Invalid shipment response from OTO API');
            }

            // Update order with shipment data
            $this->updateOrderWithShipment($order, $shipmentDto);

            $logData = [
                'order_id' => $order->id,
                'oto_order_id' => $order->oto_order_id,
            ];

            if ($shipmentDto->hasTrackingInfo()) {
                $logData['tracking_number'] = $shipmentDto->trackingNumber;
                $logData['shipment_reference'] = $shipmentDto->shipmentReference;
                Log::info('OTO shipment created successfully', $logData);
            } else {
                Log::info('OTO shipment request received (tracking pending)', $logData);
            }

            return $shipmentDto;
        });
    }

    /**
     * Get shipment status for an order
     */
    public function getShipmentStatus(Order $order): OtoShipmentStatusDto
    {
        if (empty($order->tracking_number)) {
            throw new \InvalidArgumentException(
                "Order #{$order->order_number} does not have a tracking number"
            );
        }

        Log::info('Fetching OTO shipment status', [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
        ]);

        $response = $this->client->getShipmentStatus($order->tracking_number);

        return OtoShipmentStatusDto::fromApiResponse($response);
    }

    /**
     * Update order status based on shipment status
     */
    public function updateShipmentStatus(Order $order, OtoShipmentStatusDto $statusDto): void
    {
        // Check if status has changed
        if (!$statusDto->isDifferentFrom($order->tracking_status)) {
            Log::debug('OTO shipment status unchanged', [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'status' => $statusDto->status,
            ]);
            return;
        }

        DB::transaction(function () use ($order, $statusDto) {
            // Map OTO status to Order status
            $newOrderStatus = OtoStatusMapper::mapToOrderStatus($statusDto->status);

            $updates = [
                'tracking_status' => $statusDto->status,
                'shipping_status_updated_at' => $statusDto->updatedAt,
                'shipping_payload' => $statusDto->rawPayload,
            ];

            // Update ETA if available
            if ($statusDto->eta) {
                $updates['shipping_eta'] = $statusDto->eta;
            }

            // Update order status if mapped
            if ($newOrderStatus) {
                $updates['status'] = $newOrderStatus;

                // Auto-complete on delivery if configured
                if ($newOrderStatus === Order::STATUS_DELIVERED
                    && config('services.oto.auto_complete_on_delivered', false)) {
                    $updates['status'] = Order::STATUS_COMPLETED;
                }
            }

            $order->update($updates);

            Log::info('Order shipment status updated', [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'oto_status' => $statusDto->status,
                'order_status' => $updates['status'] ?? 'unchanged',
            ]);
        });
    }

    /**
     * Sync shipment status from OTO API
     */
    public function syncShipmentStatus(Order $order): void
    {
        $statusDto = $this->getShipmentStatus($order);
        $this->updateShipmentStatus($order, $statusDto);
    }

    /**
     * Cancel a shipment
     *
     * @throws \InvalidArgumentException
     * @throws OtoApiException
     */
    public function cancelShipment(Order $order, ?string $reason = null): array
    {
        if (empty($order->tracking_number)) {
            throw new \InvalidArgumentException(
                "Order #{$order->order_number} does not have a tracking number to cancel"
            );
        }

        Log::info('Cancelling OTO shipment', [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
            'reason' => $reason,
        ]);

        $response = $this->client->cancelShipment($order->tracking_number, $reason);

        // Update order status
        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'tracking_status' => 'cancelled',
            'shipping_status_updated_at' => now(),
        ]);

        Log::info('OTO shipment cancelled successfully', [
            'order_id' => $order->id,
            'tracking_number' => $order->tracking_number,
        ]);

        return $response;
    }

    /**
     * Cancel order in OTO
     *
     * @throws \InvalidArgumentException
     * @throws OtoApiException
     */
    public function cancelOrder(Order $order, ?string $reason = null): array
    {
        if (empty($order->oto_order_id)) {
            throw new \InvalidArgumentException(
                "Order #{$order->order_number} does not have an OTO order ID to cancel"
            );
        }

        Log::info('Cancelling OTO order', [
            'order_id' => $order->id,
            'oto_order_id' => $order->oto_order_id,
            'reason' => $reason,
        ]);

        try {
            $response = $this->client->cancelOrder($order->oto_order_id, $reason);

            // Update order status
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'shipping_status_updated_at' => now(),
            ]);

            Log::info('OTO order cancelled successfully', [
                'order_id' => $order->id,
                'oto_order_id' => $order->oto_order_id,
            ]);

            return $response;
        } catch (OtoApiException $e) {
            $statusCode = $e->getCode();

            // If cancellation fails due to permissions (403) or not found (404),
            // log warning but still update local status
            if ($statusCode === 403 || $statusCode === 404) {
                Log::warning('OTO order cancellation failed - API endpoint not available or permission denied', [
                    'order_id' => $order->id,
                    'oto_order_id' => $order->oto_order_id,
                    'status_code' => $statusCode,
                    'error' => $e->getMessage(),
                ]);

                // Still update local status to cancelled
                $order->update([
                    'status' => Order::STATUS_CANCELLED,
                    'shipping_status_updated_at' => now(),
                ]);

                // Re-throw with helpful message
                $helpMessage = $statusCode === 403
                    ? 'Your OTO account does not have permission to cancel orders via API. Please cancel the order manually in OTO dashboard (https://app.tryoto.com) or contact OTO support to enable this permission.'
                    : 'The cancel order endpoint is not available. Please cancel the order manually in OTO dashboard (https://app.tryoto.com).';

                throw new OtoApiException(
                    "Order cancellation failed: {$helpMessage}",
                    $statusCode
                );
            }

            // For other errors, re-throw as-is
            throw $e;
        }
    }

    /**
     * Validate order is eligible for shipment creation
     *
     * @throws OtoValidationException
     */
    protected function validateOrderForShipment(Order $order): void
    {
        // Check status
        if ($order->status !== Order::STATUS_PROCESSING) {
            throw OtoValidationException::invalidStatus($order);
        }

        // Check delivery method
        if ($order->delivery_method !== Order::DELIVERY_HOME) {
            throw OtoValidationException::notHomeDelivery($order);
        }

        // Check if already shipped
        if ($order->hasActiveShipment()) {
            throw OtoValidationException::alreadyShipped($order);
        }

        // Check location exists
        if (!$order->location) {
            throw OtoValidationException::invalidLocation($order, 'Location not set');
        }

        // Validate location has required fields
        $this->validateLocation($order);
    }

    /**
     * Validate location has required delivery information
     *
     * @throws OtoValidationException
     */
    protected function validateLocation(Order $order): void
    {
        $location = $order->location;

        if (empty($location->first_name) && empty($location->last_name)) {
            throw OtoValidationException::invalidLocation($order, 'Missing recipient name');
        }

        if (empty($location->phone)) {
            throw OtoValidationException::invalidLocation($order, 'Missing recipient phone');
        }

        if (empty($location->street_address)) {
            throw OtoValidationException::invalidLocation($order, 'Missing street address');
        }

        if (empty($location->city_id)) {
            throw OtoValidationException::invalidLocation($order, 'Missing city');
        }
    }

    /**
     * Build order payload for OTO API (based on official docs)
     */
    protected function buildOrderPayload(Order $order, ?string $notes = null): array
    {
        $location = $order->location;
        $pickupConfig = config('services.oto.pickup');

        // Validate pickup configuration
        if (empty($pickupConfig['phone'])) {
            throw OtoConfigurationException::missingPickupConfig('PHONE');
        }

        $payload = [
            // Order ID from your system (required)
            'orderId' => $order->order_number,

            // Create shipment automatically
            'createShipment' => true,

            // Payment information
            'payment_method' => 'paid',
            'amount' => (float) $order->total,
            'amount_due' => 0,
            'currency' => 'SAR',

            // Sender information (your warehouse/store)
            // 'senderInformation' => [
            //     'senderId' => 1,
            //     'senderFullName' => 'City Phones',
            //     'senderMobile' => '99999999',
            //     'senderCountry' => 'SA',
            //     'senderCity' => 'test city',
            //     'senderAddressLine1' => 'test address',
            //     'senderEmail' => 'test@test.com',
            // ],

            // Receiver information (customer)
            'customer' => [
                'name' => trim(($location->first_name ?? '') . ' ' . ($location->last_name ?? '')),
                'address' => $location->street_address,
                'city' => $location->city->name ?? $location->city->name_ar ?? '',
                'mobile' => $location->phone,
                'email' => $location->email ?? $order->user->email ?? '',
                'country' => 'SA',
            ],
        ];

        // Add notes if provided
        if ($notes) {
            $payload['notes'] = $notes;
        } elseif ($order->notes) {
            $payload['notes'] = $order->notes;
        }

        // Add reference for tracking
        if ($order->order_number) {
            $payload['ref1'] = $order->order_number;
        }

        $payload['items'] = $this->buildOrderItems($order);


        return $payload;
    }

    /**
     * Build order items array for OTO API
     */
    protected function buildOrderItems(Order $order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->product->name ?? 'Product',
                'sku' => $item->product->sku ?? $item->product->id,
                'quantity' => $item->quantity,
                'price' => (float) $item->unit_price,
                'total' => (float) ($item->unit_price * $item->quantity),
                'description' => $item->productOption?->name ?? '',
            ];
        }

        return $items;
    }

    /**
     * Get payment method
     */
    protected function getPaymentMethod(Order $order): string
    {
        if (!$order->paymentMethod) {
            return 'prepaid';
        }

        $gateway = $order->paymentMethod->gateway ?? '';

        if (in_array($gateway, ['cash', 'cod'])) {
            return 'cod';
        }

        return 'prepaid';
    }

    /**
     * Get COD (Cash on Delivery) amount
     */
    protected function getCodAmount(Order $order): float
    {
        if ($this->getPaymentMethod($order) === 'cod') {
            return (float) $order->total;
        }

        return 0.0;
    }

    /**
     * Update order with shipment information
     */
    protected function updateOrderWithShipment(Order $order, OtoShipmentDto $shipmentDto): void
    {
        $updates = [
            'shipping_provider' => 'OTO',
            'shipping_reference' => $shipmentDto->shipmentReference,
            'tracking_number' => $shipmentDto->trackingNumber,
            'tracking_url' => $shipmentDto->trackingUrl,
            'tracking_status' => $shipmentDto->status,
            'shipping_eta' => $shipmentDto->eta,
            'shipping_status_updated_at' => now(),
            'shipping_payload' => $shipmentDto->rawPayload,
        ];

        // Only update status to shipped if we have a tracking number
        if ($shipmentDto->hasTrackingInfo()) {
            $updates['status'] = Order::STATUS_SHIPPED;
        }

        $order->update($updates);
    }
}

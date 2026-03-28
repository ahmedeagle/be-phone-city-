<?php

namespace App\Services\Shipping;

use App\Models\Branch;
use App\Models\Order;
use App\Services\Shipping\Oto\Dto\OtoShipmentDto;
use App\Services\Shipping\Oto\Dto\OtoShipmentStatusDto;
use App\Services\Shipping\Oto\Exceptions\OtoApiException;
use App\Services\Shipping\Oto\Exceptions\OtoConfigurationException;
use App\Services\Shipping\Oto\Exceptions\OtoValidationException;
use App\Services\Shipping\Oto\OtoHttpClient;
use App\Services\Shipping\Oto\OtoStatusMapper;
use App\Services\NotificationService;
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
    public function createOrderAndShipment(Order $order, ?string $notes = null, ?int $deliveryOptionId = null, ?Branch $branch = null): OtoShipmentDto
    {
        // Validate order eligibility
        $this->validateOrderForShipment($order);

        // Load required relationships
        $order->load(['location.city', 'items.product', 'items.productOption', 'user']);

        return DB::transaction(function () use ($order, $notes, $deliveryOptionId, $branch) {
            // Step 1: Create order in OTO with auto-shipment enabled
            $orderPayload = $this->buildOrderPayload($order, $notes, $branch);

            // If we have a specific delivery option, we create order first, then shipment
            // Otherwise, we let OTO handle it automatically in one step
            $orderPayload['createShipment'] = $deliveryOptionId ? false : true;

            Log::info('OTO: Creating order and requesting auto-shipment', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'auto_ship' => $orderPayload['createShipment'],
                'payload' => $orderPayload,
            ]);

            $orderResponse = $this->client->createOrder($orderPayload);

            Log::info('OTO order API response', [
                'response' => $orderResponse,
                'order_id' => $order->id,
            ]);

            // Extract OTO order ID (otoId from docs)
            $otoOrderId = $orderResponse['otoId'] ??
                         $orderResponse['id'] ??
                         $orderResponse['data']['otoId'] ??
                         null;

            if (!$otoOrderId) {
                Log::error('No order ID in OTO response', ['full_response' => $orderResponse]);
                throw new \RuntimeException('Failed to get order ID from OTO API response.');
            }

            // Store OTO order ID and set provider
            $order->update([
                'oto_order_id' => $otoOrderId,
                'shipping_provider' => 'OTO'
            ]);

            // Step 2: ONLY call createShipment if we have a specific deliveryOptionId
            if ($deliveryOptionId) {
                Log::info('OTO: Manually requesting specific courier', [
                    'order_id' => $order->id,
                    'delivery_option_id' => $deliveryOptionId
                ]);

                $this->client->createShipment([
                    'orderId' => $order->order_number,
                    'deliveryOptionId' => $deliveryOptionId
                ]);
            }

            // Step 3: Immediate Sync to fetch tracking number
            // Since OTO processes tracking in background, we wait a moment
            try {
                usleep(500000); // 0.5 seconds
                $this->syncShipmentStatus($order);
            } catch (\Exception $e) {
                Log::debug('OTO: Initial sync pending (OTO still processing carrier assignment)');
            }

            // Refresh order to get the latest state from sync
            $order->refresh();

            // Return a DTO representing the current state
            return new OtoShipmentDto(
                shipmentReference: $order->shipping_reference ?? '',
                trackingNumber: $order->tracking_number ?? '',
                trackingUrl: $order->tracking_url ?? '',
                status: $order->tracking_status ?? 'pending',
                eta: $order->shipping_eta,
                rawPayload: $order->shipping_payload
            );
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
     * Uses orderStatus endpoint first to get tracking number, then trackShipment for details
     */
    public function getShipmentStatus(Order $order): OtoShipmentStatusDto
    {
        Log::info('Fetching OTO shipment status', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'oto_order_id' => $order->oto_order_id,
            'current_tracking_number' => $order->tracking_number,
        ]);

        // Step 1: Get order status using orderStatus endpoint
        // documentation says we should use orderId (our order_number or otoId)
        $orderStatusResponse = null;
        $orderIdUsed = null;

        try {
            // Try with order_number first as OTO seems to prefer the ID we provided
            $orderIdUsed = $order->order_number;

            Log::info('OTO: Calling orderStatus with order_number', [
                'order_id' => $order->id,
                'using_id' => $orderIdUsed,
            ]);

            $orderStatusResponse = $this->client->getOrderStatus($orderIdUsed);
        } catch (\Exception $e) {
            // If order_number fails and we have oto_order_id, try that
            if ($order->oto_order_id) {
                Log::info('OTO: orderStatus with order_number failed, trying oto_order_id', [
                    'order_id' => $order->id,
                    'oto_order_id' => $order->oto_order_id,
                    'error' => $e->getMessage(),
                ]);
                try {
                    $orderIdUsed = $order->oto_order_id;
                    $orderStatusResponse = $this->client->getOrderStatus($order->oto_order_id);
                } catch (\Exception $e2) {
                    Log::error('OTO: Both order_number and oto_order_id failed for orderStatus', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'oto_order_id' => $order->oto_order_id,
                        'error' => $e2->getMessage(),
                    ]);
                    throw $e2;
                }
            } else {
                throw $e;
            }
        }

        Log::info('OTO: orderStatus response received', [
            'order_id' => $order->id,
            'response' => $orderStatusResponse,
        ]);

        // Extract tracking information from orderStatus response
        // dcTrackingNumber is the carrier's tracking number
        $dcTrackingNumber = $orderStatusResponse['dcTrackingNumber'] ?? null;
        $deliveryCompany = $orderStatusResponse['deliveryCompany'] ?? null;
        $shipmentId = $orderStatusResponse['shipmentId'] ?? null;
        $printAWBURL = $orderStatusResponse['printAWBURL'] ?? null;

        // If we have tracking number, get detailed tracking info
        $trackShipmentResponse = null;
        if ($dcTrackingNumber && $deliveryCompany) {
            try {
                Log::info('OTO: Fetching detailed tracking with trackShipment', [
                    'order_id' => $order->id,
                    'tracking_number' => $dcTrackingNumber,
                    'delivery_company' => $deliveryCompany,
                ]);

                $trackShipmentResponse = $this->client->trackShipment(
                    $dcTrackingNumber,
                    $deliveryCompany,
                    statusHistory: true
                );

                Log::info('OTO: trackShipment response received', [
                    'order_id' => $order->id,
                    'response' => $trackShipmentResponse,
                ]);
            } catch (\Exception $e) {
                Log::warning('OTO: trackShipment failed, using orderStatus data only', [
                    'order_id' => $order->id,
                    'tracking_number' => $dcTrackingNumber,
                    'error' => $e->getMessage(),
                ]);
                // Continue with orderStatus data only
            }
        } else {
            Log::warning('OTO: No tracking number or delivery company in orderStatus response yet', [
                'order_id' => $order->id,
                'order_status_response' => $orderStatusResponse,
            ]);
        }

        // Combine data from both responses
        $combinedData = $this->combineTrackingData($orderStatusResponse, $trackShipmentResponse, $order);

        Log::info('OTO shipment status response (combined)', [
            'response' => $combinedData,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return OtoShipmentStatusDto::fromApiResponse($combinedData);
    }

    /**
     * Combine data from orderStatus and trackShipment responses
     */
    protected function combineTrackingData(array $orderStatusResponse, ?array $trackShipmentResponse, Order $order): array
    {
        // Start with orderStatus data
        $combined = [
            'orderId' => $orderStatusResponse['orderId'] ?? $order->order_number,
            'status' => $orderStatusResponse['status'] ?? 'unknown',
            'deliveryCompany' => $orderStatusResponse['deliveryCompany'] ?? null,
            'shipmentId' => $orderStatusResponse['shipmentId'] ?? null,
            'dcTrackingNumber' => $orderStatusResponse['dcTrackingNumber'] ?? null,
            'date' => $orderStatusResponse['date'] ?? null,
            'note' => $orderStatusResponse['note'] ?? null,
            'deliverySlotDate' => $orderStatusResponse['deliverySlotDate'] ?? null,
            'printAWBURL' => $orderStatusResponse['printAWBURL'] ?? null,
        ];

        // Add tracking number for DTO compatibility
        $combined['tracking_number'] = $combined['dcTrackingNumber'] ?? $order->tracking_number;

        // If we have trackShipment response, merge detailed data
        if ($trackShipmentResponse && isset($trackShipmentResponse['items']) && !empty($trackShipmentResponse['items'])) {
            $item = $trackShipmentResponse['items'][0]; // Get first item

            // Use the most recent status from trackShipment if available
            if (isset($item['otoStatus'])) {
                $combined['status'] = $item['otoStatus'];
            }

            // Add tracking URL
            if (isset($trackShipmentResponse['trackingUrl'])) {
                $combined['trackingUrl'] = $trackShipmentResponse['trackingUrl'];
            }

            // Add status history/events
            if (isset($item['history']) && is_array($item['history'])) {
                $combined['events'] = $item['history'];
                $combined['tracking_events'] = $item['history'];

                // Get latest event for status description
                $latestEvent = end($item['history']);
                if ($latestEvent && isset($latestEvent['dcDescription'])) {
                    $combined['status_description'] = $latestEvent['dcDescription'];
                }

                // Get latest update date
                if ($latestEvent && isset($latestEvent['dcUpdateDate'])) {
                    $combined['updated_at'] = $latestEvent['dcUpdateDate'];
                }
            }

            // Add current location if available
            if (isset($item['currentLocation'])) {
                $combined['currentLocation'] = $item['currentLocation'];
            }
        }

        // Add raw payloads for debugging
        $combined['raw_order_status'] = $orderStatusResponse;
        if ($trackShipmentResponse) {
            $combined['raw_track_shipment'] = $trackShipmentResponse;
        }

        return $combined;
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

        $previousTrackingStatus = $order->tracking_status;

        DB::transaction(function () use ($order, $statusDto) {
            // Map OTO status to Order status
            $newOrderStatus = OtoStatusMapper::mapToOrderStatus($statusDto->status);

            $updates = [
                'tracking_status' => OtoStatusMapper::normalize($statusDto->status),
                'shipping_status_updated_at' => $statusDto->updatedAt,
                'shipping_payload' => $statusDto->rawPayload,
            ];

            // Update tracking number if we got it from API but order doesn't have it
            if (!empty($statusDto->trackingNumber) && empty($order->tracking_number)) {
                $updates['tracking_number'] = $statusDto->trackingNumber;
                Log::info('OTO: Updating order with tracking number from API', [
                    'order_id' => $order->id,
                    'tracking_number' => $statusDto->trackingNumber,
                ]);
            }

            // Update tracking URL if available
            $rawPayload = $statusDto->rawPayload;
            if (isset($rawPayload['trackingUrl']) && empty($order->tracking_url)) {
                $updates['tracking_url'] = $rawPayload['trackingUrl'];
            }
            if (isset($rawPayload['printAWBURL']) && empty($order->tracking_url)) {
                $updates['tracking_url'] = $rawPayload['printAWBURL'];
            }

            // Update shipping reference if available
            if (isset($rawPayload['shipmentId']) && empty($order->shipping_reference)) {
                $updates['shipping_reference'] = $rawPayload['shipmentId'];
            }

            // Update ETA if available
            if ($statusDto->eta) {
                $updates['shipping_eta'] = $statusDto->eta;
            }

            // Update order status if mapped AND it's a progression (never regress)
            if ($newOrderStatus && OtoStatusMapper::isProgression($order->status, $newOrderStatus)) {
                $updates['status'] = $newOrderStatus;

                // Auto-complete on delivery if configured
                if ($newOrderStatus === Order::STATUS_DELIVERED
                    && config('services.oto.auto_complete_on_delivered', false)) {
                    $updates['status'] = Order::STATUS_COMPLETED;
                }
            }

            $order->update($updates);

            Log::info('OTO Sync: Order shipment status updated in database', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'tracking_number' => $order->tracking_number,
                'oto_status' => $statusDto->status,
                'oto_status_description' => $statusDto->statusDescription,
                'order_status' => $updates['status'] ?? 'unchanged',
                'tracking_status' => $updates['tracking_status'],
                'shipping_eta' => $updates['shipping_eta'] ?? null,
                'shipping_status_updated_at' => $statusDto->updatedAt?->toDateTimeString(),
                'shipping_payload' => $statusDto->rawPayload,
            ]);
        });

        // Send delivery failure notification (outside transaction)
        if (OtoStatusMapper::isFailed($statusDto->status)
            && !OtoStatusMapper::isFailed($previousTrackingStatus ?? '')) {
            try {
                app(NotificationService::class)->notifyDeliveryFailed($order, $statusDto->status);
            } catch (\Exception $e) {
                Log::error('Failed to send delivery failure notification', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Sync shipment status from OTO API
     * Works even if order doesn't have tracking_number yet - uses orderStatus endpoint first
     */
    public function syncShipmentStatus(Order $order): void
    {
        // Store original values for comparison
        $originalStatus = $order->status;
        $originalTrackingStatus = $order->tracking_status;
        $originalTrackingNumber = $order->tracking_number;

        // Get shipment status (will use orderStatus endpoint if no tracking number)
        $statusDto = $this->getShipmentStatus($order);

        // Log comprehensive OTO sync data
        Log::info('OTO Sync: Fetched shipment status from API', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'oto_order_id' => $order->oto_order_id,
            'tracking_number' => $order->tracking_number,
            'oto_status' => $statusDto->status,
            'oto_status_description' => $statusDto->statusDescription,
            'oto_eta' => $statusDto->eta,
            'oto_updated_at' => $statusDto->updatedAt?->toDateTimeString(),
            'oto_events' => $statusDto->events,
            'oto_raw_response' => $statusDto->rawPayload,
            'current_order_status' => $originalStatus,
            'current_tracking_status' => $originalTrackingStatus,
        ]);

        $this->updateShipmentStatus($order, $statusDto);

        // Reload order to get updated values
        $order->refresh();

        // Log what changed after sync
        $changes = [];
        if ($order->status !== $originalStatus) {
            $changes['status'] = ['from' => $originalStatus, 'to' => $order->status];
        }
        if ($order->tracking_status !== $originalTrackingStatus) {
            $changes['tracking_status'] = ['from' => $originalTrackingStatus, 'to' => $order->tracking_status];
        }
        if ($order->tracking_number !== $originalTrackingNumber && !empty($order->tracking_number)) {
            $changes['tracking_number'] = ['from' => $originalTrackingNumber, 'to' => $order->tracking_number];
        }
        if ($order->shipping_eta !== null) {
            $changes['shipping_eta'] = $order->shipping_eta;
        }

        Log::info('OTO Sync: Order updated after sync', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'changes' => $changes,
            'final_status' => $order->status,
            'final_tracking_status' => $order->tracking_status,
            'shipping_eta' => $order->shipping_eta,
            'shipping_status_updated_at' => $order->shipping_status_updated_at?->toDateTimeString(),
        ]);
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
    protected function buildOrderPayload(Order $order, ?string $notes = null, ?Branch $branch = null): array
    {
        $location = $order->location;
        $pickupConfig = config('services.oto.pickup');

        // Validate pickup configuration
        if (empty($pickupConfig['phone'])) {
            throw OtoConfigurationException::missingPickupConfig('PHONE');
        }

        $paymentMethod = $this->getPaymentMethod($order);
        $codAmount = $this->getCodAmount($order);

        $payload = [
            // Order ID from your system (required)
            'orderId' => $order->order_number,

            // Create shipment automatically
            'createShipment' => true,

            // Payment information
            'payment_method' => $paymentMethod,
            'amount' => (float) $order->total,
            'amount_due' => $codAmount,
            'currency' => 'SAR',
        ];

        // Warehouse selection — if branch has OTO warehouse ID, send it at root level
        if ($branch && $branch->oto_warehouse_id) {
            $warehouseId = is_numeric($branch->oto_warehouse_id)
                ? (int) $branch->oto_warehouse_id
                : $branch->oto_warehouse_id;
            $payload['senderId'] = $warehouseId;
        }

        $payload += [

            // Receiver information (customer)
            'customer' => [
                'name' => trim(($location->first_name ?? '') . ' ' . ($location->last_name ?? '')),
                'address' => $location->street_address,
                'city' => $location->city->name ?? $location->city->name_ar ?? '',
                'mobile' => $location->phone,
                'email' => $location->email ?? $order->user->email ?? '',
                'country' => 'SA',
                'shortAddressCode' => $location->national_address,
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

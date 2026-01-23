<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Shipping\Oto\Dto\OtoShipmentStatusDto;
use App\Services\Shipping\OtoShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook controller for OTO shipment status updates
 */
class OtoShipmentWebhookController extends Controller
{
    protected OtoShippingService $shippingService;

    public function __construct(OtoShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Handle OTO shipment webhook
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('OTO webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::warning('OTO webhook signature verification failed', [
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);
        }

        // Validate payload
        $validation = $this->validatePayload($request);
        if (!$validation['valid']) {
            Log::warning('OTO webhook invalid payload', [
                'errors' => $validation['errors'],
                'payload' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid payload',
                'errors' => $validation['errors'],
            ], 400);
        }

        // Find order
        $order = $this->findOrder($request);
        if (!$order) {
            Log::warning('OTO webhook: Order not found', [
                'tracking_number' => $request->input('tracking_number'),
                'reference' => $request->input('reference'),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Process webhook (idempotent)
        try {
            $this->processWebhook($order, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('OTO webhook processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Verify webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        // If strict verification is disabled, skip
        if (!config('services.oto.webhook.strict_verification', true)) {
            return true;
        }

        $webhookSecret = config('services.oto.webhook.secret');
        if (empty($webhookSecret)) {
            Log::warning('OTO webhook secret not configured, skipping verification');
            return true;
        }

        $signatureHeader = config('services.oto.webhook.signature_header', 'X-OTO-Signature');
        $providedSignature = $request->header($signatureHeader);

        if (empty($providedSignature)) {
            Log::warning('OTO webhook signature header missing', [
                'expected_header' => $signatureHeader,
            ]);
            return false;
        }

        // Calculate expected signature
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Compare signatures (timing-safe)
        return hash_equals($expectedSignature, $providedSignature);
    }

    /**
     * Validate webhook payload
     */
    protected function validatePayload(Request $request): array
    {
        $errors = [];

        // Check required fields
        if (!$request->has('tracking_number') && !$request->has('reference')) {
            $errors[] = 'Missing tracking_number or reference';
        }

        if (!$request->has('status')) {
            $errors[] = 'Missing status';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Find order by tracking number or reference
     */
    protected function findOrder(Request $request): ?Order
    {
        // Try by tracking number first
        if ($trackingNumber = $request->input('tracking_number')) {
            $order = Order::where('tracking_number', $trackingNumber)->first();
            if ($order) {
                return $order;
            }
        }

        // Try by shipping reference
        if ($reference = $request->input('reference')) {
            $order = Order::where('shipping_reference', $reference)->first();
            if ($order) {
                return $order;
            }
        }

        // Try by order number in reference or metadata
        if ($orderNumber = $request->input('metadata.order_number') ?? $request->input('reference')) {
            $order = Order::where('order_number', $orderNumber)->first();
            if ($order) {
                return $order;
            }
        }

        return null;
    }

    /**
     * Process webhook (idempotent)
     */
    protected function processWebhook(Order $order, Request $request): void
    {
        $payload = $request->all();
        
        // Create status DTO from webhook payload
        $statusDto = OtoShipmentStatusDto::fromWebhook($payload);

        // Check if this is a duplicate/old webhook
        if ($this->isDuplicateWebhook($order, $statusDto)) {
            Log::info('OTO webhook is duplicate, skipping', [
                'order_id' => $order->id,
                'current_status' => $order->tracking_status,
                'webhook_status' => $statusDto->status,
            ]);
            return;
        }

        // Update shipment status
        $this->shippingService->updateShipmentStatus($order, $statusDto);

        Log::info('OTO webhook processed successfully', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tracking_number' => $order->tracking_number,
            'old_status' => $order->getOriginal('tracking_status'),
            'new_status' => $statusDto->status,
        ]);
    }

    /**
     * Check if webhook is duplicate/outdated
     */
    protected function isDuplicateWebhook(Order $order, OtoShipmentStatusDto $statusDto): bool
    {
        // If status hasn't changed, consider it a duplicate
        if (!$statusDto->isDifferentFrom($order->tracking_status)) {
            return true;
        }

        // If webhook timestamp is older than last update, it's outdated
        if ($order->shipping_status_updated_at && $statusDto->updatedAt) {
            return $statusDto->updatedAt->lessThan($order->shipping_status_updated_at);
        }

        return false;
    }
}



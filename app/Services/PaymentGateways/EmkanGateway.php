<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Emkan Finance — Retail BNPL Merchant E-commerce Gateway.
 *
 * Implementation follows Ejada "Integration Specification Document" v1.7
 * for Retail Finance / Buy Now Pay Later / Merchant Ecommerce.
 *
 *  Hostnames
 *   - Production : https://gw-pub.emkanfinance.com.sa
 *   - Sandbox    : https://sit-gw-pub.emkanfinance.com.sa
 *
 *  Authentication
 *   - HTTP Basic (username:password) — supplied by Emkan via email.
 *   - All outbound traffic must originate from an IP whitelisted with Emkan.
 *
 *  Endpoints used
 *   - GET  /retail/bnpl/partner-management/v1/{merchantId}/merchantConfig
 *   - POST /retail/bnpl/bff/v1/order-create
 *   - GET  /retail/bnpl/bff/v1/order-status/{orderId}?merchantId={merchantId}
 *   - POST /retail/bnpl/bff/v1/order/refund/submit
 *   - GET  /retail/bnpl/bff/v1/order/refund-details/{orderCode}?merchantCode=&merchantId=
 *   - POST /retail/bnpl/bnpl-bff/order/v1/cancelOrder
 *
 *  Webhook ("Merchant Notifier") — inbound POST from Emkan
 *   Payload:  { orderCode, merchantId, eventCode, nationalId, merchantOrderCode? }
 *   eventCode: DOWN_PAYMENT_SUCCESS | CANCELED | FULLY_REFUND | PARTIAL_REFUND
 *   The spec does NOT define a signature header; security relies on IP allowlist.
 */
class EmkanGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'emkan';

    /**
     * Build the "Authorization: Basic ..." header.
     * Prefers an explicit pre-built EMKAN_BASIC_AUTH if present; otherwise
     * builds it from EMKAN_API_KEY (username) + EMKAN_API_SECRET (password).
     */
    protected function buildAuthHeader(): ?string
    {
        $basic = $this->getConfig('basic_auth');
        if ($basic) {
            return 'Basic ' . ltrim((string) preg_replace('/^Basic\s+/i', '', $basic));
        }

        $user = (string) $this->getConfig('api_key', '');
        $pass = (string) $this->getConfig('api_secret', '');
        if ($user === '' || $pass === '') {
            return null;
        }

        return 'Basic ' . base64_encode($user . ':' . $pass);
    }

    /**
     * Headers required on every Emkan call.
     *
     * @param  array<string,string>  $extra  caller-supplied overrides / additions
     * @return array<string,string>
     */
    protected function commonHeaders(array $extra = []): array
    {
        $headers = [
            'Authorization' => (string) $this->buildAuthHeader(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'channel' => (string) $this->getConfig('channel', 'BNPL'),
            'language' => app()->getLocale() === 'ar' ? 'AR' : 'EN',
            'caller-reference-number' => (string) Str::uuid(),
        ];

        return array_merge($headers, $extra);
    }

    protected function baseUrl(): string
    {
        return rtrim((string) $this->getConfig('api_url', 'https://gw-pub.emkanfinance.com.sa'), '/');
    }

    protected function isConfigured(): bool
    {
        return $this->buildAuthHeader() !== null
            && $this->getConfig('merchant_id') !== null
            && $this->getConfig('merchant_code') !== null;
    }

    /**
     * Optional helper — call /merchantConfig for diagnostics & limit checks.
     *
     * @return array<string,mixed>
     */
    public function getMerchantConfig(): array
    {
        $merchantId = (string) $this->getConfig('merchant_id');
        $url = $this->baseUrl() . '/retail/bnpl/partner-management/v1/' . rawurlencode($merchantId) . '/merchantConfig';

        $response = $this->httpGet($url, [], $this->commonHeaders());

        return [
            'success' => $response['success'] ?? false,
            'data' => $response['data'] ?? [],
            'status_code' => $response['status_code'] ?? 0,
        ];
    }

    /**
     * Create a BNPL order.
     */
    public function createPayment(Order $order): array
    {
        try {
            if (! $this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => __('Emkan configuration is incomplete'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $minAmount = (float) $this->getConfig('min_amount', 500);
            $maxAmount = (float) $this->getConfig('max_amount', 0);

            if ($minAmount > 0 && (float) $order->total < $minAmount) {
                return [
                    'success' => false,
                    'message' => __('emkan.error_below_minimum', ['min' => $minAmount]),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            if ($maxAmount > 0 && (float) $order->total > $maxAmount) {
                return [
                    'success' => false,
                    'message' => __('emkan.error_above_maximum', ['max' => $maxAmount]),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $merchantId = (string) $this->getConfig('merchant_id');
            $merchantCode = (string) $this->getConfig('merchant_code');
            $aggregatorId = $this->getConfig('aggregator_id');
            $expiresInMinutes = (int) $this->getConfig('expires_in_minutes', 30);

            $user = $order->user;
            $mobile = $this->normalizePhone($user->phone ?? '');

            $now = now()->toIso8601ZuluString();
            $orderItems = $order->items->map(function ($item) use ($now) {
                return [
                    'itemPrice' => round((float) $item->price, 2),
                    'quantity' => (int) $item->quantity,
                    'orderId' => (int) $item->id,
                    'createAt' => $now,
                    'updatedAt' => $now,
                    'itemName' => Str::limit(
                        $item->product->name_en ?? $item->product->name ?? 'Product',
                        100,
                        ''
                    ),
                ];
            })->values()->toArray();

            if (empty($orderItems)) {
                $orderItems[] = [
                    'itemPrice' => round((float) $order->total, 2),
                    'quantity' => 1,
                    'orderId' => (int) $order->id,
                    'createAt' => $now,
                    'updatedAt' => $now,
                    'itemName' => 'Order #' . $order->order_number,
                ];
            }

            $payload = array_filter([
                'orderId' => (string) $order->order_number,
                'merchantOrderCode' => (string) $order->order_number,
                'aggregatorId' => $aggregatorId,
                'merchantId' => $merchantId,
                'billAmount' => round((float) $order->total, 2),
                'mobileNumber' => $mobile,
                'expiresInMinutes' => $expiresInMinutes,
                'successRedirectionUrl' => route('payment.callback', ['order' => $order->id, 'status' => 'success']),
                'failureRedirectionUrl' => route('payment.callback', ['order' => $order->id, 'status' => 'failure']),
                'callbackUrl' => route('payment.webhook', ['gateway' => 'emkan']),
                'orderItems' => $orderItems,
            ], static fn ($v) => $v !== null);
            // Force orderItems back in even if filter drops it (it's an array so it survives)
            $payload['orderItems'] = $orderItems;

            $headers = $this->commonHeaders([
                'MERCHANT_CODE' => $merchantCode,
                'origin-source-channel' => (string) $this->getConfig('origin_source_channel', 'Neoleap_POS'),
            ]);

            $response = $this->httpPost(
                $this->baseUrl() . '/retail/bnpl/bff/v1/order-create',
                $payload,
                $headers
            );

            $data = $response['data'] ?? [];

            if (! $response['success'] || ($data['code'] ?? null) && $data['code'] !== 'I000000') {
                return [
                    'success' => false,
                    'message' => $data['description'] ?? $response['error'] ?? __('Failed to create Emkan order'),
                    'transaction_id' => $data['orderId'] ?? null,
                    'redirect_url' => null,
                    'data' => $data,
                ];
            }

            $orderCode = $data['orderId'] ?? null;
            $paymentUrl = $data['paymentURL'] ?? $data['paymentUrl'] ?? null;

            return [
                'success' => true,
                'transaction_id' => $orderCode,
                'redirect_url' => $paymentUrl,
                'requires_redirect' => ! empty($paymentUrl),
                'status' => 'pending',
                'message' => $data['description'] ?? __('Emkan order created successfully'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Emkan order creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create Emkan order: :error', ['error' => $e->getMessage()]),
                'transaction_id' => null,
                'redirect_url' => null,
            ];
        }
    }

    /**
     * Emkan has no explicit "capture" step — payment is finalised when the
     * customer pays the down-payment, which Emkan signals via the
     * DOWN_PAYMENT_SUCCESS webhook. We expose this method only because the
     * abstract class requires it; we simply return the current order status.
     */
    public function capturePayment(string $transactionId, ?Order $order = null): array
    {
        $status = $this->getPaymentStatus($transactionId);

        return [
            'success' => $status['success'] ?? false,
            'message' => $status['success']
                ? __('Emkan does not require an explicit capture step; current status returned.')
                : ($status['message'] ?? __('Failed to fetch Emkan order status')),
            'data' => $status['data'] ?? [],
        ];
    }

    /**
     * Submit a refund request.
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            if (! $this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => __('Emkan configuration is incomplete'),
                ];
            }

            $payload = [
                'orderCode' => $transactionId,
                'merchantCode' => (string) $this->getConfig('merchant_code'),
                'merchantId' => (string) $this->getConfig('merchant_id'),
                'refundAmount' => number_format($amount, 2, '.', ''),
            ];

            $response = $this->httpPost(
                $this->baseUrl() . '/retail/bnpl/bff/v1/order/refund/submit',
                $payload,
                $this->commonHeaders()
            );

            $data = $response['data'] ?? [];

            if (! $response['success'] || (isset($data['code']) && $data['code'] !== 'I000000')) {
                return [
                    'success' => false,
                    'refund_id' => null,
                    'message' => $data['description'] ?? $response['error'] ?? __('Failed to submit Emkan refund'),
                    'data' => $data,
                ];
            }

            return [
                'success' => true,
                // Emkan does not return a refund id on submit — use the requestId for traceability.
                'refund_id' => $data['requestId'] ?? ($transactionId . '-refund'),
                'message' => $data['description'] ?? __('Refund submitted successfully'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Emkan refund failed', [
                'order_code' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to process refund: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Inquire refunded amount details for an order.
     *
     * @return array<string,mixed>
     */
    public function getRefundDetails(string $orderCode): array
    {
        $merchantId = (string) $this->getConfig('merchant_id');
        $merchantCode = (string) $this->getConfig('merchant_code');

        $url = $this->baseUrl() . '/retail/bnpl/bff/v1/order/refund-details/' . rawurlencode($orderCode);

        $response = $this->httpGet(
            $url,
            ['merchantCode' => $merchantCode, 'merchantId' => $merchantId],
            $this->commonHeaders()
        );

        return [
            'success' => $response['success'] ?? false,
            'data' => $response['data'] ?? [],
            'status_code' => $response['status_code'] ?? 0,
        ];
    }

    /**
     * Cancel an Emkan order (only valid in early stages — see error 3000).
     */
    public function cancelOrder(string $orderCode): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => __('Emkan configuration is incomplete'),
            ];
        }

        $headers = $this->commonHeaders([
            'MERCHANT_CODE' => (string) $this->getConfig('merchant_code'),
        ]);

        $response = $this->httpPost(
            $this->baseUrl() . '/retail/bnpl/bnpl-bff/order/v1/cancelOrder',
            [
                'orderCode' => $orderCode,
                'merchantId' => (string) $this->getConfig('merchant_id'),
            ],
            $headers
        );

        $data = $response['data'] ?? [];
        $ok = $response['success'] && (! isset($data['code']) || $data['code'] === 'I000000');

        return [
            'success' => $ok,
            'message' => $data['description'] ?? ($ok ? __('Order cancelled') : __('Failed to cancel Emkan order')),
            'data' => $data,
        ];
    }

    /**
     * Get payment / order status.
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            if (! $this->isConfigured()) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Emkan configuration is incomplete'),
                ];
            }

            $merchantId = (string) $this->getConfig('merchant_id');
            $url = $this->baseUrl() . '/retail/bnpl/bff/v1/order-status/' . rawurlencode($transactionId);

            $response = $this->httpGet(
                $url,
                ['merchantId' => $merchantId],
                $this->commonHeaders()
            );

            $data = $response['data'] ?? [];

            if (! $response['success'] || (isset($data['code']) && $data['code'] !== 'I000000')) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $data['description'] ?? $response['error'] ?? __('Failed to fetch Emkan status'),
                    'data' => $data,
                ];
            }

            return [
                'success' => true,
                'status' => $this->mapOrderStatus((string) ($data['statusCode'] ?? 'unknown')),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Emkan status check failed', [
                'order_code' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => __('Failed to get payment status: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Handle Merchant Notifier (webhook) callbacks.
     * Payload per spec: { orderCode, merchantId, eventCode, nationalId, merchantOrderCode? }
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $eventCode = (string) ($payload['eventCode'] ?? '');
            $orderCode = $payload['orderCode'] ?? null;
            // merchantOrderCode is the merchant's own reference (= our order_number) per spec v1.5.
            $orderNumber = $payload['merchantOrderCode'] ?? null;

            if (! $orderCode && ! $orderNumber) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => 'error',
                    'message' => __('Emkan webhook missing orderCode and merchantOrderCode'),
                ];
            }

            $order = null;
            if ($orderNumber) {
                $order = Order::where('order_number', $orderNumber)->first();
            }
            if (! $order && $orderCode) {
                // Fallback: locate via payment_transactions table
                $txn = \App\Models\PaymentTransaction::where('transaction_id', $orderCode)
                    ->where('gateway', 'emkan')
                    ->first();
                $order = $txn?->order;
            }

            return [
                'success' => true,
                'order_id' => $order?->id,
                'status' => $this->mapEventCode($eventCode),
                'transaction_id' => $orderCode,
                'event_code' => $eventCode,
            ];
        } catch (\Exception $e) {
            Log::error('Emkan webhook processing failed', [
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Webhook signature verification.
     *
     * Emkan integration spec (v1.7) does NOT define a signature header for the
     * Merchant Notifier callback. Authentication of the webhook caller is
     * delegated to network-level controls (IP allowlisting agreed during
     * onboarding). We therefore:
     *   - return true when no secret is configured (default Emkan posture);
     *   - if EMKAN_WEBHOOK_SECRET *is* set, opportunistically verify a
     *     X-Emkan-Signature / X-Signature HMAC-SHA256 header for forward
     *     compatibility, but treat its absence as success (logging only).
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $secret = $this->getConfig('webhook_secret');
        if (! $secret) {
            return true;
        }

        $signature = $request->header('X-Emkan-Signature') ?? $request->header('X-Signature');
        if (! $signature) {
            Log::info('Emkan webhook arrived without signature header — accepting (per spec, IP allowlist enforced upstream).');
            return true;
        }

        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);
        return hash_equals($expected, (string) $signature);
    }

    /**
     * Map Emkan orderStatus codes (Get BNPL Order Status) to internal statuses.
     */
    protected function mapOrderStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'COMPLETED' => 'success',
            'CREATED', 'INITIATED', 'PENDING_IVR', 'ACCEPTED_IVR' => 'pending',
            'REJECTED_IVR' => 'failed',
            'CANCELED', 'CANCELLED' => 'cancelled',
            default => 'pending',
        };
    }

    /**
     * Map Merchant Notifier eventCode values to internal statuses.
     */
    protected function mapEventCode(string $eventCode): string
    {
        return match (strtoupper($eventCode)) {
            'DOWN_PAYMENT_SUCCESS' => 'success',
            'CANCELED', 'CANCELLED' => 'cancelled',
            'FULLY_REFUND', 'PARTIAL_REFUND' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Normalize Saudi phone number to 9665XXXXXXXX (12 digits, no '+').
     * Emkan example values use this exact format ("966541710298").
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = (string) preg_replace('/[^0-9]/', '', $phone);

        if ($phone === '' || strlen($phone) < 9) {
            return '966500000000';
        }

        if (strlen($phone) === 9 && str_starts_with($phone, '5')) {
            return '966' . $phone;
        }

        if (strlen($phone) === 10 && str_starts_with($phone, '05')) {
            return '966' . substr($phone, 1);
        }

        if (str_starts_with($phone, '966') && strlen($phone) === 12) {
            return $phone;
        }

        return '966' . substr($phone, -9);
    }
}

<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Madfu Payment Gateway
 *
 * Buy-Now-Pay-Later split payments via Madfu hosted checkout.
 * Official docs: https://madfuapis.readme.io/
 *
 * Flow:
 *   1) POST /merchants/token/init  → JWT (short-lived, cached)
 *   2) POST /Merchants/Checkout/CreateOrder  → { orderId, invoiceCode, checkoutLink }
 *   3) Redirect customer to checkoutLink
 *   4) Madfu posts webhook on status change
 *
 * Required headers on every call (except token/init):
 *   Token          → JWT from step 1 (no "Bearer " prefix)
 *   APIKey         → MADFU_API_KEY
 *   AppCode        → MADFU_APP_CODE
 *   PlatformTypeId → MADFU_PLATFORM_TYPE_ID (default: 7 for web)
 *   Authorization  → Basic <MADFU_BASIC_AUTH> (static credential from Madfu portal)
 */
class MadfuGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'madfu';

    protected const TOKEN_CACHE_KEY = 'madfu:jwt_token';
    protected const TOKEN_CACHE_TTL = 3300; // 55 minutes

    /**
     * Build the Basic Authorization header used on every Madfu call.
     */
    protected function buildAuthHeader(): ?string
    {
        $basicAuth = $this->getConfig('basic_auth');
        if ($basicAuth) {
            return 'Basic ' . ltrim(preg_replace('/^Basic\s+/i', '', $basicAuth));
        }
        $apiKey = $this->getConfig('api_key');
        return $apiKey ? 'Bearer ' . $apiKey : null;
    }

    /**
     * Common headers required by every Madfu endpoint.
     */
    protected function commonHeaders(?string $jwt = null): array
    {
        $headers = [
            'APIKey' => (string) $this->getConfig('api_key'),
            'AppCode' => (string) $this->getConfig('app_code'),
            'PlatformTypeId' => (string) $this->getConfig('platform_type_id', 7),
            'Authorization' => (string) $this->buildAuthHeader(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($jwt) {
            // Madfu expects the raw JWT in the "Token" header, NO "Bearer " prefix.
            $headers['Token'] = preg_replace('/^Bearer\s+/i', '', $jwt);
        }

        return $headers;
    }

    /**
     * Obtain (and cache) a JWT via /merchants/token/init.
     */
    protected function getJwtToken(bool $forceRefresh = false): ?string
    {
        if (! $forceRefresh) {
            $cached = Cache::get(self::TOKEN_CACHE_KEY);
            if ($cached) {
                return $cached;
            }
        }

        $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

        $response = $this->httpPost(
            $baseUrl . '/merchants/token/init',
            [
                'uuid' => (string) Str::uuid(),
                'systemInfo' => 'web',
            ],
            $this->commonHeaders()
        );

        if (! $response['success']) {
            Log::error('Madfu token init failed', [
                'status_code' => $response['status_code'] ?? null,
                'error' => $response['error'] ?? null,
                'data' => $response['data'] ?? null,
            ]);
            return null;
        }

        $data = $response['data'] ?? [];
        $jwt = $data['token']
            ?? $data['Token']
            ?? ($data['data']['token'] ?? null)
            ?? ($data['responseBody']['token'] ?? null);

        if (! $jwt) {
            Log::error('Madfu token init returned no JWT', ['response' => $data]);
            return null;
        }

        Cache::put(self::TOKEN_CACHE_KEY, $jwt, self::TOKEN_CACHE_TTL);
        return $jwt;
    }

    /**
     * Create a payment session for the order.
     */
    public function createPayment(Order $order): array
    {
        try {
            $apiKey = $this->getConfig('api_key');
            $appCode = $this->getConfig('app_code');
            $authHeader = $this->buildAuthHeader();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $authHeader || ! $apiKey || ! $appCode) {
                return [
                    'success' => false,
                    'message' => __('Madfu configuration is incomplete'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $jwt = $this->getJwtToken();
            if (! $jwt) {
                return [
                    'success' => false,
                    'message' => __('Failed to authenticate with Madfu'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $user = $order->user;
            $location = $order->location;
            $phone = $this->normalizePhone($user->phone ?? '');

            $orderDetails = $order->items->map(function ($item) {
                return [
                    'ProductName' => Str::limit($item->product->name_en ?? $item->product->name ?? 'Product', 100, ''),
                    'Quantity' => (int) $item->quantity,
                    'Price' => round((float) $item->price, 2),
                ];
            })->values()->toArray();

            if (empty($orderDetails)) {
                $orderDetails[] = [
                    'ProductName' => 'Order #' . $order->order_number,
                    'Quantity' => 1,
                    'Price' => round((float) $order->total, 2),
                ];
            }

            $successUrl = route('payment.callback', ['order' => $order->id, 'status' => 'success']);
            $failureUrl = route('payment.callback', ['order' => $order->id, 'status' => 'failure']);
            $webhookUrl = route('payment.webhook', ['gateway' => 'madfu']);

            $payload = [
                'Order' => [
                    'MerchantReference' => (string) $order->order_number,
                    'TotalAmount' => round((float) $order->total, 2),
                    'PaidAmount' => 0,
                    'Vat' => round((float) ($order->tax ?? 0), 2),
                    'DeliveryAmount' => round((float) ($order->shipping ?? 0), 2),
                    'DiscountAmount' => round((float) ($order->discount ?? 0), 2),
                    'Branch' => (int) $this->getConfig('branch_id', 1),
                    'OrderDetails' => $orderDetails,
                ],
                'GuestOrderData' => [
                    'FullName' => $user->name ?? 'Customer',
                    'Email' => $user->email ?? 'customer@example.com',
                    'Mobile' => $phone,
                    'City' => $location?->city?->name_en ?? $location?->city?->name ?? 'Riyadh',
                    'Address' => $location?->address ?? 'Saudi Arabia',
                    'Country' => 'SA',
                ],
                'MerchantUrls' => [
                    'SuccessUrl' => $successUrl,
                    'FailUrl' => $failureUrl,
                    'CancelUrl' => $failureUrl,
                    'WebhookUrl' => $webhookUrl,
                ],
            ];

            $response = $this->httpPost(
                $baseUrl . '/Merchants/Checkout/CreateOrder',
                $payload,
                $this->commonHeaders($jwt)
            );

            // If 401, token may have expired — retry once with fresh JWT
            if (! $response['success'] && ($response['status_code'] ?? 0) === 401) {
                $jwt = $this->getJwtToken(true);
                if ($jwt) {
                    $response = $this->httpPost(
                        $baseUrl . '/Merchants/Checkout/CreateOrder',
                        $payload,
                        $this->commonHeaders($jwt)
                    );
                }
            }

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create Madfu transaction'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $data = $response['data'] ?? [];
            $body = $data['responseBody'] ?? $data;

            $transactionId = $body['orderId']
                ?? $body['OrderId']
                ?? $body['invoiceCode']
                ?? $body['InvoiceCode']
                ?? null;

            $redirectUrl = $body['checkoutLink']
                ?? $body['CheckoutLink']
                ?? $body['checkoutUrl']
                ?? $body['redirectUrl']
                ?? null;

            if (! $redirectUrl || ! $transactionId) {
                Log::error('Madfu createOrder returned incomplete response', [
                    'order_id' => $order->id,
                    'response' => $data,
                ]);
                return [
                    'success' => false,
                    'message' => __('Madfu did not return a checkout URL'),
                    'transaction_id' => $transactionId,
                    'redirect_url' => null,
                    'data' => $data,
                ];
            }

            return [
                'success' => true,
                'transaction_id' => (string) $transactionId,
                'redirect_url' => $redirectUrl,
                'requires_redirect' => true,
                'status' => 'pending',
                'message' => __('Madfu transaction created successfully'),
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Madfu payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create Madfu transaction: :error', ['error' => $e->getMessage()]),
                'transaction_id' => null,
                'redirect_url' => null,
            ];
        }
    }

    /**
     * Madfu auto-captures on checkout completion; no explicit capture endpoint.
     */
    public function capturePayment(string $transactionId): array
    {
        return [
            'success' => true,
            'message' => __('Madfu auto-captures payments on checkout completion'),
            'data' => ['transaction_id' => $transactionId],
        ];
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $jwt = $this->getJwtToken();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $jwt) {
                return [
                    'success' => false,
                    'message' => __('Failed to authenticate with Madfu'),
                ];
            }

            $response = $this->httpPost(
                $baseUrl . '/Merchants/Refund',
                [
                    'OrderId' => $transactionId,
                    'RefundAmount' => round($amount, 2),
                ],
                $this->commonHeaders($jwt)
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process Madfu refund'),
                    'refund_id' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $data = $response['data'] ?? [];
            $body = $data['responseBody'] ?? $data;

            return [
                'success' => true,
                'refund_id' => $body['refundId'] ?? $body['RefundId'] ?? ($transactionId . '-refund'),
                'message' => __('Refund processed successfully'),
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Madfu refund failed', [
                'transaction_id' => $transactionId,
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
     * Get payment status from gateway.
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $jwt = $this->getJwtToken();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $jwt) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Failed to authenticate with Madfu'),
                ];
            }

            $response = $this->httpGet(
                $baseUrl . '/Merchants/Order/' . urlencode($transactionId),
                [],
                $this->commonHeaders($jwt)
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get Madfu status'),
                ];
            }

            $data = $response['data'] ?? [];
            $body = $data['responseBody'] ?? $data;

            return [
                'success' => true,
                'status' => $this->mapStatus((string) ($body['orderStatus'] ?? $body['status'] ?? 'unknown')),
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Madfu status check failed', [
                'transaction_id' => $transactionId,
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
     * Handle webhook notification from Madfu.
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $status = $this->mapStatus((string) (
                $payload['orderStatus']
                ?? $payload['OrderStatus']
                ?? $payload['status']
                ?? 'unknown'
            ));

            $transactionId = $payload['orderId']
                ?? $payload['OrderId']
                ?? $payload['invoiceCode']
                ?? $payload['InvoiceCode']
                ?? null;

            $orderNumber = $payload['merchantReference']
                ?? $payload['MerchantReference']
                ?? $payload['reference']
                ?? null;

            if (! $orderNumber) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order reference not found in Madfu webhook'),
                ];
            }

            $order = Order::where('order_number', $orderNumber)->first();

            return [
                'success' => true,
                'order_id' => $order?->id,
                'status' => $status,
                'transaction_id' => $transactionId,
            ];

        } catch (\Exception $e) {
            Log::error('Madfu webhook processing failed', [
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
     * Validate webhook signature (HMAC-SHA256).
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $secret = $this->getConfig('webhook_secret');

        if (! $secret) {
            return ! config('payment-gateways.webhook.verify_signature', true);
        }

        $signature = $request->header('X-Madfu-Signature') ?? $request->header('X-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Map Madfu status string to internal status.
     */
    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'approved', 'captured', 'completed', 'paid', 'success', 'successful' => 'success',
            'pending', 'processing', 'initiated', 'created', 'new' => 'pending',
            'rejected', 'declined', 'failed', 'expired' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Normalize Saudi phone number to 9665XXXXXXXX format.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($phone) || strlen($phone) < 9) {
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

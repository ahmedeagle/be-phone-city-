<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Madfu Payment Gateway
 *
 * Madfu provides Buy-Now-Pay-Later split payments.
 * Documentation: https://www.madfu.com.sa / https://docs.madfu.com.sa
 *
 * Implementation follows the same architecture as Tabby/Tamara.
 * Configure credentials in config/payment-gateways.php under 'madfu'.
 */
class MadfuGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'madfu';

    /**
     * Build the Authorization header value.
     * Prefers pre-built Basic auth token from Madfu portal, falls back to Bearer api_key.
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
     * Create a payment session for the order
     */
    public function createPayment(Order $order): array
    {
        try {
            $merchantId = $this->getConfig('merchant_id');
            $appCode = $this->getConfig('app_code');
            $authHeader = $this->buildAuthHeader();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $authHeader || ! $merchantId) {
                return [
                    'success' => false,
                    'message' => __('Madfu configuration is incomplete'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $user = $order->user;
            $location = $order->location;

            $phone = $this->normalizePhone($user->phone ?? '');

            $paymentData = [
                'merchant_id' => $merchantId,
                'app_code' => $appCode,
                'order_id' => $order->order_number,
                'amount' => number_format($order->total, 2, '.', ''),
                'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                'description' => __('Order') . ' #' . $order->order_number,
                'language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
                'customer' => [
                    'name' => $user->name ?? 'Customer',
                    'email' => $user->email ?? 'customer@example.com',
                    'phone' => $phone,
                ],
                'shipping' => [
                    'city' => $location?->city?->name ?? 'Riyadh',
                    'address' => $location?->address ?? 'Saudi Arabia',
                    'country' => 'SA',
                ],
                'items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->product->name ?? 'Product',
                        'quantity' => $item->quantity,
                        'price' => number_format($item->price, 2, '.', ''),
                        'sku' => $item->product_id,
                    ];
                })->toArray(),
                'success_url' => route('payment.callback', ['order' => $order->id, 'status' => 'success']),
                'cancel_url' => route('payment.callback', ['order' => $order->id, 'status' => 'cancel']),
                'failure_url' => route('payment.callback', ['order' => $order->id, 'status' => 'failure']),
                'webhook_url' => route('payment.webhook', ['gateway' => 'madfu']),
            ];

            $response = $this->httpPost(
                $baseUrl . '/v1/transactions/create',
                $paymentData,
                [
                    'Authorization' => $authHeader,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create Madfu transaction'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $data = $response['data'];
            $transactionId = $data['transaction_id'] ?? $data['id'] ?? null;
            $redirectUrl = $data['redirect_url'] ?? $data['payment_url'] ?? $data['checkout_url'] ?? null;

            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'redirect_url' => $redirectUrl,
                'requires_redirect' => ! empty($redirectUrl),
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
     * Capture a payment
     */
    public function capturePayment(string $transactionId): array
    {
        try {
            $authHeader = $this->buildAuthHeader();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $authHeader) {
                return [
                    'success' => false,
                    'message' => __('Madfu API key is not configured'),
                ];
            }

            $response = $this->httpPost(
                $baseUrl . '/v1/transactions/' . $transactionId . '/capture',
                [],
                [
                    'Authorization' => $authHeader,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to capture Madfu payment'),
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => __('Payment captured successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Madfu capture failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to capture payment: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $authHeader = $this->buildAuthHeader();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $authHeader) {
                return [
                    'success' => false,
                    'message' => __('Madfu API key is not configured'),
                ];
            }

            $response = $this->httpPost(
                $baseUrl . '/v1/transactions/' . $transactionId . '/refund',
                [
                    'amount' => number_format($amount, 2, '.', ''),
                ],
                [
                    'Authorization' => $authHeader,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process Madfu refund'),
                    'refund_id' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'refund_id' => $response['data']['refund_id'] ?? $response['data']['id'] ?? ($transactionId . '-refund'),
                'message' => __('Refund processed successfully'),
                'data' => $response['data'] ?? [],
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
     * Get payment status from gateway
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $authHeader = $this->buildAuthHeader();
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.madfu.com.sa'), '/');

            if (! $authHeader) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Madfu API key is not configured'),
                ];
            }

            $response = $this->httpGet(
                $baseUrl . '/v1/transactions/' . $transactionId,
                [],
                [
                    'Authorization' => $authHeader,
                    'Accept' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get Madfu status'),
                ];
            }

            $data = $response['data'];

            return [
                'success' => true,
                'status' => $this->mapStatus($data['status'] ?? 'unknown'),
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
     * Handle webhook notification
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $status = $this->mapStatus($payload['status'] ?? $payload['event'] ?? 'unknown');
            $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;
            $orderNumber = $payload['order_id'] ?? $payload['reference_id'] ?? $payload['order']['reference_id'] ?? null;

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
     * Validate webhook signature (HMAC-SHA256)
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
     * Map Madfu status to internal status
     */
    protected function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'approved', 'captured', 'completed', 'paid', 'success' => 'success',
            'pending', 'processing', 'initiated', 'created' => 'pending',
            'rejected', 'declined', 'failed', 'expired' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Normalize Saudi phone number to 9665XXXXXXXX format
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

<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Emkan Payment Gateway
 *
 * Emkan provides Sharia-compliant Buy-Now-Pay-Later financing.
 * Documentation: https://emkanfinance.com.sa
 *
 * Implementation follows the same architecture as Tabby/Tamara.
 * Configure credentials in config/payment-gateways.php under 'emkan'.
 */
class EmkanGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'emkan';

    /**
     * Create a payment session for the order
     */
    public function createPayment(Order $order): array
    {
        try {
            $apiKey = $this->getConfig('api_key');
            $merchantId = $this->getConfig('merchant_id');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.emkanfinance.com.sa'), '/');

            if (! $apiKey || ! $merchantId) {
                return [
                    'success' => false,
                    'message' => __('Emkan configuration is incomplete'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $user = $order->user;
            $location = $order->location;

            $phone = $this->normalizePhone($user->phone ?? '');

            $paymentData = [
                'merchant_id' => $merchantId,
                'reference_id' => $order->order_number,
                'amount' => number_format($order->total, 2, '.', ''),
                'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                'description' => __('Order') . ' #' . $order->order_number,
                'language' => app()->getLocale() === 'ar' ? 'ar' : 'en',
                'customer' => [
                    'name' => $user->name ?? 'Customer',
                    'email' => $user->email ?? 'customer@example.com',
                    'phone' => $phone,
                    'national_id' => $user->national_id ?? null,
                ],
                'shipping_address' => [
                    'city' => $location?->city?->name ?? 'Riyadh',
                    'address' => $location?->address ?? 'Saudi Arabia',
                    'country' => 'SA',
                ],
                'items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->product->name ?? 'Product',
                        'quantity' => $item->quantity,
                        'unit_price' => number_format($item->price, 2, '.', ''),
                        'reference_id' => $item->product_id,
                    ];
                })->toArray(),
                'callback_urls' => [
                    'success' => route('payment.callback', ['order' => $order->id, 'status' => 'success']),
                    'cancel' => route('payment.callback', ['order' => $order->id, 'status' => 'cancel']),
                    'failure' => route('payment.callback', ['order' => $order->id, 'status' => 'failure']),
                ],
                'webhook_url' => route('payment.webhook', ['gateway' => 'emkan']),
            ];

            $response = $this->httpPost(
                $baseUrl . '/v1/checkout/sessions',
                $paymentData,
                [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create Emkan checkout session'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $data = $response['data'];
            $sessionId = $data['session_id'] ?? $data['id'] ?? null;
            $redirectUrl = $data['redirect_url'] ?? $data['checkout_url'] ?? null;

            return [
                'success' => true,
                'transaction_id' => $sessionId,
                'redirect_url' => $redirectUrl,
                'requires_redirect' => ! empty($redirectUrl),
                'status' => 'pending',
                'message' => __('Emkan checkout session created successfully'),
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Emkan payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create Emkan session: :error', ['error' => $e->getMessage()]),
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
            $apiKey = $this->getConfig('api_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.emkanfinance.com.sa'), '/');

            if (! $apiKey) {
                return [
                    'success' => false,
                    'message' => __('Emkan API key is not configured'),
                ];
            }

            $response = $this->httpPost(
                $baseUrl . '/v1/payments/' . $transactionId . '/capture',
                [],
                [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to capture Emkan payment'),
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => __('Payment captured successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Emkan capture failed', [
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
            $apiKey = $this->getConfig('api_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.emkanfinance.com.sa'), '/');

            if (! $apiKey) {
                return [
                    'success' => false,
                    'message' => __('Emkan API key is not configured'),
                ];
            }

            $response = $this->httpPost(
                $baseUrl . '/v1/payments/' . $transactionId . '/refunds',
                [
                    'amount' => number_format($amount, 2, '.', ''),
                ],
                [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process Emkan refund'),
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
            Log::error('Emkan refund failed', [
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
            $apiKey = $this->getConfig('api_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.emkanfinance.com.sa'), '/');

            if (! $apiKey) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Emkan API key is not configured'),
                ];
            }

            $response = $this->httpGet(
                $baseUrl . '/v1/payments/' . $transactionId,
                [],
                [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get Emkan status'),
                ];
            }

            $data = $response['data'];

            return [
                'success' => true,
                'status' => $this->mapStatus($data['status'] ?? 'unknown'),
                'data' => $data,
            ];

        } catch (\Exception $e) {
            Log::error('Emkan status check failed', [
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
            $transactionId = $payload['payment_id'] ?? $payload['id'] ?? $payload['session_id'] ?? null;
            $orderNumber = $payload['reference_id'] ?? $payload['order']['reference_id'] ?? null;

            if (! $orderNumber) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order reference not found in Emkan webhook'),
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
     * Validate webhook signature (HMAC-SHA256)
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $secret = $this->getConfig('webhook_secret');

        if (! $secret) {
            // No secret configured - accept (or change to false to reject)
            return ! config('payment-gateways.webhook.verify_signature', true);
        }

        $signature = $request->header('X-Emkan-Signature') ?? $request->header('X-Signature');

        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Map Emkan status to internal status
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

<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Moyasar Payment Gateway
 *
 * Moyasar is a payment gateway service provider in Saudi Arabia.
 * Documentation: https://moyasar.com/docs
 */
class MoyasarGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'moyasar';

    /**
     * Create a payment session for the order
     *
     * @param Order $order
     * @return array
     */
    public function createPayment(Order $order): array
    {
        try {
            $apiKey = $this->getConfig('secret_key');
            $apiUrl = $this->getConfig('api_url', 'https://api.moyasar.com/v1');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => __('Moyasar API key is not configured'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'status' => 'failed',
                ];
            }

            // Prepare payment data for Invoices API
            // Using invoices is the most reliable way to get a hosted payment page from the backend
            $callbackUrl = $this->getConfig('callback_url');
            if (empty($callbackUrl)) {
                $callbackUrl = route('payment.callback', ['order' => $order->id]);
            }

            $paymentData = [
                'amount' => (int)($order->total * 100), // Convert to halalas (cents)
                'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                'description' => __('Order') . ' #' . $order->order_number,
                'callback_url' => $callbackUrl, // Webhook URL (Moyasar server -> your server)
                'success_url' => $callbackUrl,  // Redirect URL (User browser -> your server)
                'back_url' => url('/'),         // Back URL (User browser -> your server)
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ];

            // Make API request to /invoices instead of /sessions or /payments for hosted checkout
            // Invoices API is widely available and returns a hosted payment URL
            $response = $this->httpPost(
                $apiUrl . '/invoices',
                $paymentData,
                [
                    'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create payment invoice'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'status' => 'failed',
                    'data' => $response['data'] ?? [],
                ];
            }

            $paymentResponse = $response['data'];

            return [
                'success' => true,
                'transaction_id' => $paymentResponse['id'] ?? null,
                'redirect_url' => $paymentResponse['url'] ?? null, // Invoices use 'url' for the payment page
                'requires_redirect' => !empty($paymentResponse['url']),
                'status' => $this->mapMoyasarStatus($paymentResponse['status'] ?? 'initiated'),
                'message' => __('Payment invoice created successfully'),
                'data' => $paymentResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create payment session: :error', ['error' => $e->getMessage()]),
                'transaction_id' => null,
                'redirect_url' => null,
                'status' => 'failed',
            ];
        }
    }

    /**
     * Capture a payment (finalize the transaction)
     *
     * @param string $transactionId
     * @return array
     */
    public function capturePayment(string $transactionId): array
    {
        try {
            $apiKey = $this->getConfig('secret_key');
            $apiUrl = $this->getConfig('api_url', 'https://api.moyasar.com/v1');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => __('Moyasar API key is not configured'),
                ];
            }

            $response = $this->httpPost(
                $apiUrl . '/payments/' . $transactionId . '/capture',
                [],
                [
                    'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to capture payment'),
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => __('Payment captured successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar payment capture failed', [
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
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $apiKey = $this->getConfig('secret_key');
            $apiUrl = $this->getConfig('api_url', 'https://api.moyasar.com/v1');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => __('Moyasar API key is not configured'),
                    'refund_id' => null,
                ];
            }

            $refundData = [
                'amount' => (int)($amount * 100), // Convert to halalas
            ];

            $response = $this->httpPost(
                $apiUrl . '/payments/' . $transactionId . '/refund',
                $refundData,
                [
                    'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process refund'),
                    'refund_id' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $refundResponse = $response['data'];

            return [
                'success' => true,
                'refund_id' => $refundResponse['id'] ?? $transactionId . '-refund',
                'message' => __('Refund processed successfully'),
                'data' => $refundResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar refund failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to process refund: :error', ['error' => $e->getMessage()]),
                'refund_id' => null,
            ];
        }
    }

    /**
     * Get payment status from gateway
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $apiKey = $this->getConfig('secret_key');
            $apiUrl = $this->getConfig('api_url', 'https://api.moyasar.com/v1');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Moyasar API key is not configured'),
                ];
            }

            // Determine if we are checking an invoice or a payment
            $isInvoice = str_starts_with($transactionId, 'inv_');
            $endpoint = $isInvoice ? '/invoices/' : '/payments/';

            $response = $this->httpGet(
                $apiUrl . $endpoint . $transactionId,
                [],
                [
                    'Authorization' => 'Basic ' . base64_encode($apiKey . ':'),
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get status'),
                    'data' => $response['data'] ?? [],
                ];
            }

            $paymentData = $response['data'];
            $status = $this->mapMoyasarStatus($paymentData['status'] ?? 'unknown');

            return [
                'success' => true,
                'status' => $status,
                'data' => $paymentData,
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar status check failed', [
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
     * Handle webhook notification from gateway
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        try {
            // Moyasar webhook payload structure
            $type = $payload['type'] ?? null;
            $data = $payload['data'] ?? [];

            if ($type !== 'payment_paid') {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => 'ignored',
                    'message' => __('Webhook type not supported: :type', ['type' => $type]),
                ];
            }

            $paymentId = $data['id'] ?? null;
            $status = $this->mapMoyasarStatus($data['status'] ?? 'unknown');
            $metadata = $data['metadata'] ?? [];
            $orderId = $metadata['order_id'] ?? null;

            if (!$orderId) {
                Log::warning('Moyasar webhook: Order ID not found in metadata', [
                    'payment_id' => $paymentId,
                    'payload' => $payload,
                ]);

                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order ID not found in webhook payload'),
                ];
            }

            return [
                'success' => true,
                'order_id' => (int)$orderId,
                'status' => $status,
                'transaction_id' => $paymentId,
            ];

        } catch (\Exception $e) {
            Log::error('Moyasar webhook processing failed', [
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
     * Validate webhook signature
     *
     * @param Request $request
     * @return bool
     */
    public function validateWebhookSignature(Request $request): bool
    {
        try {
            $webhookSecret = $this->getConfig('webhook_secret');

            if (!$webhookSecret) {
                Log::warning('Moyasar webhook secret not configured');
                return false;
            }

            $signature = $request->header('X-Moyasar-Signature');
            $payload = $request->getContent();

            if (!$signature) {
                return false;
            }

            // Moyasar uses HMAC SHA256 for webhook signatures
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

            return hash_equals($expectedSignature, $signature);

        } catch (\Exception $e) {
            Log::error('Moyasar webhook signature validation failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Map Moyasar payment status to standard status
     *
     * @param string $moyasarStatus
     * @return string
     */
    protected function mapMoyasarStatus(string $moyasarStatus): string
    {
        return match(strtolower($moyasarStatus)) {
            'paid', 'captured' => 'success',
            'authorized' => 'pending',
            'failed', 'declined' => 'failed',
            'refunded' => 'refunded',
            'canceled', 'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}


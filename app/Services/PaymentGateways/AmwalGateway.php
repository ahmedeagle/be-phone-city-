<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Amwal Payment Gateway
 *
 * Amwal provides fast and secure payment processing with installment options.
 * Documentation: https://docs.amwal.tech
 */
class AmwalGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'amwal';

    public function __construct()
    {
        parent::__construct();

        // Debug: Log config loading
        Log::info('AmwalGateway config loaded', [
            'enabled' => $this->config['enabled'] ?? 'NOT SET',
            'merchant_id' => !empty($this->config['merchant_id']) ? 'SET' : 'NOT SET',
            'api_key' => !empty($this->config['api_key']) ? 'SET' : 'NOT SET',
        ]);
    }

    /**
     * Create a payment session for the order
     *
     * @param Order $order
     * @return array
     */
    public function createPayment(Order $order): array
    {
        try {
            $apiKey = $this->getConfig('api_key');
            $merchantId = $this->getConfig('merchant_id');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://backend.sa.amwal.tech'), '/');

            if (!$apiKey || !$merchantId) {
                return [
                    'success' => false,
                    'message' => __('Amwal configuration is incomplete'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $user = $order->user;
            $location = $order->location;

            // Prepare names
            $nameParts = explode(' ', $user->name ?? 'Customer', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? $firstName;

            // Prepare phone number (Amwal expects format like 966501234567)
            $phone = $user->phone ?? '500000000';
            // Remove all non-numeric characters
            $phone = preg_replace('/[^0-9]/', '', $phone);

            // Handle different phone number formats
            if (empty($phone) || strlen($phone) < 9) {
                $phone = '966500000000'; // Default fallback
            } elseif (strlen($phone) === 9 && strpos($phone, '5') === 0) {
                // 9 digits starting with 5: add country code
                $phone = '966' . $phone;
            } elseif (strlen($phone) === 10 && strpos($phone, '05') === 0) {
                // 10 digits starting with 05: remove leading 0 and add country code
                $phone = '966' . substr($phone, 1);
            } elseif (strpos($phone, '966') === 0 && strlen($phone) === 12) {
                // Already has country code (966XXXXXXXXX)
                // Keep as is
            } elseif (strlen($phone) > 12) {
                // Too long, take last 12 digits if starts with 966, or format properly
                if (strpos($phone, '966') === 0) {
                    $phone = substr($phone, 0, 12);
                } else {
                    // Take last 9 digits and add country code
                    $phone = '966' . substr($phone, -9);
                }
            } elseif (strlen($phone) < 12 && strpos($phone, '966') !== 0) {
                // Less than 12 digits and doesn't start with 966
                // Take last 9 digits and add country code
                $phone = '966' . substr($phone, -9);
            }

            // Ensure phone is exactly 12 digits (966 + 9 digits)
            if (strlen($phone) !== 12 || strpos($phone, '966') !== 0) {
                $phone = '966500000000'; // Final fallback
            }

            // Determine language
            $locale = app()->getLocale();
            $language = $locale === 'ar' ? 'ar' : 'en';
            $smsLanguage = $language;

            $paymentData = [
                'amount' => number_format($order->total, 2, '.', ''),
                'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                'language' => $language,
                'sms_language' => $smsLanguage,
                'client_first_name' => $firstName,
                'client_last_name' => $lastName,
                'client_email' => $user->email ?? 'customer@example.com',
                // 'client_phone_number' => $phone,
                'callback_url' => $this->getWebhookUrl(),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $user->id,
                ],
            ];

            // Add optional fields if available
            if ($location) {
                $paymentData['metadata']['city'] = $location->city?->name ?? 'Riyadh';
                $paymentData['metadata']['address'] = $location->address ?? '';
            }

            $headers = [
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            // Add optional X-Amwal-Key header if configured (for environment identification)
            $amwalKey = $this->getConfig('amwal_key');
            if ($amwalKey) {
                $headers['X-Amwal-Key'] = $amwalKey;
            }

            $response = $this->httpPost(
                $baseUrl . '/payment_links/' . $merchantId . '/create',
                $paymentData,
                $headers
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create Amwal payment link'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $paymentResponse = $response['data'];
            $paymentLinkId = $paymentResponse['payment_link_id'] ?? $paymentResponse['id'] ?? null;
            $redirectUrl = $paymentResponse['redirect_url'] ?? $paymentResponse['url'] ?? null;

            return [
                'success' => true,
                'transaction_id' => $paymentLinkId,
                'redirect_url' => $redirectUrl,
                'requires_redirect' => !empty($redirectUrl),
                'status' => 'pending',
                'message' => __('Amwal payment link created successfully'),
                'data' => $paymentResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Amwal payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create Amwal payment link: :error', ['error' => $e->getMessage()]),
                'transaction_id' => null,
                'redirect_url' => null,
            ];
        }
    }

    /**
     * Capture a payment
     *
     * Note: Amwal payments are typically captured automatically upon approval.
     * This method may not be needed for Amwal, but is included for interface compliance.
     *
     * @param string $transactionId
     * @return array
     */
    public function capturePayment(string $transactionId): array
    {
        try {
            $apiKey = $this->getConfig('api_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://backend.sa.amwal.tech'), '/');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => __('Amwal API key is not configured'),
                ];
            }

            // Amwal payments are typically auto-captured, but we can check status
            $statusResponse = $this->getPaymentStatus($transactionId);

            if (!$statusResponse['success']) {
                return [
                    'success' => false,
                    'message' => $statusResponse['message'] ?? __('Failed to capture Amwal payment'),
                ];
            }

            // If status is success, consider it captured
            if ($statusResponse['status'] === 'success') {
                return [
                    'success' => true,
                    'message' => __('Payment captured successfully'),
                    'data' => $statusResponse['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => __('Payment cannot be captured. Current status: :status', ['status' => $statusResponse['status']]),
            ];

        } catch (\Exception $e) {
            Log::error('Amwal payment capture failed', [
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
            $apiKey = $this->getConfig('api_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://backend.sa.amwal.tech'), '/');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'message' => __('Amwal API key is not configured'),
                ];
            }

            // Check Amwal documentation for refund endpoint
            // This is a placeholder implementation
            $refundData = [
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => config('payment-gateways.currency', 'SAR'),
            ];

            $headers = [
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            $amwalKey = $this->getConfig('amwal_key');
            if ($amwalKey) {
                $headers['X-Amwal-Key'] = $amwalKey;
            }

            // Note: Update this endpoint based on Amwal's actual refund API
            $response = $this->httpPost(
                $baseUrl . '/payment_links/' . $transactionId . '/refund',
                $refundData,
                $headers
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process Amwal refund'),
                    'refund_id' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'refund_id' => $response['data']['refund_id'] ?? $transactionId . '-refund',
                'message' => __('Refund processed successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Amwal refund failed', [
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
     *
     * @param string $transactionId (payment_link_id)
     * @return array
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $apiKey = $this->getConfig('api_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://backend.sa.amwal.tech'), '/');

            if (!$apiKey) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Amwal API key is not configured'),
                ];
            }

            $headers = [
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ];

            $amwalKey = $this->getConfig('amwal_key');
            if ($amwalKey) {
                $headers['X-Amwal-Key'] = $amwalKey;
            }

            $response = $this->httpPost(
                $baseUrl . '/payment_links/' . $transactionId . '/details',
                [],
                $headers
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get Amwal payment status'),
                ];
            }

            $paymentData = $response['data'];
            $status = $this->mapAmwalStatus($paymentData['status'] ?? 'unknown');

            return [
                'success' => true,
                'status' => $status,
                'data' => $paymentData,
            ];

        } catch (\Exception $e) {
            Log::error('Amwal status check failed', [
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
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $status = $this->mapAmwalStatus($payload['status'] ?? 'unknown');
            $paymentLinkId = $payload['payment_link_id'] ?? $payload['id'] ?? null;

            // Get order from metadata
            $orderId = $payload['metadata']['order_id'] ?? null;
            $orderNumber = $payload['metadata']['order_number'] ?? null;

            if (!$orderId && !$orderNumber) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order reference not found in Amwal webhook'),
                ];
            }

            $order = null;
            if ($orderId) {
                $order = Order::find($orderId);
            } elseif ($orderNumber) {
                $order = Order::where('order_number', $orderNumber)->first();
            }

            return [
                'success' => true,
                'order_id' => $order?->id,
                'status' => $status,
                'transaction_id' => $paymentLinkId,
            ];

        } catch (\Exception $e) {
            Log::error('Amwal webhook processing failed', [
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
        // Amwal webhook validation
        // Check if webhook secret is configured
        $webhookSecret = $this->getConfig('webhook_secret');
        if (!$webhookSecret) {
            // If no secret configured, accept webhook (not recommended for production)
            return true;
        }

        // Check for signature header (update based on Amwal's actual implementation)
        $signature = $request->header('X-Amwal-Signature') ?? $request->header('Authorization');
        if (!$signature) {
            return false;
        }

        // Validate signature (implement based on Amwal's signature algorithm)
        // This is a placeholder - update with actual validation logic
        return true;
    }

    /**
     * Override isEnabled to add debug logging
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $enabled = $this->config['enabled'] ?? false;

        // Debug logging
        Log::info('AmwalGateway isEnabled check', [
            'enabled' => $enabled,
            'config_enabled' => $this->config['enabled'] ?? 'NOT SET',
            'config_array' => [
                'enabled' => $this->config['enabled'] ?? null,
                'merchant_id_set' => !empty($this->config['merchant_id']),
                'api_key_set' => !empty($this->config['api_key']),
            ],
        ]);

        return $enabled;
    }

    /**
     * Get webhook URL with HTTPS
     * Amwal requires HTTPS for callback URLs
     *
     * @return string
     */
    protected function getWebhookUrl(): string
    {
        // Check for custom webhook URL in config (for production)
        $customWebhookUrl = $this->getConfig('webhook_url');
        if ($customWebhookUrl) {
            return $customWebhookUrl;
        }

        // Generate URL from route
        $url = route('payment.webhook', ['gateway' => 'amwal'], false);

        // Get the base URL from config
        $baseUrl = config('app.url');

        // Ensure HTTPS (Amwal requires HTTPS)
        if (strpos($baseUrl, 'http://') === 0) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        } elseif (strpos($baseUrl, 'https://') !== 0) {
            $baseUrl = 'https://' . $baseUrl;
        }

        return rtrim($baseUrl, '/') . $url;
    }

    /**
     * Map Amwal status to internal status
     *
     * @param string $status
     * @return string
     */
    protected function mapAmwalStatus(string $status): string
    {
        return match(strtolower($status)) {
            'paid', 'completed', 'success', 'approved' => 'success',
            'pending', 'processing', 'initiated' => 'pending',
            'failed', 'rejected', 'declined', 'expired' => 'failed',
            'cancelled', 'canceled' => 'cancelled',
            'refunded', 'partially_refunded' => 'refunded',
            default => 'pending',
        };
    }
}

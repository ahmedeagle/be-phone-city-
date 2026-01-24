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
                'callback_url' => $this->getCallbackUrl($order),
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

            // Use GET request to fetch payment link details
            $response = $this->httpGet(
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

            // Extract status from payment_link or transactions array
            // Amwal API returns: { payment_link: { status: "Paid" }, transactions: [{ status: "success" }] }
            $amwalStatus = 'unknown';

            // Check payment_link status first
            if (isset($paymentData['payment_link']['status'])) {
                $amwalStatus = $paymentData['payment_link']['status'];
            }
            // If not found, check first transaction status
            elseif (isset($paymentData['transactions'][0]['status'])) {
                $amwalStatus = $paymentData['transactions'][0]['status'];
            }
            // Fallback to root status if exists
            elseif (isset($paymentData['status'])) {
                $amwalStatus = $paymentData['status'];
            }

            $status = $this->mapAmwalStatus($amwalStatus);

            Log::info('Amwal status extracted', [
                'transaction_id' => $transactionId,
                'amwal_status' => $amwalStatus,
                'mapped_status' => $status,
                'payment_link_status' => $paymentData['payment_link']['status'] ?? null,
                'transaction_status' => $paymentData['transactions'][0]['status'] ?? null,
            ]);

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
            Log::info('Amwal webhook received', ['payload' => $payload]);

            // Extract status - Amwal may send 'Paid', 'success', or transaction status
            $amwalStatus = $payload['status'] ??
                          $payload['payment_link']['status'] ??
                          $payload['transactions'][0]['status'] ??
                          'unknown';

            $status = $this->mapAmwalStatus($amwalStatus);
            $paymentLinkId = $payload['payment_link_id'] ??
                            $payload['payment_link']['id'] ??
                            $payload['id'] ??
                            null;

            // Get order from metadata
            $orderId = $payload['metadata']['order_id'] ?? null;
            $orderNumber = $payload['metadata']['order_number'] ?? null;

            // If metadata not in root, check payment_link.metadata
            if (!$orderId && !$orderNumber && isset($payload['payment_link']['metadata'])) {
                $orderId = $payload['payment_link']['metadata']['order_id'] ?? null;
                $orderNumber = $payload['payment_link']['metadata']['order_number'] ?? null;
            }

            if (!$orderId && !$orderNumber) {
                Log::warning('Amwal webhook: Order reference not found', ['payload' => $payload]);
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

            if (!$order) {
                Log::warning('Amwal webhook: Order not found', [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                ]);
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order not found'),
                ];
            }

            Log::info('Amwal webhook: Order found', [
                'order_id' => $order->id,
                'payment_link_id' => $paymentLinkId,
                'amwal_status' => $amwalStatus,
                'mapped_status' => $status,
            ]);

            return [
                'success' => true,
                'order_id' => $order->id,
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

        return $enabled;
    }

    /**
     * Get callback URL for user redirect (after payment success/failure)
     * Amwal redirects users here and sends payment data
     * Amwal requires HTTPS for callback URLs
     *
     * @param Order $order
     * @return string
     */
    protected function getCallbackUrl(Order $order): string
    {
        // Check for custom callback URL in config
        $customCallbackUrl = $this->getConfig('callback_url');
        if ($customCallbackUrl) {
            // Replace {order_id} placeholder if exists
            return str_replace('{order_id}', $order->id, $customCallbackUrl);
        }

        // For local development: Check if ngrok is being used
        $ngrokUrl = env('NGROK_URL');
        if ($ngrokUrl) {
            $url = route('payment.callback', ['order' => $order->id], false);
            return rtrim($ngrokUrl, '/') . $url;
        }

        // Generate URL from route
        $url = route('payment.callback', ['order' => $order->id], false);

        // Get the base URL from config
        $baseUrl = config('app.url');

        // Check if we're in local development (localhost or 127.0.0.1)
        $isLocal = strpos($baseUrl, '127.0.0.1') !== false
                || strpos($baseUrl, 'localhost') !== false
                || strpos($baseUrl, 'http://') === 0;

        if ($isLocal) {
            // For local development, NGROK_URL or AMWAL_CALLBACK_URL must be set
            Log::warning('Amwal callback URL: Local development detected but no NGROK_URL or AMWAL_CALLBACK_URL set. Please set NGROK_URL in .env with your ngrok HTTPS URL.');

            // Still try to convert to HTTPS (won't work but at least shows the issue)
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        } else {
            // Ensure HTTPS (Amwal requires HTTPS)
            if (strpos($baseUrl, 'http://') === 0) {
                $baseUrl = str_replace('http://', 'https://', $baseUrl);
            } elseif (strpos($baseUrl, 'https://') !== 0) {
                $baseUrl = 'https://' . $baseUrl;
            }
        }

        return rtrim($baseUrl, '/') . $url;
    }

    /**
     * Get webhook URL with HTTPS (for server-to-server notifications)
     * Amwal requires HTTPS for webhook URLs
     *
     * @return string
     */
    protected function getWebhookUrl(): string
    {
        // Check for custom webhook URL in config (for production or local development with ngrok)
        $customWebhookUrl = $this->getConfig('webhook_url');
        if ($customWebhookUrl) {
            return $customWebhookUrl;
        }

        // For local development: Check if ngrok is being used
        // You can set NGROK_URL in .env for automatic detection
        $ngrokUrl = env('NGROK_URL');
        if ($ngrokUrl) {
            $url = route('payment.webhook', ['gateway' => 'amwal'], false);
            return rtrim($ngrokUrl, '/') . $url;
        }

        // Generate URL from route
        $url = route('payment.webhook', ['gateway' => 'amwal'], false);

        // Get the base URL from config
        $baseUrl = config('app.url');

        // Check if we're in local development (localhost or 127.0.0.1)
        $isLocal = strpos($baseUrl, '127.0.0.1') !== false
                || strpos($baseUrl, 'localhost') !== false
                || strpos($baseUrl, 'http://') === 0;

        if ($isLocal) {
            // For local development, AMWAL_WEBHOOK_URL or NGROK_URL must be set
            Log::warning('Amwal webhook URL: Local development detected but no AMWAL_WEBHOOK_URL or NGROK_URL set. Please set AMWAL_WEBHOOK_URL in .env with your ngrok HTTPS URL.');

            // Still try to convert to HTTPS (won't work but at least shows the issue)
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        } else {
            // Ensure HTTPS (Amwal requires HTTPS)
            if (strpos($baseUrl, 'http://') === 0) {
                $baseUrl = str_replace('http://', 'https://', $baseUrl);
            } elseif (strpos($baseUrl, 'https://') !== 0) {
                $baseUrl = 'https://' . $baseUrl;
            }
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

<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentGateway
{
    /**
     * Gateway name
     */
    protected string $gateway;

    /**
     * Gateway configuration
     */
    protected array $config;

    /**
     * HTTP request timeout in seconds
     */
    protected int $timeout = 30;

    public function __construct()
    {
        $this->config = config("payment-gateways.gateways.{$this->gateway}", []);
        $this->timeout = $this->config['timeout'] ?? 30;
    }

    /**
     * Create a payment session for the order
     *
     * @param Order $order
     * @return array ['success' => bool, 'transaction_id' => string, 'redirect_url' => string|null, 'data' => array]
     */
    abstract public function createPayment(Order $order): array;

    /**
     * Capture a payment (finalize the transaction)
     *
     * @param string $transactionId
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    abstract public function capturePayment(string $transactionId): array;

    /**
     * Refund a payment
     *
     * @param string $transactionId
     * @param float $amount
     * @return array ['success' => bool, 'refund_id' => string, 'message' => string]
     */
    abstract public function refundPayment(string $transactionId, float $amount): array;

    /**
     * Get payment status from gateway
     *
     * @param string $transactionId
     * @return array ['success' => bool, 'status' => string, 'data' => array]
     */
    abstract public function getPaymentStatus(string $transactionId): array;

    /**
     * Handle webhook notification from gateway
     *
     * @param array $payload
     * @return array ['success' => bool, 'order_id' => int|null, 'status' => string]
     */
    abstract public function handleWebhook(array $payload): array;

    /**
     * Validate webhook signature
     *
     * @param Request $request
     * @return bool
     */
    abstract public function validateWebhookSignature(Request $request): bool;

    /**
     * Check if gateway requires proof upload (e.g., bank transfer)
     *
     * @return bool
     */
    public function requiresProofUpload(): bool
    {
        return false;
    }

    /**
     * Check if gateway requires admin review
     *
     * @return bool
     */
    public function requiresAdminReview(): bool
    {
        return false;
    }

    /**
     * Check if gateway is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Get gateway configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Make HTTP POST request to gateway API
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    protected function httpPost(string $url, array $data = [], array $headers = []): array
    {
        try {
            $this->logRequest('POST', $url, $data, $headers);

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($url, $data);

            $responseData = $response->json() ?? [];
            $this->logResponse($url, $response->status(), $responseData);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'data' => $responseData,
                'raw_body' => $response->body(),
            ];
        } catch (\Exception $e) {
            $this->logError('HTTP POST Error', $url, $e);

            return [
                'success' => false,
                'status_code' => 0,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Make HTTP GET request to gateway API
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return array
     */
    protected function httpGet(string $url, array $params = [], array $headers = []): array
    {
        try {
            $this->logRequest('GET', $url, $params, $headers);

            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->get($url, $params);

            $responseData = $response->json() ?? [];
            $this->logResponse($url, $response->status(), $responseData);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'data' => $responseData,
                'raw_body' => $response->body(),
            ];
        } catch (\Exception $e) {
            $this->logError('HTTP GET Error', $url, $e);

            return [
                'success' => false,
                'status_code' => 0,
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Log API request
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     */
    protected function logRequest(string $method, string $url, array $data, array $headers = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        // Mask sensitive data in headers
        $maskedHeaders = $this->maskSensitiveData($headers);

        Log::channel($this->getLogChannel())->info("Payment Gateway Request [{$this->gateway}]", [
            'method' => $method,
            'url' => $url,
            'headers' => $maskedHeaders,
            'data' => $data,
        ]);
    }

    /**
     * Log API response
     *
     * @param string $url
     * @param int $statusCode
     * @param array $data
     */
    protected function logResponse(string $url, int $statusCode, array $data): void
    {
        if (!$this->shouldLog()) {
            return;
        }

        Log::channel($this->getLogChannel())->info("Payment Gateway Response [{$this->gateway}]", [
            'url' => $url,
            'status_code' => $statusCode,
            'data' => $data,
        ]);
    }

    /**
     * Log error
     *
     * @param string $message
     * @param string $context
     * @param \Exception $exception
     */
    protected function logError(string $message, string $context, \Exception $exception): void
    {
        Log::channel($this->getLogChannel())->error("Payment Gateway Error [{$this->gateway}]", [
            'message' => $message,
            'context' => $context,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    protected function shouldLog(): bool
    {
        return config('payment-gateways.logging.enabled', true);
    }

    /**
     * Get log channel
     *
     * @return string
     */
    protected function getLogChannel(): string
    {
        return config('payment-gateways.logging.channel', 'stack');
    }

    /**
     * Mask sensitive data in arrays
     *
     * @param array $data
     * @return array
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['api_key', 'secret_key', 'token', 'password', 'authorization'];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '***MASKED***';
            }
        }

        return $data;
    }

    /**
     * Generate unique transaction ID
     *
     * @param string $prefix
     * @return string
     */
    protected function generateTransactionId(string $prefix = 'TXN'): string
    {
        return $prefix . '-' . strtoupper(uniqid()) . '-' . time();
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getGatewayName(): string
    {
        return $this->gateway;
    }
}

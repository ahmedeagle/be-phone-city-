<?php

namespace App\Services\Shipping\Oto;

use App\Services\Shipping\Oto\Exceptions\OtoApiException;
use App\Services\Shipping\Oto\Exceptions\OtoConfigurationException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP Client for OTO API with authentication, retries, and error handling
 */
class OtoHttpClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected int $timeout;
    protected int $retryTimes;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->validateConfiguration();

        $environment = config('services.oto.environment', 'sandbox');
        $this->baseUrl = $this->getBaseUrl($environment);
        $this->apiKey = config('services.oto.key');
        $this->apiSecret = config('services.oto.secret');
        $this->timeout = config('services.oto.timeout', 30);
        $this->retryTimes = config('services.oto.retry_times', 2);
    }

    /**
     * Validate OTO configuration
     */
    protected function validateConfiguration(): void
    {
        if (empty(config('services.oto.key'))) {
            throw OtoConfigurationException::missingConfig('OTO_API_KEY');
        }

        if (empty(config('services.oto.secret'))) {
            throw OtoConfigurationException::missingConfig('OTO_API_SECRET');
        }

        $environment = config('services.oto.environment');
        if (!in_array($environment, ['sandbox', 'staging', 'production'])) {
            throw OtoConfigurationException::invalidEnvironment($environment);
        }
    }

    /**
     * Get base URL based on environment
     */
    protected function getBaseUrl(string $environment): string
    {
        // Get explicit base URL from config
        $explicitBaseUrl = config('services.oto.urls.base');

        // Only use explicit URL if it's not the default/empty
        if ($explicitBaseUrl &&
            $explicitBaseUrl !== 'https://api.tryoto.com/rest/v2') {
            return rtrim($explicitBaseUrl, '/');
        }

        // Use environment-specific URLs (staging maps to sandbox)
        return $environment === 'production'
            ? rtrim(config('services.oto.urls.production'), '/')
            : rtrim(config('services.oto.urls.sandbox'), '/');
    }

    /**
     * Create authenticated HTTP client
     */
    protected function client(): PendingRequest
    {
        $token = $this->getAccessToken();

        return Http::timeout($this->timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}",
            ])
            ->baseUrl($this->baseUrl);
    }

    /**
     * Get access token, either from cache or by refreshing
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $cacheKey = 'oto_access_token_' . md5($this->apiKey . $this->baseUrl);

        $this->accessToken = Cache::get($cacheKey);

        if (!$this->accessToken) {
            $this->accessToken = $this->refreshAccessToken($cacheKey);
        }

        return $this->accessToken;
    }

    /**
     * Refresh access token using the refresh token (apiKey)
     */
    protected function refreshAccessToken(string $cacheKey): string
    {
        $endpoint = config('services.oto.endpoints.refresh_token', '/refreshToken');

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . $endpoint, [
                    'refresh_token' => $this->apiKey,
                ]);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'refresh token');
            }

            $data = $response->json();
            $token = $data['access_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 3600;

            if (!$token) {
                throw new \RuntimeException('OTO API returned success but no access token was found in the response.');
            }

            // Cache token slightly less than expiry (e.g., 5 mins buffer)
            $ttl = max(60, $expiresIn - 300);
            Cache::put($cacheKey, $token, $ttl);

            return $token;
        } catch (\Exception $e) {
            Log::error('OTO token refresh failed', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ]);
            throw $e;
        }
    }

    /**
     * Create order via OTO API
     */
    public function createOrder(array $payload): array
    {
        $endpoint = config('services.oto.endpoints.create_order', '/createOrder');
        $this->logRequest('POST', $endpoint, $payload);

        try {
            $response = $this->client()->post($endpoint, $payload);
            // Log response IMMEDIATELY to debug
            Log::info('OTO createOrder RAW Response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
            ]);

            $this->logResponse('POST', $endpoint, $response);

            if ($response->failed()) {
                Log::error('OTO createOrder API FAILED', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw OtoApiException::fromResponse($response, 'create order');
            }

            $data = $response->json();

            // IMPORTANT: Log the full response to see structure
            Log::info('OTO createOrder SUCCESS - Parsed Data', [
                'status' => $response->status(),
                'data' => $data,
            ]);

            return $data;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => '/createOrder',
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Create shipment via OTO API
     */
    public function createShipment(array $payload): array
    {
        $endpoint = config('services.oto.endpoints.create_shipment', '/createShipment');

        $this->logRequest('POST', $endpoint, $payload);

        try {
            $response = $this->client()->post($endpoint, $payload);

            $this->logResponse('POST', $endpoint, $response);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'create shipment');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => '/createShipment',
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Check delivery options for an order
     */
    public function checkDelivery(array $payload): array
    {
        $endpoint = config('services.oto.endpoints.check_delivery', '/shipmentTransactions');

        $this->logRequest('GET', $endpoint, $payload);

        try {
            $response = $this->client()->get($endpoint, $payload);
            $this->logResponse('GET', $endpoint, $response);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'check delivery options');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Get order status by order ID (order_number or oto_order_id)
     * This endpoint returns tracking information including dcTrackingNumber
     */
    public function getOrderStatus(string $orderId): array
    {
        $endpoint = config('services.oto.endpoints.order_status', '/orderStatus');
        $payload = ['orderId' => $orderId];

        $this->logRequest('POST', $endpoint, $payload);

        try {
            $response = $this->client()->post($endpoint, $payload);

            $this->logResponse('POST', $endpoint, $response);

            // Always log sync requests (not just in debug mode)
            Log::info('OTO API: Get order status response', [
                'endpoint' => $endpoint,
                'order_id' => $orderId,
                'status_code' => $response->status(),
                'response_data' => $response->json(),
            ]);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'get order status');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Track shipment by tracking number and delivery company
     * This endpoint provides detailed tracking with history
     */
    public function trackShipment(string $trackingNumber, string $deliveryCompanyName, bool $statusHistory = true, ?string $brandName = null): array
    {
        $endpoint = config('services.oto.endpoints.track_shipment', '/trackShipment');
        $payload = [
            'trackingNumber' => $trackingNumber,
            'deliveryCompanyName' => $deliveryCompanyName,
            'statusHistory' => $statusHistory,
        ];

        if ($brandName) {
            $payload['brandName'] = $brandName;
        }

        $this->logRequest('POST', $endpoint, $payload);

        try {
            $response = $this->client()->post($endpoint, $payload);

            $this->logResponse('POST', $endpoint, $response);

            // Always log sync requests (not just in debug mode)
            Log::info('OTO API: Track shipment response', [
                'endpoint' => $endpoint,
                'tracking_number' => $trackingNumber,
                'delivery_company' => $deliveryCompanyName,
                'status_code' => $response->status(),
                'response_data' => $response->json(),
            ]);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'track shipment');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Get shipment status by tracking number (deprecated - use getOrderStatus + trackShipment instead)
     * Kept for backward compatibility
     */
    public function getShipmentStatus(string $trackingNumber): array
    {
        $endpoint = "/orderStatus/{$trackingNumber}";
        $this->logRequest('POST', $endpoint, ['orderId' => $trackingNumber]);

        try {
            $response = $this->client()->post($endpoint, ['orderId' => $trackingNumber]);

            $this->logResponse('POST', $endpoint, $response);

            // Always log sync requests (not just in debug mode)
            Log::info('OTO API: Get shipment status response', [
                'endpoint' => $endpoint,
                'tracking_number' => $trackingNumber,
                'status_code' => $response->status(),
                'response_data' => $response->json(),
            ]);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'get shipment status');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Get shipment by reference ID
     */
    public function getShipmentByReference(string $reference): array
    {
        $endpoint = "/shipments/reference/{$reference}";
        $this->logRequest('GET', $endpoint);

        try {
            $response = $this->client()->get($endpoint);

            $this->logResponse('GET', $endpoint, $response);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'get shipment by reference');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Cancel shipment
     */
    public function cancelShipment(string $trackingNumber, ?string $reason = null): array
    {
        $endpoint = "/shipments/{$trackingNumber}/cancel";
        $payload = $reason ? ['reason' => $reason] : [];

        $this->logRequest('POST', $endpoint, $payload);

        try {
            $response = $this->client()->post($endpoint, $payload);

            $this->logResponse('POST', $endpoint, $response);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'cancel shipment');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Cancel order in OTO
     *
     * Note: This endpoint may require special permissions in OTO account.
     * If you get 403 error, contact OTO support to enable order cancellation permissions.
     */
    public function cancelOrder(string $otoOrderId, ?string $reason = null): array
    {
        // Try multiple possible endpoints
        $endpoints = [
            config('services.oto.endpoints.cancel_order', '/orders/{id}/cancelOrder'),
            "/orders/{$otoOrderId}/cancel",
            "/createOrder/{$otoOrderId}/cancel",
            "/order/{$otoOrderId}/cancel",
        ];

        $payload = $reason ? ['reason' => $reason] : [];

        $lastException = null;

        foreach ($endpoints as $endpointTemplate) {
            $endpoint = str_replace('{id}', $otoOrderId, $endpointTemplate);

            $this->logRequest('POST', $endpoint, $payload);

            try {
                $response = $this->client()->post($endpoint, $payload);

                $this->logResponse('POST', $endpoint, $response);

                if ($response->successful()) {
                    Log::info('OTO order cancellation successful', [
                        'endpoint' => $endpoint,
                        'oto_order_id' => $otoOrderId,
                    ]);
                    return $response->json();
                }

                // If 403, try next endpoint
                if ($response->status() === 403) {
                    Log::warning('OTO order cancellation endpoint returned 403, trying next endpoint', [
                        'endpoint' => $endpoint,
                        'oto_order_id' => $otoOrderId,
                    ]);
                    $lastException = OtoApiException::fromResponse($response, 'cancel order');
                    continue;
                }

                // For other errors, throw immediately
                throw OtoApiException::fromResponse($response, 'cancel order');

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('OTO API connection failed', [
                    'endpoint' => $endpoint,
                    'oto_order_id' => $otoOrderId,
                    'error' => $e->getMessage(),
                ]);
                $lastException = OtoApiException::connectionFailed($e);
                continue;
            } catch (OtoApiException $e) {
                // If it's not a 403, throw immediately
                if ($e->getCode() !== 403) {
                    throw $e;
                }
                $lastException = $e;
                continue;
            }
        }

        // If all endpoints failed, throw the last exception
        if ($lastException) {
            Log::error('All OTO cancel order endpoints failed', [
                'oto_order_id' => $otoOrderId,
                'tried_endpoints' => $endpoints,
            ]);
            throw $lastException;
        }

        throw new \RuntimeException('Failed to cancel order: No valid endpoint found');
    }

    /**
     * Get all registered senders/pickup locations from OTO
     */
    public function getSenders(): array
    {
        $endpoint = '/getSenders';
        $this->logRequest('GET', $endpoint);

        try {
            $response = $this->client()->get($endpoint);
            $this->logResponse('GET', $endpoint, $response);

            if ($response->failed()) {
                throw OtoApiException::fromResponse($response, 'get senders');
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Fetch AWB PDF from OTO using authenticated client
     * The printAWBURL from orderStatus requires Bearer token authentication
     */
    public function fetchAwbPdf(string $awbUrl): string
    {
        Log::info('OTO API: Fetching AWB PDF', ['url' => $awbUrl]);

        try {
            $token = $this->getAccessToken();

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept' => 'application/pdf',
                ])
                ->get($awbUrl);

            if ($response->failed()) {
                Log::error('OTO API: AWB PDF fetch failed', [
                    'url' => $awbUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw OtoApiException::fromResponse($response, 'fetch AWB PDF');
            }

            return $response->body();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OTO API connection failed for AWB fetch', [
                'url' => $awbUrl,
                'error' => $e->getMessage(),
            ]);
            throw OtoApiException::connectionFailed($e);
        }
    }

    /**
     * Log API request
     */
    protected function logRequest(string $method, string $endpoint, ?array $payload = null): void
    {
        if (config('app.debug')) {
            Log::debug('OTO API Request', [
                'method' => $method,
                'endpoint' => $endpoint,
                'base_url' => $this->baseUrl,
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Log API response
     */
    protected function logResponse(string $method, string $endpoint, Response $response): void
    {
        if (config('app.debug')) {
            Log::debug('OTO API Response', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }
    }
}

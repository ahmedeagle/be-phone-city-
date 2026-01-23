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
                'endpoint' => '/shipments',
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
     * Get shipment status by tracking number
     */
    public function getShipmentStatus(string $trackingNumber): array
    {
        $endpoint = "/shipments/{$trackingNumber}";
        $this->logRequest('GET', $endpoint);

        try {
            $response = $this->client()->get($endpoint);

            $this->logResponse('GET', $endpoint, $response);

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

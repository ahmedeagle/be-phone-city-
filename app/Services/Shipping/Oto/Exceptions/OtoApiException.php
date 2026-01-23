<?php

namespace App\Services\Shipping\Oto\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Exception thrown when OTO API request fails
 */
class OtoApiException extends Exception
{
    protected ?Response $response = null;

    /**
     * Create exception from HTTP response
     */
    public static function fromResponse(Response $response, string $context = ''): self
    {
        $statusCode = $response->status();
        $body = $response->body();
        
        $message = "OTO API request failed";
        if ($context) {
            $message .= " ({$context})";
        }
        $message .= ": HTTP {$statusCode}";
        
        // Try to extract error message from response
        $jsonData = $response->json();
        if ($jsonData) {
            if (isset($jsonData['message'])) {
                $message .= " - " . $jsonData['message'];
            } elseif (isset($jsonData['error'])) {
                $message .= " - " . $jsonData['error'];
            }
        } elseif ($statusCode === 404) {
            // 404 usually means wrong endpoint path
            $message .= " - Endpoint not found. Please check OTO_ENDPOINT_CREATE_SHIPMENT in your .env file or verify the correct endpoint path in OTO API documentation.";
        } elseif ($statusCode === 401 || $statusCode === 403) {
            // Auth errors
            $message .= " - Authentication or permission failed. Please verify your OTO_API_KEY (Refresh Token) and account permissions in OTO dashboard.";
        }

        $exception = new self($message, $statusCode);
        $exception->response = $response;
        
        return $exception;
    }

    /**
     * Create exception for network/connection errors
     */
    public static function connectionFailed(\Throwable $previous): self
    {
        return new self(
            "Failed to connect to OTO API: {$previous->getMessage()}",
            0,
            $previous
        );
    }

    /**
     * Create exception for timeout
     */
    public static function timeout(): self
    {
        return new self("OTO API request timed out. Please try again later.");
    }

    /**
     * Get the HTTP response if available
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Get response body as array
     */
    public function getResponseData(): ?array
    {
        return $this->response?->json();
    }
}



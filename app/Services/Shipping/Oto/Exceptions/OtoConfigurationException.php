<?php

namespace App\Services\Shipping\Oto\Exceptions;

use Exception;

/**
 * Exception thrown when OTO configuration is invalid or missing
 */
class OtoConfigurationException extends Exception
{
    /**
     * Create exception for missing configuration key
     */
    public static function missingConfig(string $key): self
    {
        return new self("OTO configuration missing: {$key}. Please check your config/services.php and .env file.");
    }

    /**
     * Create exception for invalid environment
     */
    public static function invalidEnvironment(string $environment): self
    {
        return new self("Invalid OTO environment: {$environment}. Must be 'sandbox', 'staging', or 'production'.");
    }

    /**
     * Create exception for missing pickup configuration
     */
    public static function missingPickupConfig(string $field): self
    {
        return new self("OTO pickup configuration missing: {$field}. Please configure OTO_PICKUP_{$field} in your .env file.");
    }
}

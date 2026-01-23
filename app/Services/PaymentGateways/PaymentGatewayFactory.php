<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentMethod;
use Exception;

class PaymentGatewayFactory
{
    /**
     * Create a payment gateway instance based on gateway name
     *
     * @param string $gateway
     * @return AbstractPaymentGateway
     * @throws Exception
     */
    public static function make(string $gateway): AbstractPaymentGateway
    {
        return match($gateway) {
            'cash' => new CashGateway(),
            'bank_transfer' => new BankTransferGateway(),
            'tamara' => new TamaraGateway(),
            'tabby' => new TabbyGateway(),
            'amwal' => new AmwalGateway(),
            'moyasar' => new MoyasarGateway(),
            default => throw new Exception("Payment gateway '{$gateway}' is not supported"),
        };
    }

    /**
     * Create a payment gateway instance from PaymentMethod model
     *
     * @param PaymentMethod $paymentMethod
     * @return AbstractPaymentGateway
     * @throws Exception
     */
    public static function makeFromPaymentMethod(PaymentMethod $paymentMethod): AbstractPaymentGateway
    {
        if (empty($paymentMethod->gateway)) {
            throw new Exception("Payment method does not have a gateway configured");
        }

        return static::make($paymentMethod->gateway);
    }

    /**
     * Get list of supported gateways
     *
     * @return array
     */
    public static function getSupportedGateways(): array
    {
        return [
            'cash',
            'bank_transfer',
            'tamara',
            'tabby',
            'amwal',
            'moyasar',
        ];
    }

    /**
     * Check if a gateway is supported
     *
     * @param string $gateway
     * @return bool
     */
    public static function isSupported(string $gateway): bool
    {
        return in_array($gateway, static::getSupportedGateways());
    }

    /**
     * Get enabled gateways from configuration
     *
     * @return array
     */
    public static function getEnabledGateways(): array
    {
        $gateways = config('payment-gateways.gateways', []);
        $enabled = [];

        foreach ($gateways as $name => $config) {
            if ($config['enabled'] ?? false) {
                $enabled[] = $name;
            }
        }

        return $enabled;
    }
}

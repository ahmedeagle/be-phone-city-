<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Amwal Payment Gateway
 *
 * This is a placeholder implementation.
 * Will be fully implemented in Phase 10.
 */
class AmwalGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'amwal';

    public function createPayment(Order $order): array
    {
        // TODO: Implement in Phase 10
        return [
            'success' => false,
            'message' => __('Amwal gateway not yet implemented'),
            'transaction_id' => null,
            'redirect_url' => null,
        ];
    }

    public function capturePayment(string $transactionId): array
    {
        // TODO: Implement in Phase 10
        return [
            'success' => false,
            'message' => __('Amwal gateway not yet implemented'),
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array
    {
        // TODO: Implement in Phase 10
        return [
            'success' => false,
            'message' => __('Amwal gateway not yet implemented'),
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        // TODO: Implement in Phase 10
        return [
            'success' => false,
            'status' => 'unknown',
        ];
    }

    public function handleWebhook(array $payload): array
    {
        // TODO: Implement in Phase 10
        return [
            'success' => false,
            'order_id' => null,
            'status' => 'not_implemented',
        ];
    }

    public function validateWebhookSignature(Request $request): bool
    {
        // TODO: Implement in Phase 10
        return false;
    }
}

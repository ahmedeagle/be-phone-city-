<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;

class CashGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'cash';

    /**
     * Create a payment session for cash payment
     * Cash payments are automatically marked as successful
     *
     * @param Order $order
     * @return array
     */
    public function createPayment(Order $order): array
    {
        $transactionId = $this->generateTransactionId('CASH');

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'redirect_url' => null, // No redirect needed for cash
            'requires_redirect' => false,
            'status' => 'success',
            'message' => __('Cash payment will be collected on delivery'),
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total,
                'currency' => 'SAR',
                'payment_method' => 'cash',
            ],
        ];
    }

    /**
     * Capture payment (not needed for cash)
     *
     * @param string $transactionId
     * @return array
     */
    public function capturePayment(string $transactionId): array
    {
        return [
            'success' => true,
            'message' => __('Cash payment captured'),
            'data' => [
                'transaction_id' => $transactionId,
                'captured_at' => now()->toDateTimeString(),
            ],
        ];
    }

    /**
     * Refund a cash payment
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        $refundId = $this->generateTransactionId('CASH-REFUND');

        return [
            'success' => true,
            'refund_id' => $refundId,
            'message' => __('Cash refund processed. Customer should be refunded manually.'),
            'data' => [
                'transaction_id' => $transactionId,
                'refund_id' => $refundId,
                'amount' => $amount,
                'refunded_at' => now()->toDateTimeString(),
                'note' => __('Manual cash refund required'),
            ],
        ];
    }

    /**
     * Get payment status (always successful for cash)
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentStatus(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'paid',
            'data' => [
                'transaction_id' => $transactionId,
                'payment_method' => 'cash',
                'status' => 'success',
            ],
        ];
    }

    /**
     * Handle webhook (not applicable for cash)
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        return [
            'success' => false,
            'order_id' => null,
            'status' => 'not_applicable',
            'message' => __('Webhooks are not applicable for cash payments'),
        ];
    }

    /**
     * Validate webhook signature (not applicable for cash)
     *
     * @param Request $request
     * @return bool
     */
    public function validateWebhookSignature(Request $request): bool
    {
        return true; // Always valid as webhooks don't exist for cash
    }

    /**
     * Cash doesn't require proof upload
     *
     * @return bool
     */
    public function requiresProofUpload(): bool
    {
        return false;
    }

    /**
     * Cash doesn't require admin review
     *
     * @return bool
     */
    public function requiresAdminReview(): bool
    {
        return false;
    }
}

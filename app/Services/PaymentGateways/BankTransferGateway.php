<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BankTransferGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'bank_transfer';

    /**
     * Create a payment session for bank transfer
     * Returns bank account details for customer to make payment
     *
     * @param Order $order
     * @return array
     */
    public function createPayment(Order $order): array
    {
        $transactionId = $this->generateTransactionId('BANK');

        // Get bank account details from settings
        $bankAccountDetails = $this->getBankAccountDetails();

        if (empty($bankAccountDetails)) {
            return [
                'success' => false,
                'message' => __('Bank transfer is not configured. Please contact support.'),
                'error' => 'bank_transfer_not_configured',
            ];
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'redirect_url' => null, // No redirect needed
            'requires_redirect' => false,
            'requires_proof_upload' => true,
            'requires_admin_review' => true,
            'status' => 'awaiting_payment',
            'message' => __('Please transfer the amount to the provided bank account and upload payment proof'),
            'bank_account_details' => $bankAccountDetails,
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total,
                'currency' => 'SAR',
                'payment_method' => 'bank_transfer',
                'instructions' => __('After making the bank transfer, please upload the payment receipt for verification.'),
            ],
        ];
    }

    /**
     * Get bank account details from settings or configuration
     *
     * @return array
     */
    protected function getBankAccountDetails(): array
    {
        // Try to get from settings table first
        try {
            $settings = \App\Models\Setting::where('group', 'bank_account')->first();

            if ($settings && !empty($settings->value)) {
                return is_array($settings->value) ? $settings->value : json_decode($settings->value, true);
            }
        } catch (\Exception $e) {
            // Settings table might not exist yet or no settings configured
        }

        // No settings found — log a warning and return empty details so the
        // caller / frontend can surface a meaningful "not configured" error.
        Log::warning('BankTransferGateway: bank account settings are not configured. Please add them via Admin > Settings > Bank Account.');

        return [];
    }

    /**
     * Capture payment (finalize after admin approval)
     *
     * @param string $transactionId
     * @return array
     */
    public function capturePayment(string $transactionId): array
    {
        return [
            'success' => true,
            'message' => __('Bank transfer payment captured after admin approval'),
            'data' => [
                'transaction_id' => $transactionId,
                'captured_at' => now()->toDateTimeString(),
            ],
        ];
    }

    /**
     * Refund a bank transfer payment
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        $refundId = $this->generateTransactionId('BANK-REFUND');

        return [
            'success' => true,
            'refund_id' => $refundId,
            'message' => __('Bank transfer refund initiated. Manual bank transfer required.'),
            'data' => [
                'transaction_id' => $transactionId,
                'refund_id' => $refundId,
                'amount' => $amount,
                'refunded_at' => now()->toDateTimeString(),
                'note' => __('Admin must manually process bank transfer refund to customer'),
            ],
        ];
    }

    /**
     * Get payment status
     * For bank transfers, status depends on admin review
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentStatus(string $transactionId): array
    {
        // Get transaction from database
        $transaction = \App\Models\PaymentTransaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return [
                'success' => false,
                'status' => 'not_found',
                'data' => [
                    'transaction_id' => $transactionId,
                    'message' => __('Transaction not found'),
                ],
            ];
        }

        $status = $transaction->status;

        // Map internal status to payment status
        $paymentStatus = match($status) {
            'success' => 'paid',
            'failed' => 'failed',
            'pending', 'processing' => $transaction->hasPaymentProof() ? 'awaiting_review' : 'awaiting_payment',
            default => $status,
        };

        return [
            'success' => true,
            'status' => $paymentStatus,
            'data' => [
                'transaction_id' => $transactionId,
                'internal_status' => $status,
                'has_payment_proof' => $transaction->hasPaymentProof(),
                'is_reviewed' => $transaction->isReviewed(),
                'reviewed_at' => $transaction->reviewed_at?->toDateTimeString(),
                'review_notes' => $transaction->review_notes,
            ],
        ];
    }

    /**
     * Handle webhook (not applicable for bank transfer)
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
            'message' => __('Webhooks are not applicable for bank transfer payments'),
        ];
    }

    /**
     * Validate webhook signature (not applicable for bank transfer)
     *
     * @param Request $request
     * @return bool
     */
    public function validateWebhookSignature(Request $request): bool
    {
        return true; // Always valid as webhooks don't exist for bank transfers
    }

    /**
     * Bank transfer requires proof upload
     *
     * @return bool
     */
    public function requiresProofUpload(): bool
    {
        return true;
    }

    /**
     * Bank transfer requires admin review
     *
     * @return bool
     */
    public function requiresAdminReview(): bool
    {
        return true;
    }

    /**
     * Validate uploaded payment proof file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array ['valid' => bool, 'error' => string|null]
     */
    public function validatePaymentProof($file): array
    {
        $allowedTypes = $this->getConfig('allowed_file_types', ['jpg', 'jpeg', 'png', 'pdf']);
        $maxFileSize = $this->getConfig('max_file_size', 10240); // KB

        // Check if file exists
        if (!$file || !$file->isValid()) {
            return [
                'valid' => false,
                'error' => __('Invalid file upload'),
            ];
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            return [
                'valid' => false,
                'error' => __('File type not allowed. Allowed types: :types', [
                    'types' => implode(', ', $allowedTypes)
                ]),
            ];
        }

        // Check file size (convert to KB)
        $fileSizeKB = $file->getSize() / 1024;
        if ($fileSizeKB > $maxFileSize) {
            return [
                'valid' => false,
                'error' => __('File size exceeds maximum allowed size of :size MB', [
                    'size' => $maxFileSize / 1024
                ]),
            ];
        }

        // Check MIME type for security
        $mimeType = $file->getMimeType();
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/pdf',
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return [
                'valid' => false,
                'error' => __('Invalid file type detected'),
            ];
        }

        return [
            'valid' => true,
            'error' => null,
        ];
    }
}

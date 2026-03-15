<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\PaymentGateways\PaymentGatewayFactory;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaymentService
{
    /**
     * Initiate payment for an order
     *
     * @throws Exception
     */
    public function initiatePayment(Order $order): array
    {
        try {
            DB::beginTransaction();

            // Get payment method
            $paymentMethod = $order->paymentMethod;

            if (! $paymentMethod) {
                throw new Exception(__('Payment method not found'));
            }

            if ($paymentMethod->status !== 'active') {
                throw new Exception(__('Payment method is not active'));
            }

            // Get gateway instance
            $gateway = PaymentGatewayFactory::makeFromPaymentMethod($paymentMethod);

            // Check if gateway is enabled
            if (! $gateway->isEnabled()) {
                throw new Exception(__('Payment gateway is currently disabled'));
            }

            // Create payment transaction record (pending)
            $transaction = PaymentTransaction::create([
                'order_id' => $order->id,
                'payment_method_id' => $paymentMethod->id,
                'gateway' => $paymentMethod->gateway,
                'amount' => $order->total,
                'currency' => config('payment-gateways.currency', 'SAR'),
                'status' => PaymentTransaction::STATUS_PENDING,
                'expires_at' => now()->addMinutes(config('payment-gateways.session.expiration_minutes', 30)),
            ]);

            // Call gateway to create payment
            $paymentResponse = $gateway->createPayment($order);

            // Update transaction with gateway response
            $transaction->update([
                'transaction_id' => $paymentResponse['transaction_id'] ?? null,
                'status' => $this->mapGatewayStatusToTransactionStatus($paymentResponse['status'] ?? 'pending'),
                'request_payload' => [
                    'order_data' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'amount' => $order->total,
                    ],
                    'timestamp' => now()->toDateTimeString(),
                ],
                'response_payload' => $paymentResponse['data'] ?? [],
            ]);

            // Update order payment status
            $orderPaymentStatus = $this->determineOrderPaymentStatus($paymentResponse, $gateway);
            $order->update([
                'payment_status' => $orderPaymentStatus,
                'payment_transaction_id' => $transaction->id,
            ]);

            DB::commit();

            return [
                'success' => $paymentResponse['success'],
                'transaction_id' => $transaction->transaction_id,
                'gateway' => $paymentMethod->gateway,
                'redirect_url' => $paymentResponse['redirect_url'] ?? null,
                'requires_redirect' => $paymentResponse['requires_redirect'] ?? false,
                'requires_proof_upload' => $paymentResponse['requires_proof_upload'] ?? false,
                'bank_account_details' => $paymentResponse['bank_account_details'] ?? null,
                'message' => $paymentResponse['message'] ?? __('Payment initiated successfully'),
                'payment_status' => $orderPaymentStatus,
                'expires_at' => $transaction->expires_at?->toDateTimeString(),
                'error' => $paymentResponse['success'] ? null : ($paymentResponse['message'] ?? null),
                'error_code' => $paymentResponse['error_code'] ?? null,
                'can_retry' => ! $paymentResponse['success'],
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process payment callback from gateway
     */
    public function processPaymentCallback(Order $order, string $transactionId, array $data): bool
    {
        try {
            DB::beginTransaction();

            // Find transaction — lock the row to prevent concurrent callback races
            $transaction = PaymentTransaction::where('transaction_id', $transactionId)
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                // Try to find the latest pending or processing transaction for this order
                // This handles cases like Moyasar where transaction_id changes from session ID to payment ID
                $transaction = PaymentTransaction::where('order_id', $order->id)
                    ->whereIn('status', [PaymentTransaction::STATUS_PENDING, PaymentTransaction::STATUS_PROCESSING])
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if ($transaction) {
                    Log::info('Payment callback: Transaction found by order ID (ID updated)', [
                        'order_id' => $order->id,
                        'old_id' => $transaction->transaction_id,
                        'new_id' => $transactionId,
                    ]);
                    // Update the transaction ID to the actual payment ID received from gateway
                    $transaction->update(['transaction_id' => $transactionId]);
                } else {
                    Log::warning('Payment callback: Transaction not found', [
                        'order_id' => $order->id,
                        'transaction_id' => $transactionId,
                    ]);

                    return false;
                }
            }

            // Get gateway instance
            $gateway = PaymentGatewayFactory::make($transaction->gateway);

            // Get payment status from gateway
            Log::info('Payment callback: Checking payment status from gateway', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'gateway' => $transaction->gateway,
            ]);

            $statusResponse = $gateway->getPaymentStatus($transactionId);

            if (! $statusResponse['success']) {
                Log::warning('Payment callback: Failed to get payment status from gateway', [
                    'order_id' => $order->id,
                    'transaction_id' => $transactionId,
                    'error' => $statusResponse['message'] ?? 'Unknown error',
                ]);
            }

            $newStatus = $this->mapGatewayStatusToTransactionStatus($statusResponse['status'] ?? 'unknown');

            // Update transaction
            $transaction->update([
                'status' => $newStatus,
                'response_payload' => array_merge(
                    $transaction->response_payload ?? [],
                    [
                        'callback' => $data,
                        'status_check' => $statusResponse['data'] ?? [],
                        'callback_timestamp' => now()->toDateTimeString(),
                    ]
                ),
            ]);

            // Update order payment status
            $oldPaymentStatus = $order->payment_status;
            $this->updateOrderPaymentStatus($order, $transaction);
            $order->refresh();

            Log::info('Payment callback: Order updated successfully', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'transaction_status' => $newStatus,
                'old_payment_status' => $oldPaymentStatus,
                'new_payment_status' => $order->payment_status,
            ]);

            DB::commit();

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment callback processing failed', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook(string $gateway, array $payload): array
    {
        try {
            // Get gateway instance
            $gatewayInstance = PaymentGatewayFactory::make($gateway);

            // Process webhook
            $webhookResponse = $gatewayInstance->handleWebhook($payload);

            if ($webhookResponse['success'] && $webhookResponse['order_id']) {
                // Find order
                $order = Order::find($webhookResponse['order_id']);

                if ($order) {
                    // Get latest transaction
                    $transaction = $order->getLatestPaymentTransaction();

                    if ($transaction) {
                        // Update transaction ID if webhook provides a different one (for Amwal)
                        if (! empty($webhookResponse['transaction_id']) &&
                            $webhookResponse['transaction_id'] !== $transaction->transaction_id) {
                            Log::info('Webhook: Updating transaction ID', [
                                'order_id' => $order->id,
                                'old_id' => $transaction->transaction_id,
                                'new_id' => $webhookResponse['transaction_id'],
                            ]);
                            $transaction->transaction_id = $webhookResponse['transaction_id'];
                        }

                        // Update transaction status
                        $newStatus = $this->mapGatewayStatusToTransactionStatus($webhookResponse['status'] ?? 'unknown');
                        $transaction->update([
                            'status' => $newStatus,
                            'transaction_id' => $transaction->transaction_id, // Save updated ID if changed
                            'response_payload' => array_merge(
                                $transaction->response_payload ?? [],
                                [
                                    'webhook' => $payload,
                                    'webhook_timestamp' => now()->toDateTimeString(),
                                ]
                            ),
                        ]);

                        // Update order payment status
                        $this->updateOrderPaymentStatus($order, $transaction);

                        Log::info('Webhook processed successfully', [
                            'order_id' => $order->id,
                            'transaction_id' => $transaction->transaction_id,
                            'status' => $newStatus,
                            'payment_status' => $order->payment_status,
                        ]);
                    } else {
                        Log::warning('Webhook: No transaction found for order', [
                            'order_id' => $order->id,
                            'gateway' => $gateway,
                        ]);
                    }
                } else {
                    Log::warning('Webhook: Order not found', [
                        'order_id' => $webhookResponse['order_id'],
                        'gateway' => $gateway,
                    ]);
                }
            } else {
                Log::warning('Webhook: Invalid response', [
                    'gateway' => $gateway,
                    'response' => $webhookResponse,
                ]);
            }

            return $webhookResponse;

        } catch (Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund an order
     *
     * @throws Exception
     */
    public function refundOrder(Order $order, ?float $amount = null): array
    {
        try {
            if (! $order->hasSuccessfulPayment()) {
                throw new Exception(__('Order payment must be successful before refunding'));
            }

            // Get latest successful transaction
            $transaction = $order->paymentTransactions()
                ->where('status', PaymentTransaction::STATUS_SUCCESS)
                ->latest()
                ->first();

            if (! $transaction) {
                throw new Exception(__('No successful transaction found for this order'));
            }

            // Default to full refund
            $refundAmount = $amount ?? $order->total;

            if ($refundAmount > $order->total) {
                throw new Exception(__('Refund amount cannot exceed order total'));
            }

            // Get gateway instance
            $gateway = PaymentGatewayFactory::make($transaction->gateway);

            // Process refund
            $refundResponse = $gateway->refundPayment($transaction->transaction_id, $refundAmount);

            if ($refundResponse['success']) {
                // Create refund transaction record
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'payment_method_id' => $transaction->payment_method_id,
                    'gateway' => $transaction->gateway,
                    'transaction_id' => $refundResponse['refund_id'] ?? null,
                    'amount' => -$refundAmount, // Negative for refund
                    'currency' => $transaction->currency,
                    'status' => PaymentTransaction::STATUS_REFUNDED,
                    'response_payload' => $refundResponse['data'] ?? [],
                ]);

                // Update order payment status
                $isPartialRefund = $refundAmount < $order->total;
                $order->update([
                    'payment_status' => $isPartialRefund
                        ? Order::PAYMENT_STATUS_PARTIALLY_REFUNDED
                        : Order::PAYMENT_STATUS_REFUNDED,
                ]);
            }

            return $refundResponse;

        } catch (Exception $e) {
            Log::error('Refund processing failed', [
                'order_id' => $order->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check payment status for an order
     */
    public function checkPaymentStatus(Order $order): array
    {
        try {
            $transaction = $order->getLatestPaymentTransaction();

            if (! $transaction) {
                return [
                    'success' => false,
                    'status' => 'no_transaction',
                    'message' => __('No payment transaction found'),
                ];
            }

            // If transaction is already successful or failed, return cached status
            if (in_array($transaction->status, [PaymentTransaction::STATUS_SUCCESS, PaymentTransaction::STATUS_REFUNDED])) {
                return [
                    'success' => true,
                    'status' => $transaction->status,
                    'payment_status' => $order->payment_status,
                    'transaction_id' => $transaction->transaction_id,
                    'can_retry' => false,
                ];
            }

            // Check if expired
            if ($transaction->isExpired()) {
                $transaction->markAsExpired();
                $order->update(['payment_status' => Order::PAYMENT_STATUS_EXPIRED]);

                return [
                    'success' => true,
                    'status' => 'expired',
                    'payment_status' => 'expired',
                    'can_retry' => true,
                ];
            }

            // Query gateway for current status
            $gateway = PaymentGatewayFactory::make($transaction->gateway);
            $statusResponse = $gateway->getPaymentStatus($transaction->transaction_id);

            // Update transaction if status changed
            if ($statusResponse['success']) {
                $newStatus = $this->mapGatewayStatusToTransactionStatus($statusResponse['status'] ?? 'unknown');

                if ($newStatus !== $transaction->status) {
                    $transaction->update(['status' => $newStatus]);
                    $this->updateOrderPaymentStatus($order, $transaction);
                }
            }

            return [
                'success' => true,
                'status' => $transaction->status,
                'payment_status' => $order->payment_status,
                'transaction_id' => $transaction->transaction_id,
                'can_retry' => $order->canRetryPayment(),
                'awaiting_review' => $order->isAwaitingPaymentReview(),
                'has_payment_proof' => $transaction->hasPaymentProof(),
            ];

        } catch (Exception $e) {
            Log::error('Payment status check failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Mark expired payments (run via scheduled task)
     */
    public function markExpiredPayments(): void
    {
        try {
            $expirationMinutes = config('payment-gateways.session.expiration_minutes', 30);

            // Mark transactions as expired
            PaymentTransaction::whereIn('status', [
                PaymentTransaction::STATUS_PENDING,
                PaymentTransaction::STATUS_PROCESSING,
            ])
                ->where('created_at', '<', now()->subMinutes($expirationMinutes))
                ->update(['status' => PaymentTransaction::STATUS_EXPIRED]);

            // Update orders with expired payments
            Order::where('payment_status', Order::PAYMENT_STATUS_PROCESSING)
                ->whereDoesntHave('paymentTransactions', function ($query) {
                    $query->where('status', PaymentTransaction::STATUS_SUCCESS);
                })
                ->where('updated_at', '<', now()->subMinutes($expirationMinutes))
                ->update(['payment_status' => Order::PAYMENT_STATUS_EXPIRED]);

            Log::info('Expired payments marked successfully');

        } catch (Exception $e) {
            Log::error('Failed to mark expired payments', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload payment proof for bank transfer
     *
     * @return string Path to uploaded file
     *
     * @throws Exception
     */
    public function uploadPaymentProof(Order $order, UploadedFile $file): string
    {
        try {
            DB::beginTransaction();

            // Get latest transaction
            $transaction = $order->getLatestPaymentTransaction();

            if (! $transaction) {
                throw new Exception(__('No payment transaction found for this order'));
            }

            if ($transaction->gateway !== 'bank_transfer') {
                throw new Exception(__('Payment proof upload is only for bank transfer'));
            }

            // Refresh transaction to get latest state
            $transaction->refresh();

            // Check if already approved - this should be checked first
            if ($transaction->isReviewed() && $transaction->status === PaymentTransaction::STATUS_SUCCESS) {
                throw new Exception(__('Payment proof already approved. Cannot upload again.'));
            }

            // Allow re-upload if previous proof was rejected
            // Rejected: reviewed + status pending + no proof path
            if ($transaction->isReviewed() && $transaction->status === PaymentTransaction::STATUS_PENDING && empty($transaction->payment_proof_path)) {
                // This means it was rejected, allow new upload
                // Reset review fields to allow new review cycle
                $transaction->update([
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_notes' => null,
                    'error_message' => null,
                ]);
            }
            // Check if proof already uploaded and awaiting review
            elseif ($transaction->hasPaymentProof() && ! $transaction->isReviewed()) {
                throw new Exception(__('Payment proof already uploaded and awaiting review.'));
            }
            // Edge case: Has proof but status is pending after review (shouldn't happen normally)
            elseif ($transaction->hasPaymentProof() && $transaction->isReviewed() && $transaction->status === PaymentTransaction::STATUS_PENDING) {
                // Delete old proof and allow new upload
                if (Storage::disk('local')->exists($transaction->payment_proof_path)) {
                    Storage::disk('local')->delete($transaction->payment_proof_path);
                }
                $transaction->update([
                    'payment_proof_path' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_notes' => null,
                    'error_message' => null,
                ]);
            }

            // Validate file
            $gateway = PaymentGatewayFactory::make('bank_transfer');
            $validation = $gateway->validatePaymentProof($file);

            if (! $validation['valid']) {
                throw new Exception($validation['error']);
            }

            // Store file
            $storagePath = config('payment-gateways.gateways.bank_transfer.storage_path', 'payment-proofs');
            $fileName = $order->order_number.'_'.time().'.'.$file->getClientOriginalExtension();
            $filePath = $file->storeAs($storagePath, $fileName, 'local'); // Store in storage/app (not public)

            // Update transaction
            $transaction->update([
                'payment_proof_path' => $filePath,
                'status' => PaymentTransaction::STATUS_PROCESSING,
            ]);

            // Update order status to awaiting review
            $order->markPaymentAsAwaitingReview($transaction);

            DB::commit();

            Log::info('Payment proof uploaded', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'file_path' => $filePath,
            ]);

            // Send notifications to user and admin
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notifyPaymentProofUploaded($transaction);

            return $filePath;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment proof upload failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Review payment proof (approve or reject)
     *
     * @throws Exception
     */
    public function reviewPaymentProof(Order $order, bool $approve, ?string $notes = null): bool
    {
        try {
            DB::beginTransaction();

            $transaction = $order->getLatestPaymentTransaction();

            if (! $transaction) {
                throw new Exception(__('No payment transaction found'));
            }

            // Allow re-review if previous review was rejection (status is pending after review)
            $wasRejected = $transaction->isReviewed()
                && $transaction->status === PaymentTransaction::STATUS_PENDING
                && empty($transaction->payment_proof_path);

            if (! $wasRejected) {
                // For new reviews, check if proof exists and not already reviewed
                if (! $transaction->hasPaymentProof()) {
                    throw new Exception(__('No payment proof uploaded'));
                }

                if ($transaction->isReviewed() && $transaction->status === PaymentTransaction::STATUS_SUCCESS) {
                    throw new Exception(__('Payment proof already approved. Cannot review again.'));
                }
            }

            // Get current admin user
            $adminId = auth()->id();

            if ($approve) {
                // Approve payment
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_SUCCESS,
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                ]);

                // Use updateOrderPaymentStatus to trigger automatic shipping
                $this->updateOrderPaymentStatus($order, $transaction);

                Log::info('Payment proof approved', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'reviewed_by' => $adminId,
                ]);

            } else {
                // Reject payment - allow user to upload again
                $transaction->update([
                    'status' => PaymentTransaction::STATUS_PENDING, // Set back to pending so user can upload again
                    'reviewed_by' => $adminId,
                    'reviewed_at' => now(),
                    'review_notes' => $notes ?? __('Payment proof rejected'),
                    'error_message' => $notes,
                    'payment_proof_path' => null, // Clear proof path to allow new upload
                ]);

                $order->markPaymentAsFailed();

                Log::info('Payment proof rejected', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'reviewed_by' => $adminId,
                    'notes' => $notes,
                ]);
            }

            DB::commit();

            // Send notification to user about review result
            // Wrap in try-catch to prevent email failures from breaking the review process
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyPaymentProofReviewed($transaction, $approve, $notes);
            } catch (\Exception $e) {
                // Log email failure but don't fail the review process
                // Database notification should still be saved
                Log::warning('Failed to send payment proof review notification', [
                    'transaction_id' => $transaction->id,
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Payment proof review failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get bank account details
     *
     * @throws Exception
     */
    public function getBankAccountDetails(): array
    {
        try {
            // Try to get from settings
            $settings = Setting::getSettings();

            $bankDetails = [
                'bank_name' => $settings->bank_name ?? __('Not configured'),
                'account_holder' => $settings->account_holder ?? __('Not configured'),
                'account_number' => $settings->account_number ?? __('Not configured'),
                'iban' => $settings->iban ?? null,
                'swift_code' => $settings->swift_code ?? null,
                'branch' => $settings->branch ?? null,
                'instructions' => $settings->bank_instructions ?? __('Please include your order number in the transfer description'),
            ];

            return $bankDetails;

        } catch (Exception $e) {
            // Settings might not be configured yet
            return [
                'bank_name' => __('Not configured'),
                'account_holder' => __('Not configured'),
                'account_number' => __('Not configured'),
                'instructions' => __('Please contact support for bank account details'),
            ];
        }
    }

    /**
     * Map gateway status to transaction status
     */
    protected function mapGatewayStatusToTransactionStatus(string $gatewayStatus): string
    {
        return match (strtolower($gatewayStatus)) {
            'success', 'paid', 'completed', 'captured' => PaymentTransaction::STATUS_SUCCESS,
            'pending', 'awaiting_payment' => PaymentTransaction::STATUS_PENDING,
            'processing', 'awaiting_review' => PaymentTransaction::STATUS_PROCESSING,
            'failed', 'declined', 'rejected' => PaymentTransaction::STATUS_FAILED,
            'expired' => PaymentTransaction::STATUS_EXPIRED,
            'cancelled', 'canceled' => PaymentTransaction::STATUS_CANCELLED,
            'refunded' => PaymentTransaction::STATUS_REFUNDED,
            default => PaymentTransaction::STATUS_PENDING,
        };
    }

    /**
     * Determine order payment status from gateway response
     *
     * @param  object  $gateway
     */
    protected function determineOrderPaymentStatus(array $paymentResponse, $gateway): string
    {
        $status = $paymentResponse['status'] ?? 'pending';

        if ($gateway->requiresAdminReview()) {
            return Order::PAYMENT_STATUS_AWAITING_REVIEW;
        }

        return match (strtolower($status)) {
            'success', 'paid' => Order::PAYMENT_STATUS_PAID,
            'processing', 'awaiting_review' => Order::PAYMENT_STATUS_PROCESSING,
            'failed', 'rejected' => Order::PAYMENT_STATUS_FAILED,
            default => Order::PAYMENT_STATUS_PENDING,
        };
    }

    /**
     * Update order payment status based on transaction
     */
    protected function updateOrderPaymentStatus(Order $order, PaymentTransaction $transaction): void
    {
        $oldPaymentStatus = $order->payment_status;

        $orderStatus = match ($transaction->status) {
            PaymentTransaction::STATUS_SUCCESS => Order::PAYMENT_STATUS_PAID,
            PaymentTransaction::STATUS_FAILED => Order::PAYMENT_STATUS_FAILED,
            PaymentTransaction::STATUS_EXPIRED => Order::PAYMENT_STATUS_EXPIRED,
            PaymentTransaction::STATUS_CANCELLED => Order::PAYMENT_STATUS_CANCELLED,
            PaymentTransaction::STATUS_REFUNDED => Order::PAYMENT_STATUS_REFUNDED,
            PaymentTransaction::STATUS_PROCESSING => $transaction->hasPaymentProof()
                ? Order::PAYMENT_STATUS_AWAITING_REVIEW
                : Order::PAYMENT_STATUS_PROCESSING,
            default => $order->payment_status, // Keep current status
        };

        Log::info('PaymentService: Updating order payment status', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_payment_status' => $oldPaymentStatus,
            'new_payment_status' => $orderStatus,
            'transaction_status' => $transaction->status,
            'delivery_method' => $order->delivery_method,
            'has_active_shipment' => $order->hasActiveShipment(),
            'oto_order_id' => $order->oto_order_id,
        ]);

        $order->update([
            'payment_status' => $orderStatus,
            'payment_transaction_id' => $transaction->id,
        ]);

        // Automatically create OTO shipment when payment becomes paid
        $shouldCreateShipping = $orderStatus === Order::PAYMENT_STATUS_PAID
            && $oldPaymentStatus !== Order::PAYMENT_STATUS_PAID
            && $order->delivery_method === Order::DELIVERY_HOME
            && ! $order->hasActiveShipment()
            && empty($order->oto_order_id);

        Log::info('PaymentService: Checking if should create automatic OTO shipping', [
            'order_id' => $order->id,
            'should_create' => $shouldCreateShipping,
            'payment_status_paid' => $orderStatus === Order::PAYMENT_STATUS_PAID,
            'was_not_paid_before' => $oldPaymentStatus !== Order::PAYMENT_STATUS_PAID,
            'is_home_delivery' => $order->delivery_method === Order::DELIVERY_HOME,
            'no_active_shipment' => ! $order->hasActiveShipment(),
            'no_oto_order_id' => empty($order->oto_order_id),
        ]);

        if ($shouldCreateShipping) {
            Log::info('PaymentService: Triggering automatic OTO shipping', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            $this->handleAutomaticOtoShipping($order);
        } else {
            Log::debug('PaymentService: Skipping automatic OTO shipping', [
                'order_id' => $order->id,
                'reason' => [
                    'payment_not_paid' => $orderStatus !== Order::PAYMENT_STATUS_PAID,
                    'was_already_paid' => $oldPaymentStatus === Order::PAYMENT_STATUS_PAID,
                    'not_home_delivery' => $order->delivery_method !== Order::DELIVERY_HOME,
                    'has_active_shipment' => $order->hasActiveShipment(),
                    'has_oto_order_id' => ! empty($order->oto_order_id),
                ],
            ]);
        }
    }

    /**
     * Handle automatic OTO shipping when order is paid
     */
    protected function handleAutomaticOtoShipping(Order $order): void
    {
        Log::info('PaymentService: Starting automatic OTO shipping process', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $order->payment_status,
            'order_status' => $order->status,
            'delivery_method' => $order->delivery_method,
        ]);

        try {
            // Refresh order to get latest state
            $order->refresh();

            Log::debug('PaymentService: Order refreshed', [
                'order_id' => $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
            ]);

            // Update order status to processing if it's still pending/confirmed
            if (in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
                Log::info('PaymentService: Updating order status to PROCESSING', [
                    'order_id' => $order->id,
                    'old_status' => $order->status,
                ]);
                $order->update(['status' => Order::STATUS_PROCESSING]);
                $order->refresh();
            }

            // Load required relationships
            $order->load(['location.city', 'items.product', 'items.productOption', 'user']);

            Log::debug('PaymentService: Relationships loaded', [
                'order_id' => $order->id,
                'has_location' => (bool) $order->location,
                'has_items' => $order->items->isNotEmpty(),
                'location_city' => $order->location?->city?->name ?? 'N/A',
            ]);

            // Check if order is eligible for shipment
            if (! $order->location || $order->delivery_method !== Order::DELIVERY_HOME) {
                Log::warning('PaymentService: Order not eligible for automatic OTO shipping', [
                    'order_id' => $order->id,
                    'delivery_method' => $order->delivery_method,
                    'has_location' => (bool) $order->location,
                    'location_id' => $order->location_id,
                ]);

                return;
            }

            Log::info('PaymentService: Attempting automatic OTO shipping for paid order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'location_id' => $order->location_id,
            ]);

            $shippingService = app(\App\Services\Shipping\OtoShippingService::class);
            $shipmentDto = $shippingService->createOrderAndShipment($order);

            // Refresh to get updated tracking info
            $order->refresh();

            Log::info('PaymentService: Automatic OTO shipping completed successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'tracking_number' => $order->tracking_number,
                'oto_order_id' => $order->oto_order_id,
                'shipping_reference' => $order->shipping_reference,
                'tracking_url' => $order->tracking_url,
            ]);

        } catch (\App\Services\Shipping\Oto\Exceptions\OtoValidationException $e) {
            Log::warning('PaymentService: Order not eligible for automatic OTO shipping (validation)', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - order is paid, just shipping couldn't be created automatically
        } catch (\App\Services\Shipping\Oto\Exceptions\OtoApiException $e) {
            Log::error('PaymentService: Failed to create automatic OTO shipment (API error)', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - order is paid, admin can create shipment manually
        } catch (\Exception $e) {
            Log::error('PaymentService: Unexpected error during automatic OTO shipping', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't throw - order is paid, admin can create shipment manually
        }
    }
}

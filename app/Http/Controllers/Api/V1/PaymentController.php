<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\PaymentGateways\PaymentGatewayFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Handle payment callback from gateway
     * This is where gateways redirect users after payment attempt
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function callback(Request $request, Order $order)
    {
        try {
            // Moyasar uses 'id', Tabby uses 'payment_id', some others might use 'transaction_id'
            $transactionId = $request->input('id') ??
                             $request->input('transaction_id') ??
                             $request->input('payment_id');

            if (!$transactionId) {
                // For Moyasar, it might be in the query string as 'id'
                $transactionId = $request->query('id') ?? $request->query('payment_id');
            }

            // If it's a browser redirect and we don't have a transaction ID,
            // but we have a status (like cancel/failure), we should still redirect to frontend
            if (!$transactionId && ($request->query('status') === 'cancel' || $request->query('status') === 'failure')) {
                return $this->redirectToFrontend($order, null);
            }

            if (!$transactionId) {
                Log::warning('Payment callback: No transaction ID found in request', [
                    'order_id' => $order->id,
                    'request_data' => $request->all(),
                ]);

                if (!$request->expectsJson()) {
                    return $this->redirectToFrontend($order, null);
                }

                return Response::error(
                    __('Transaction ID is required'),
                    null,
                    400
                );
            }

            // Process callback
            $success = $this->paymentService->processPaymentCallback(
                $order,
                $transactionId,
                $request->all()
            );

            if ($request->expectsJson()) {
                if ($success) {
                    return Response::success(
                        __('Payment processed successfully'),
                        [
                            'order_id' => $order->id,
                            'order_number' => $order->order_number,
                            'payment_status' => $order->payment_status,
                            'status' => $order->status,
                            'redirect_url' => $this->getFrontendUrl($order, $transactionId),
                        ]
                    );
                }

                return Response::error(
                    __('Payment processing failed'),
                    [
                        'order_id' => $order->id,
                        'can_retry' => $order->canRetryPayment(),
                        'redirect_url' => $this->getFrontendUrl($order, $transactionId),
                    ],
                    400
                );
            }

            // For browser redirects (standard GET/POST callback)
            return $this->redirectToFrontend($order, $transactionId);

        } catch (\Exception $e) {
            Log::error('Payment callback error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            if (!$request->expectsJson()) {
                return $this->redirectToFrontend($order, null);
            }

            return Response::error(
                __('Payment callback processing failed'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get frontend redirect URL with parameters
     *
     * @param Order $order
     * @param string|null $transactionId
     * @return string
     */
    protected function getFrontendUrl(Order $order, ?string $transactionId): string
    {
        $redirectUrl = config('payment-gateways.session.frontend_redirect_url');
        $redirectUrl = str_replace('{order_id}', $order->id, $redirectUrl);

        $queryParams = [
            'status' => $order->fresh()->payment_status,
            'order_number' => $order->order_number,
            'transaction_id' => $transactionId,
        ];

        return $redirectUrl . (str_contains($redirectUrl, '?') ? '&' : '?') . http_build_query(array_filter($queryParams));
    }

    /**
     * Redirect to frontend
     *
     * @param Order $order
     * @param string|null $transactionId
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToFrontend(Order $order, ?string $transactionId)
    {
        return redirect()->away($this->getFrontendUrl($order, $transactionId));
    }

    /**
     * Handle webhook from payment gateway
     * This is called by the gateway server (not the user)
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request, string $gateway)
    {
        try {
            Log::info('Payment webhook received', [
                'gateway' => $gateway,
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Validate webhook signature if enabled
            if (config('payment-gateways.webhook.verify_signature', true)) {
                $gatewayInstance = PaymentGatewayFactory::make($gateway);

                if (!$gatewayInstance->validateWebhookSignature($request)) {
                    Log::warning('Invalid webhook signature', [
                        'gateway' => $gateway,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid signature',
                    ], 403);
                }
            }

            // Process webhook
            $result = $this->paymentService->handleWebhook($gateway, $request->all());

            // Return success response to gateway
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            // Still return 200 to prevent gateway retries
            return response()->json([
                'success' => false,
                'message' => 'Webhook received but processing failed',
            ], 200);
        }
    }

    /**
     * Retry payment for an order
     * Allows user to pay again if previous attempt failed
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function retry(Request $request, Order $order)
    {
        
        try {
            // Check if user owns the order
            if ($order->user_id !== Auth::id()) {
                return Response::error(
                    __('Unauthorized'),
                    null,
                    403
                );
            }

            // Check if order can retry payment
            if (!$order->canRetryPayment()) {
                return Response::error(
                    __('Cannot retry payment for this order'),
                    [
                        'payment_status' => $order->payment_status,
                        'reason' => __('Payment is already successful or order status does not allow retry'),
                    ],
                    400
                );
            }

            // Check retry window (24 hours by default)
            $retryWindowHours = config('payment-gateways.session.retry_window_hours', 24);
            if (!$order->isWithinRetryWindow($retryWindowHours)) {
                return Response::error(
                    __('Payment retry window has expired'),
                    [
                        'created_at' => $order->created_at->toDateTimeString(),
                        'retry_window_hours' => $retryWindowHours,
                    ],
                    400
                );
            }

            // Check max retry attempts
            $maxRetries = config('payment-gateways.session.max_retry_attempts', 3);
            $retryCount = $order->paymentTransactions()->count();

            if ($retryCount >= $maxRetries) {
                return Response::error(
                    __('Maximum retry attempts exceeded'),
                    [
                        'retry_count' => $retryCount,
                        'max_retries' => $maxRetries,
                    ],
                    400
                );
            }

            // Cancel previous pending transactions
            $order->paymentTransactions()
                ->whereIn('status', ['pending', 'processing'])
                ->update(['status' => 'cancelled']);

            // Initiate new payment
            $paymentData = $this->paymentService->initiatePayment($order);

            return Response::success(
                __('Payment retry initiated'),
                [
                    'order_id' => $order->id,
                    'payment' => [
                        'status' => $paymentData['payment_status'],
                        'gateway' => $paymentData['gateway'],
                        'transaction_id' => $paymentData['transaction_id'],
                        'redirect_url' => $paymentData['redirect_url'] ?? null,
                        'requires_redirect' => $paymentData['requires_redirect'] ?? false,
                        'requires_proof_upload' => $paymentData['requires_proof_upload'] ?? false,
                        'bank_account_details' => $paymentData['bank_account_details'] ?? null,
                        'expires_at' => $paymentData['expires_at'] ?? null,
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Payment retry error', [
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return Response::error(
                __('Payment retry failed'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Check payment status for an order
     * Frontend can poll this to check if payment completed or reviewed
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function checkStatus(Request $request, Order $order)
    {
        try {
            // Check if user owns the order
            if ($order->user_id !== Auth::id()) {
                return Response::error(
                    __('Unauthorized'),
                    null,
                    403
                );
            }

            $statusData = $this->paymentService->checkPaymentStatus($order);

            if (!$statusData['success']) {
                return Response::error(
                    $statusData['message'] ?? __('Failed to check payment status'),
                    $statusData,
                    400
                );
            }

            $transaction = $order->getLatestPaymentTransaction();

            return Response::success(
                __('Payment status retrieved'),
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $statusData['payment_status'],
                    'transaction_status' => $statusData['status'],
                    'transaction_id' => $statusData['transaction_id'] ?? null,
                    'can_retry' => $statusData['can_retry'] ?? false,
                    'awaiting_review' => $statusData['awaiting_review'] ?? false,
                    'has_payment_proof' => $statusData['has_payment_proof'] ?? false,
                    'reviewed_at' => $transaction?->reviewed_at?->toDateTimeString(),
                    'review_notes' => $transaction?->review_notes,
                ]
            );

        } catch (\Exception $e) {
            Log::error('Payment status check error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return Response::error(
                __('Failed to check payment status'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Upload payment proof for bank transfer
     *
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\Response
     */
    public function uploadProof(Request $request, Order $order)
    {
        try {
            // Check if user owns the order
            if ($order->user_id !== Auth::id()) {
                return Response::error(
                    __('Unauthorized'),
                    null,
                    403
                );
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'payment_proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB
            ]);

            if ($validator->fails()) {
                return Response::error(
                    __('Validation failed'),
                    ['errors' => $validator->errors()],
                    422
                );
            }

            // Check if order payment method is bank transfer
            $transaction = $order->getLatestPaymentTransaction();
            if (!$transaction || $transaction->gateway !== 'bank_transfer') {
                return Response::error(
                    __('Payment proof upload is only for bank transfer payments'),
                    null,
                    400
                );
            }

            // Upload proof
            $filePath = $this->paymentService->uploadPaymentProof(
                $order,
                $request->file('payment_proof')
            );

            return Response::success(
                __('Payment proof uploaded successfully. Your payment is now awaiting admin review.'),
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_status' => $order->fresh()->payment_status,
                    'awaiting_review' => true,
                    'uploaded_at' => now()->toDateTimeString(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Payment proof upload error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return Response::error(
                __('Payment proof upload failed'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Get bank account details for payment
     * This is a public endpoint (no authentication required)
     *
     * @return \Illuminate\Http\Response
     */
    public function bankAccountDetails()
    {
        try {
            $bankDetails = $this->paymentService->getBankAccountDetails();

            return Response::success(
                __('Bank account details retrieved'),
                [
                    'bank_account' => $bankDetails,
                    'instructions' => [
                        __('Transfer the exact order amount to the account above'),
                        __('Include your order number in the transfer description'),
                        __('Upload the payment receipt after completing the transfer'),
                        __('Your order will be processed after admin verification'),
                    ],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Bank account details retrieval error', [
                'error' => $e->getMessage(),
            ]);

            return Response::error(
                __('Failed to retrieve bank account details'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}

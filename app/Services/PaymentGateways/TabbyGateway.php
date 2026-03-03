<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Tabby Payment Gateway
 *
 * This is a placeholder implementation.
 * Will be fully implemented in Phase 9.
 */
class TabbyGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'tabby';

    /**
     * Create a payment session for the order
     */
    public function createPayment(Order $order): array
    {
        try {
            $publicKey = $this->getConfig('public_key');
            $merchantCode = $this->getConfig('merchant_code');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.tabby.ai'), '/');

            if (! $publicKey || ! $merchantCode) {
                return [
                    'success' => false,
                    'message' => __('Tabby configuration is incomplete'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $user = $order->user;
            $location = $order->location;

            $paymentData = [
                'payment' => [
                    'amount' => number_format($order->total, 2, '.', ''),
                    'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                    'description' => __('Order').' #'.$order->order_number,
                    'buyer' => [
                        'phone' => $user->phone ?? '966500000000',
                        'email' => $user->email ?? 'customer@example.com',
                        'name' => $user->name ?? 'Customer',
                    ],
                    'shipping_address' => [
                        'city' => $location?->city?->name ?? 'Riyadh',
                        'address' => $location?->address ?? 'Saudi Arabia',
                        'zip' => '12345',
                    ],
                    'order' => [
                        'tax_amount' => number_format($order->tax, 2, '.', ''),
                        'shipping_amount' => number_format($order->shipping, 2, '.', ''),
                        'discount_amount' => number_format($order->discount + $order->points_discount, 2, '.', ''),
                        'reference_id' => $order->order_number,
                        'items' => $order->items->map(function ($item) {
                            return [
                                'title' => $item->product->name ?? 'Product',
                                'description' => $item->product->description ?? 'Product description',
                                'quantity' => $item->quantity,
                                'unit_price' => number_format($item->price, 2, '.', ''),
                                'discount_amount' => '0.00',
                                'reference_id' => $item->product_id,
                                'category' => $item->product->categories->first()?->name ?? 'General',
                            ];
                        })->toArray(),
                    ],
                ],
                'lang' => app()->getLocale(),
                'merchant_code' => $merchantCode,
                'merchant_urls' => [
                    'success' => route('payment.callback', ['order' => $order->id, 'status' => 'success']),
                    'cancel' => route('payment.callback', ['order' => $order->id, 'status' => 'cancel']),
                    'failure' => route('payment.callback', ['order' => $order->id, 'status' => 'failure']),
                ],
            ];

            $response = $this->httpPost(
                $baseUrl.'/api/v2/checkout',
                $paymentData,
                [
                    'Authorization' => 'Bearer '.$publicKey,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create Tabby checkout session'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $paymentResponse = $response['data'];
            $tabbyPayment = $paymentResponse['payment'] ?? [];
            $configuration = $paymentResponse['configuration'] ?? [];
            $status = $paymentResponse['status'] ?? null;

            // Tabby returns HTTP 200 even when rejected - check status and available products
            if ($status === 'rejected' || empty($configuration['available_products'] ?? [])) {
                $errorMessage = $this->getTabbyRejectionMessage($paymentResponse);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'transaction_id' => $tabbyPayment['id'] ?? null,
                    'redirect_url' => null,
                    'status' => 'rejected',
                    'error_code' => $paymentResponse['rejection_reason_code'] ?? null,
                    'data' => $paymentResponse,
                ];
            }

            // Extract web_url - Tabby schema: configuration.available_products.installments[0].web_url
            $availableProducts = $configuration['available_products'] ?? [];
            $installmentsList = is_array($availableProducts) && isset($availableProducts['installments'])
                ? ($availableProducts['installments'] ?? [])
                : $availableProducts;
            $firstInstallment = is_array($installmentsList) ? ($installmentsList[0] ?? []) : [];
            $webUrl = $firstInstallment['web_url'] ?? null;

            return [
                'success' => true,
                'transaction_id' => $tabbyPayment['id'] ?? null,
                'redirect_url' => $webUrl,
                'requires_redirect' => true,
                'status' => 'pending',
                'message' => __('Tabby checkout session created successfully'),
                'data' => $paymentResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Tabby payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create Tabby session: :error', ['error' => $e->getMessage()]),
                'transaction_id' => null,
                'redirect_url' => null,
            ];
        }
    }

    /**
     * Capture a payment
     */
    public function capturePayment(string $transactionId): array
    {
        try {
            $secretKey = $this->getConfig('secret_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.tabby.ai'), '/');

            if (! $secretKey) {
                return [
                    'success' => false,
                    'message' => __('Tabby secret key is not configured'),
                ];
            }

            $response = $this->httpPost(
                $baseUrl.'/api/v1/payments/'.$transactionId.'/captures',
                [],
                [
                    'Authorization' => 'Bearer '.$secretKey,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to capture Tabby payment'),
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => __('Payment captured successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Tabby payment capture failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to capture payment: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $secretKey = $this->getConfig('secret_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.tabby.ai'), '/');

            if (! $secretKey) {
                return [
                    'success' => false,
                    'message' => __('Tabby secret key is not configured'),
                ];
            }

            $refundData = [
                'amount' => number_format($amount, 2, '.', ''),
            ];

            $response = $this->httpPost(
                $baseUrl.'/api/v1/payments/'.$transactionId.'/refunds',
                $refundData,
                [
                    'Authorization' => 'Bearer '.$secretKey,
                    'Content-Type' => 'application/json',
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process Tabby refund'),
                    'refund_id' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'refund_id' => $response['data']['id'] ?? $transactionId.'-refund',
                'message' => __('Refund processed successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Tabby refund failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to process refund: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Get payment status from gateway
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $secretKey = $this->getConfig('secret_key');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.tabby.ai'), '/');

            if (! $secretKey) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Tabby secret key is not configured'),
                ];
            }

            $response = $this->httpGet(
                $baseUrl.'/api/v2/payments/'.$transactionId,
                [],
                [
                    'Authorization' => 'Bearer '.$secretKey,
                ]
            );

            if (! $response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get Tabby status'),
                ];
            }

            $paymentData = $response['data'];
            $status = $this->mapTabbyStatus($paymentData['status'] ?? 'unknown');

            return [
                'success' => true,
                'status' => $status,
                'data' => $paymentData,
            ];

        } catch (\Exception $e) {
            Log::error('Tabby status check failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => __('Failed to get payment status: :error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * Handle webhook notification
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $status = $this->mapTabbyStatus($payload['status'] ?? 'unknown');
            $paymentId = $payload['id'] ?? null;
            $orderNumber = $payload['order']['reference_id'] ?? null;

            if (! $orderNumber) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order reference not found in Tabby webhook'),
                ];
            }

            $order = Order::where('order_number', $orderNumber)->first();

            return [
                'success' => true,
                'order_id' => $order?->id,
                'status' => $status,
                'transaction_id' => $paymentId,
            ];

        } catch (\Exception $e) {
            Log::error('Tabby webhook processing failed', [
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhookSignature(Request $request): bool
    {
        // Tabby webhooks are typically validated by checking the payload content
        // or using a specific header if configured.
        // For simplicity, we'll assume validation is handled or simplified here.
        return true;
    }

    /**
     * Get user-friendly message for Tabby rejection
     */
    protected function getTabbyRejectionMessage(array $paymentResponse): string
    {
        $rejectionReason = null;
        $configuration = $paymentResponse['configuration'] ?? [];
        $products = $configuration['products'] ?? [];
        $installments = $products['installments'] ?? [];

        if (! empty($installments['rejection_reason'])) {
            $rejectionReason = $installments['rejection_reason'];
        }

        $rejectionCode = $paymentResponse['rejection_reason_code'] ?? $rejectionReason;

        $messages = [
            'order_amount_too_high' => __('Tabby installment is not available for this order amount. The order total exceeds the allowed limit. Please try a different payment method.'),
            'order_amount_too_low' => __('Tabby installment is not available. The order amount is below the minimum required.'),
            'not_enough_limit' => __('Tabby installment is not available. Your spending limit does not cover this order. Please try a different payment method.'),
            'not_available' => __('Tabby installment is not available for this order. Please try a different payment method.'),
        ];

        return $messages[$rejectionReason ?? $rejectionCode] ?? $messages['not_available'];
    }

    /**
     * Map Tabby status to internal status
     */
    protected function mapTabbyStatus(string $status): string
    {
        return match (strtolower($status)) {
            'authorized', 'captured', 'closed' => 'success',
            'created', 'initiated' => 'pending',
            'rejected', 'expired' => 'failed',
            'canceled' => 'cancelled',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }
}

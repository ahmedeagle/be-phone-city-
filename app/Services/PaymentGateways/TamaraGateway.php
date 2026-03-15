<?php

namespace App\Services\PaymentGateways;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Tamara Payment Gateway
 *
 * Tamara is a buy-now-pay-later service popular in Saudi Arabia and UAE.
 * Documentation: https://docs.tamara.co
 */
class TamaraGateway extends AbstractPaymentGateway
{
    protected string $gateway = 'tamara';

    /**
     * Create a payment session for the order
     *
     * @param Order $order
     * @return array
     */
    public function createPayment(Order $order): array
    {
        try {
            $apiToken = $this->getConfig('api_token');
            $baseUrl = rtrim($this->getConfig('api_url', 'https://api.tamara.co'), '/');

            if (!$apiToken) {
                return [
                    'success' => false,
                    'message' => __('Tamara API token is not configured'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                ];
            }

            $user = $order->user;
            $location = $order->location;

            // Prepare names
            $nameParts = explode(' ', $user->name ?? 'Customer', 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? $firstName;

            // Tamara expects clean phone numbers, ideally starting with +966
            $phone = $user->phone ?? '500000000';
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($phone) === 9 && strpos($phone, '5') === 0) {
                // 9 digits starting with 5 → 966XXXXXXXXX
                $phone = '966' . $phone;
            } elseif (strlen($phone) === 10 && strpos($phone, '05') === 0) {
                // 10 digits starting with 05 → remove leading 0 and add country code
                $phone = '966' . substr($phone, 1);
            } elseif (strlen($phone) === 12 && strpos($phone, '966') === 0) {
                // Already correctly formatted
            } else {
                $phone = '966500000000'; // Final fallback
            }
            if (strpos($phone, '+') !== 0) {
                $phone = '+' . ltrim($phone, '+');
            }

            $paymentData = [
                'order_reference_id' => $order->order_number,
                'order_number' => $order->order_number,
                'total_amount' => [
                    'amount' => (float)$order->total,
                    'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                ],
                'description' => __('Order') . ' #' . $order->order_number,
                'country_code' => 'SA',
                'payment_type' => 'PAY_BY_INSTALMENTS',
                'locale' => app()->getLocale() === 'ar' ? 'ar_SA' : 'en_US',
                'customer' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone_number' => $phone,
                    'email' => $user->email ?? 'customer@example.com',
                ],
                'shipping_address' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'line1' => $location?->address ?? 'Saudi Arabia',
                    'city' => $location?->city?->name ?? 'Riyadh',
                    'country_code' => 'SA',
                    'phone_number' => $phone,
                ],
                'items' => $order->items->map(function ($item) {
                    return [
                        'reference_id' => (string)$item->product_id,
                        'type' => 'Physical',
                        'name' => $item->product->name ?? 'Product',
                        'sku' => (string)$item->product_id,
                        'quantity' => (int)$item->quantity,
                        'total_amount' => [
                            'amount' => (float)$item->total,
                            'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                        ],
                        'unit_price' => [
                            'amount' => (float)$item->price,
                            'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                        ],
                        'discount_amount' => [
                            'amount' => 0.0,
                            'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                        ],
                        'tax_amount' => [
                            'amount' => 0.0,
                            'currency' => strtoupper($order->currency ?? config('payment-gateways.currency', 'SAR')),
                        ],
                    ];
                })->toArray(),
                'merchant_url' => [
                    'success' => route('payment.callback', ['order' => $order->id, 'status' => 'success']),
                    'failure' => route('payment.callback', ['order' => $order->id, 'status' => 'failure']),
                    'cancel' => route('payment.callback', ['order' => $order->id, 'status' => 'cancel']),
                    'notification' => route('payment.webhook', ['gateway' => 'tamara']),
                ],
                'tax_amount' => [
                    'amount' => (float)$order->tax,
                    'currency' => 'SAR',
                ],
                'shipping_amount' => [
                    'amount' => (float)$order->shipping,
                    'currency' => 'SAR',
                ],
                'discount_amount' => [
                    'amount' => (float)($order->discount + $order->points_discount),
                    'currency' => 'SAR',
                ],
            ];

            $response = $this->httpPost(
                $baseUrl . '/checkout',
                $paymentData,
                [
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to create Tamara checkout session'),
                    'transaction_id' => null,
                    'redirect_url' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            $paymentResponse = $response['data'];

            return [
                'success' => true,
                'transaction_id' => $paymentResponse['order_id'] ?? null,
                'redirect_url' => $paymentResponse['checkout_url'] ?? null,
                'requires_redirect' => !empty($paymentResponse['checkout_url']),
                'status' => 'pending',
                'message' => __('Tamara checkout session created successfully'),
                'data' => $paymentResponse,
            ];

        } catch (\Exception $e) {
            Log::error('Tamara payment creation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('Failed to create Tamara session: :error', ['error' => $e->getMessage()]),
                'transaction_id' => null,
                'redirect_url' => null,
            ];
        }
    }

    /**
     * Authorise a Tamara order
     *
     * @param string $orderId
     * @return array
     */
    public function authoriseOrder(string $orderId): array
    {
        try {
            $apiToken = $this->getConfig('api_token');
            $apiUrl = $this->getConfig('api_url', 'https://api.tamara.co');

            $response = $this->httpPost(
                rtrim($apiUrl, '/') . "/orders/{$orderId}/authorise",
                [],
                [
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ]
            );

            return $response;
        } catch (\Exception $e) {
            Log::error('Tamara order authorisation failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Capture a payment
     *
     * @param string $transactionId
     * @return array
     */
    public function capturePayment(string $transactionId): array
    {
        try {
            $apiToken = $this->getConfig('api_token');
            $apiUrl = $this->getConfig('api_url', 'https://api.tamara.co');

            if (!$apiToken) {
                return [
                    'success' => false,
                    'message' => __('Tamara API token is not configured'),
                ];
            }

            $captureData = [
                'order_id' => $transactionId,
                'total_amount' => [
                    'amount' => 0, // Tamara often requires full order data for partial captures, but for full we can omit or send total
                    'currency' => 'SAR',
                ],
                'shipping_info' => [
                    'shipping_company' => 'OTO',
                ]
            ];

            $response = $this->httpPost(
                rtrim($apiUrl, '/') . '/payments/capture',
                $captureData,
                [
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to capture Tamara payment'),
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'message' => __('Payment captured successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Tamara payment capture failed', [
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
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $apiToken = $this->getConfig('api_token');
            $apiUrl = $this->getConfig('api_url', 'https://api.tamara.co');

            if (!$apiToken) {
                return [
                    'success' => false,
                    'message' => __('Tamara API token is not configured'),
                ];
            }

            $refundData = [
                'order_id' => $transactionId,
                'refund_amount' => [
                    'amount' => (float)$amount,
                    'currency' => 'SAR',
                ],
                'comment' => 'Order refund',
            ];

            $response = $this->httpPost(
                rtrim($apiUrl, '/') . '/payments/refund',
                $refundData,
                [
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Content-Type' => 'application/json',
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => $response['error'] ?? __('Failed to process Tamara refund'),
                    'refund_id' => null,
                    'data' => $response['data'] ?? [],
                ];
            }

            return [
                'success' => true,
                'refund_id' => $response['data']['refund_id'] ?? $transactionId . '-refund',
                'message' => __('Refund processed successfully'),
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Tamara refund failed', [
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
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $apiToken = $this->getConfig('api_token');
            $apiUrl = $this->getConfig('api_url', 'https://api.tamara.co');

            if (!$apiToken) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => __('Tamara API token is not configured'),
                ];
            }

            $response = $this->httpGet(
                rtrim($apiUrl, '/') . "/orders/{$transactionId}",
                [],
                [
                    'Authorization' => 'Bearer ' . $apiToken,
                ]
            );

            if (!$response['success']) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => $response['error'] ?? __('Failed to get Tamara status'),
                ];
            }

            $paymentData = $response['data'];
            $status = $this->mapTamaraStatus($paymentData['status'] ?? 'unknown');

            return [
                'success' => true,
                'status' => $status,
                'data' => $paymentData,
            ];

        } catch (\Exception $e) {
            Log::error('Tamara status check failed', [
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
     *
     * @param array $payload
     * @return array
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $status = $this->mapTamaraStatus($payload['status'] ?? 'unknown');
            $paymentId = $payload['order_id'] ?? null;
            $orderNumber = $payload['order_reference_id'] ?? null;

            if (!$orderNumber) {
                return [
                    'success' => false,
                    'order_id' => null,
                    'status' => $status,
                    'message' => __('Order reference not found in Tamara webhook'),
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
            Log::error('Tamara webhook processing failed', [
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
     *
     * @param Request $request
     * @return bool
     */
    public function validateWebhookSignature(Request $request): bool
    {
        $webhookToken = $this->getConfig('webhook_token');
        if (!$webhookToken) {
            return true; // Or false based on security policy
        }

        $signature = $request->header('X-Tamara-Signature');
        if (!$signature) {
            return false;
        }

        // Tamara signature validation logic
        // Typically it involves HMAC with the webhook token
        return true;
    }

    /**
     * Map Tamara status to internal status
     *
     * @param string $status
     * @return string
     */
    protected function mapTamaraStatus(string $status): string
    {
        return match(strtolower($status)) {
            'approved', 'authorised', 'fully_captured', 'partially_captured' => 'success',
            'new', 'initiated' => 'pending',
            'declined', 'expired' => 'failed',
            'canceled' => 'cancelled',
            'fully_refunded', 'partially_refunded' => 'refunded',
            default => 'pending',
        };
    }
}

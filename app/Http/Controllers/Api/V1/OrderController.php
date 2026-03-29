<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Location;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\DiscountService;
use App\Services\OrderCalculationService;
use App\Services\OrderService;
use App\Services\ShippingService;
use App\Traits\PaginatesResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class OrderController extends Controller
{
    use PaginatesResponses;

    public function __construct(
        protected OrderService $orderService,
        protected OrderCalculationService $calculationService,
        protected ShippingService $shippingService,
        protected DiscountService $discountService,
        protected \App\Services\PaymentService $paymentService,
        protected \App\Services\NotificationService $notificationService
    ) {}

    /**
     * Get all orders for authenticated user
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->filled('status') ? $request->status : null,
            'date_from' => $request->filled('date_from') ? $request->date_from : null,
            'date_to' => $request->filled('date_to') ? $request->date_to : null,
        ];

        $query = $this->orderService->getUserOrders(Auth::id(), $filters);
        $data = $this->paginateData($query);
        $orders = OrderResource::collection($data['data']);

        return Response::success(
            __('Orders fetched successfully'),
            $orders,
            200,
            $data['pagination']
        );
    }

    /**
     * Create order from cart items
     */
    public function store(StoreOrderRequest $request)
    {
        // Get cart items
        $cartItems = Cart::where('user_id', Auth::id())
            ->with(['product.categories', 'productOption'])
            ->get();

        if ($cartItems->isEmpty()) {
            return Response::error(
                __('Cart is empty'),
                null,
                400
            );
        }

        // Stock availability check before proceeding
        $stockIssues = $this->calculationService->checkStockAvailability($cartItems);
        if (! empty($stockIssues)) {
            return Response::error(
                __('One or more items are out of stock or have insufficient quantity'),
                ['stock_issues' => $stockIssues],
                422
            );
        }

        // Validate discount code if provided
        $discount = null;
        if ($request->filled('discount_code')) {
            $discount = $this->discountService->validateDiscountCode($request->discount_code);
            if (! $discount) {
                return Response::error(
                    __('Invalid or expired discount code'),
                    null,
                    400
                );
            }

            // Validate discount conditions
            $subtotal = $this->calculationService->calculateSubtotal($cartItems);
            $cartItemsCount = $cartItems->sum('quantity');
            $conditionResult = $this->discountService->validateConditions($discount, $subtotal, Auth::id(), $cartItemsCount);
            if (! $conditionResult['valid']) {
                return Response::error(
                    $conditionResult['error'],
                    null,
                    400
                );
            }
        }

        // Get and validate payment method
        $paymentMethod = PaymentMethod::find($request->payment_method_id);
        if (! $paymentMethod) {
            return Response::error(
                __('Invalid payment method'),
                null,
                422
            );
        }

        // If any product is in a bank transfer category, only bank transfer payment methods are allowed
        $hasBankTransferCategoryProduct = $cartItems->contains(function ($item) {
            return $item->product->categories->contains('is_bank_transfer', true);
        });

        if ($hasBankTransferCategoryProduct && ! $paymentMethod->is_bank_transfer) {
            return Response::error(
                __('One or more items in your cart require bank transfer payment only'),
                null,
                422
            );
        }

        // Check if payment method is installment and if all cart items support it
        if ($paymentMethod->is_installment) {
            $allSupportInstallment = $cartItems->every(function ($item) {
                return $item->product->is_installment;
            });

            if (! $allSupportInstallment) {
                return Response::error(
                    __('One or more items in your cart do not support installment payments'),
                    null,
                    422
                );
            }
        }

        // Validate location for home delivery
        $location = null;
        if ($request->delivery_method === Order::DELIVERY_HOME) {
            if (! $request->filled('location_id')) {
                return Response::error(
                    __('Location is required for home delivery'),
                    null,
                    422
                );
            }

            $locationValidation = $this->shippingService->validateLocation($request->location_id);
            if (! $locationValidation['valid']) {
                return Response::error(
                    $locationValidation['error'],
                    null,
                    422
                );
            }
            $location = $locationValidation['location'];
        }

        // Calculate order totals
        $calculations = $this->calculationService->calculateOrderTotals([
            'cart_items' => $cartItems,
            'discount_code' => $request->discount_code,
            'delivery_method' => $request->delivery_method,
            'location' => $location,
            'payment_method' => $paymentMethod,
            'use_points' => $request->boolean('use_point'),
            'user_id' => Auth::id(),
        ]);

        // Prepare order data
        $orderData = [
            'user_id' => Auth::id(),
            'notes' => $request->notes,
            'location_id' => $location?->id,
            'branch_id' => $request->delivery_method === Order::DELIVERY_STORE_PICKUP ? $request->branch_id : null,
            'payment_method_id' => $paymentMethod->id,
            'delivery_method' => $request->delivery_method,
            'subtotal' => $calculations['subtotal'],
            'discount' => $calculations['discount'],
            'discount_id' => $calculations['discount_model']?->id,
            'shipping' => $calculations['shipping'],
            'tax' => $calculations['tax'],
            'points_discount' => $calculations['points_discount'],
            'points_to_consume' => $calculations['points_to_consume'],
            'total' => $calculations['total'],
            'status' => Order::STATUS_PENDING,
        ];

        try {
            // Wrap both order creation AND payment initiation in a single transaction.
            // If the payment gateway call fails, the order is rolled back automatically.
            $result = DB::transaction(function () use ($orderData, $paymentMethod) {
                // Create order
                $order = $this->orderService->createOrderFromCart($orderData);

                // Initiate payment — if this throws, the whole transaction rolls back
                $paymentData = $this->paymentService->initiatePayment($order);

                return compact('order', 'paymentData');
            });

            $order = $result['order'];
            $paymentData = $result['paymentData'];

            // Prepare response with order and payment data
            $responseData = [
                'order' => new OrderResource($order->fresh(['paymentMethod', 'location', 'branch', 'items'])),
                'payment' => [
                    'success' => $paymentData['success'],
                    'status' => $paymentData['payment_status'],
                    'gateway' => $paymentData['gateway'],
                    'transaction_id' => $paymentData['transaction_id'],
                    'redirect_url' => $paymentData['redirect_url'] ?? null,
                    'requires_redirect' => $paymentData['requires_redirect'] ?? false,
                    'requires_proof_upload' => $paymentData['requires_proof_upload'] ?? false,
                    'bank_account_details' => $paymentData['bank_account_details'] ?? null,
                    'message' => $paymentData['message'] ?? null,
                    'expires_at' => $paymentData['expires_at'] ?? null,
                    'error' => $paymentData['error'] ?? null,
                    'error_code' => $paymentData['error_code'] ?? null,
                    'can_retry' => $paymentData['can_retry'] ?? false,
                ],
            ];

            // For bank transfers, add upload URL
            if ($paymentData['requires_proof_upload'] ?? false) {
                $responseData['payment']['upload_url'] = route('orders.payment.uploadProof', ['order' => $order->id]);
            }

            return Response::success(
                __('Order created successfully'),
                $responseData,
                201
            );

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Response::error(
                __('Failed to create order'),
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * Preview order calculation - Get and check all info for order
     * Returns all data needed to display order summary in frontend
     */
    public function preview(Request $request)
    {
        // Get cart items
        $cartItems = Cart::where('user_id', Auth::id())
            ->with(['product.categories', 'productOption'])
            ->get();

        if ($cartItems->isEmpty()) {
            return Response::error(
                __('Cart is empty'),
                null,
                400
            );
        }

        // Validate required fields
        $errors = $this->validatePreviewRequest($request);
        if (! empty($errors)) {
            return Response::error(
                __('Validation failed'),
                ['errors' => $errors],
                422
            );
        }

        // Get settings
        $settings = Setting::getSettings();

        // Validate discount code
        $discountInfo = null;
        $discountCode = null;
        if ($request->filled('discount_code')) {
            $discount = $this->discountService->validateDiscountCode($request->discount_code);
            if (! $discount) {
                $errors[] = __('Invalid or expired discount code');
            } else {
                $subtotal = $this->calculationService->calculateSubtotal($cartItems);
                $cartItemsCount = $cartItems->sum('quantity');

                // Validate discount conditions
                $conditionResult = $this->discountService->validateConditions($discount, $subtotal, Auth::id(), $cartItemsCount);
                if (! $conditionResult['valid']) {
                    $errors[] = $conditionResult['error'];
                } else {
                    $discountCode = $request->discount_code;
                    $discountAmount = $this->discountService->calculateDiscountAmount($discount, $subtotal);

                    $discountInfo = [
                        'id' => $discount->id,
                        'code' => $discount->code,
                        'type' => $discount->type,
                        'value' => $discount->value,
                        'discount_amount' => number_format($discountAmount, 2),
                    ];
                }
            }
        }

        // Get and validate payment method
        $paymentMethod = null;
        $warnings = [];
        if ($request->filled('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->payment_method_id);
            if (! $paymentMethod) {
                $errors[] = __('Invalid payment method');
            } elseif ($paymentMethod->status !== 'active') {
                $warnings[] = __('Selected payment method is not active');
            } else {
                $hasBankTransferCategoryProduct = $cartItems->contains(function ($item) {
                    return $item->product->categories->contains('is_bank_transfer', true);
                });

                if ($hasBankTransferCategoryProduct && ! $paymentMethod->is_bank_transfer) {
                    $errors[] = __('One or more items in your cart require bank transfer payment only');
                } elseif ($paymentMethod->is_installment) {
                    // Check if all cart items support installment
                    $allSupportInstallment = $cartItems->every(function ($item) {
                        return $item->product->is_installment;
                    });

                    if (! $allSupportInstallment) {
                        $errors[] = __('One or more items in your cart do not support installment payments');
                    }
                }
            }
        }

        // Validate location for home delivery
        $location = null;
        $shippingInfo = null;
        if ($request->delivery_method === Order::DELIVERY_HOME && $request->filled('location_id')) {
            $locationValidation = $this->shippingService->validateLocation($request->location_id);
            if (! $locationValidation['valid']) {
                $errors[] = $locationValidation['error'];
            } else {
                $location = $locationValidation['location'];
                $shippingInfo = $this->buildShippingInfo($location);
            }
        }

        // Return early if there are validation errors
        if (! empty($errors)) {
            return Response::error(
                __('Order validation failed'),
                [
                    'errors' => $errors,
                    'warnings' => $warnings,
                ],
                422
            );
        }

        // Calculate order totals
        $calculations = $this->calculationService->calculateOrderTotals([
            'cart_items' => $cartItems,
            'discount_code' => $discountCode,
            'delivery_method' => $request->delivery_method,
            'location' => $location,
            'payment_method' => $paymentMethod,
            'use_points' => $request->boolean('use_point'),
            'user_id' => Auth::id(),
        ]);

        // Check stock availability
        $stockIssues = $this->calculationService->checkStockAvailability($cartItems);
        if (! empty($stockIssues)) {
            $warnings[] = __('Some products have insufficient stock');
        }

        // Build response
        $orderData = $this->buildPreviewResponse(
            $cartItems,
            $calculations,
            $request,
            $location,
            $paymentMethod,
            $discountCode,
            $discountInfo,
            $shippingInfo,
            $warnings,
            $stockIssues
        );

        return Response::success(
            __('Order preview calculated successfully'),
            $orderData,
            200
        );
    }

    /**
     * Get single order
     */
    public function show(int $id)
    {
        $order = Order::where('user_id', Auth::id())
            ->with([
                'items.product.images',
                'items.product.categories',
                'items.productOption.images',
                'location.city',
                'branch',
                'paymentMethod',
                'discountCode',
                'invoice',
            ])
            ->findOrFail($id);

        return Response::success(
            __('Order fetched successfully'),
            new OrderResource($order),
            200
        );
    }

    /**
     * Cancel an order (only if pending/confirmed/processing)
     */
    public function cancel(int $id)
    {
        $order = Order::where('user_id', Auth::id())
            ->findOrFail($id);

        $cancellableStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PROCESSING,
        ];

        if (!in_array($order->status, $cancellableStatuses)) {
            return Response::error(__('This order cannot be cancelled'), 422);
        }

        if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
            return Response::error(__('Paid orders cannot be cancelled. Please contact support.'), 422);
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
        ]);

        return Response::success(
            __('Order cancelled successfully'),
            new OrderResource($order->load([
                'items.product.images',
                'items.product.categories',
                'items.productOption.images',
                'location.city',
                'branch',
                'paymentMethod',
                'discountCode',
                'invoice',
            ])),
            200
        );
    }

    /**
     * Refresh shipping status from OTO for a specific order
     */
    public function refreshShipping(int $id)
    {
        $order = Order::where('user_id', Auth::id())
            ->findOrFail($id);

        if (empty($order->tracking_number) && empty($order->oto_order_id)) {
            return Response::error(__('This order has no shipment to refresh'), 422);
        }

        try {
            $shippingService = app(\App\Services\Shipping\OtoShippingService::class);
            $shippingService->syncShipmentStatus($order);
            $order->refresh();

            return Response::success(
                __('Shipping status updated'),
                new OrderResource($order->load([
                    'items.product.images',
                    'items.product.categories',
                    'items.productOption.images',
                    'location.city',
                    'branch',
                    'paymentMethod',
                    'discountCode',
                    'invoice',
                ])),
                200
            );
        } catch (\Exception $e) {
            return Response::error(__('Failed to refresh shipping status'), 500);
        }
    }

    /**
     * Validate preview request
     *
     * @return array Validation errors
     */
    protected function validatePreviewRequest(Request $request): array
    {
        $errors = [];

        if (! $request->filled('payment_method_id')) {
            $errors[] = __('Payment method is required');
        }

        if (! $request->filled('delivery_method')) {
            $errors[] = __('Delivery method is required');
        }

        if ($request->delivery_method === Order::DELIVERY_HOME && ! $request->filled('location_id')) {
            $errors[] = __('Location is required for home delivery');
        }

        return $errors;
    }

    /**
     * Build shipping info array for response
     */
    protected function buildShippingInfo(Location $location): array
    {
        return [
            'city' => [
                'id' => $location->city->id,
                'slug' => $location->city->slug,
                'name' => $location->city->name,
                'name_en' => $location->city->name_en,
                'name_ar' => $location->city->name_ar,
                'shipping_fee' => number_format($location->city->shipping_fee, 2),
            ],
            'location' => new \App\Http\Resources\LocationResource($location),
        ];
    }

    /**
     * Build complete preview response
     *
     * @param  mixed  $cartItems
     */
    protected function buildPreviewResponse(
        $cartItems,
        array $calculations,
        Request $request,
        ?Location $location,
        ?PaymentMethod $paymentMethod,
        ?string $discountCode,
        ?array $discountInfo,
        ?array $shippingInfo,
        array $warnings,
        array $stockIssues
    ): array {
        $settings = $calculations['settings'];
        $freeShippingThreshold = $settings->free_shipping_threshold ?? 0;

        // Prepare cart items data
        $items = $cartItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'slug' => $item->product->slug,
                    'name' => $item->product->name,
                    'name_en' => $item->product->name_en,
                    'name_ar' => $item->product->name_ar,
                    'main_image' => $item->product->main_image
                        ? asset('storage/'.$item->product->main_image)
                        : null,
                    'stock_status' => $item->productOption ? $item->productOption->stock_status : $item->product->stock_status,
                    'quantity_available' => $item->productOption ? $item->productOption->quantity : $item->product->quantity,
                ],
                'product_option' => $item->productOption ? [
                    'id' => $item->productOption->id,
                    'type' => $item->productOption->type,
                    'value' => $item->productOption->value,
                ] : null,
                'price' => number_format($item->price, 2),
                'quantity' => $item->quantity,
                'total' => number_format($item->price * $item->quantity, 2),
            ];
        });

        // Build points info
        $pointsInfo = null;
        if ($request->boolean('use_point')) {
            $pointValue = $settings->point_value ?? 1.00;
            $pointsInfo = [
                'points_available' => $calculations['available_points'],
                'points_to_use' => $calculations['points_to_consume'],
                'point_value' => number_format($pointValue, 2),
                'discount_amount' => number_format($calculations['points_discount'], 2),
            ];

            if ($calculations['available_points'] === 0) {
                $pointsInfo['message'] = __('No available points');
            }
        }

        return [
            'items' => $items,
            'summary' => [
                'subtotal' => number_format($calculations['subtotal'], 2),
                'discount' => number_format($calculations['discount'], 2),
                'discount_code' => $discountCode,
                'discount_info' => $discountInfo,
                'shipping' => number_format($calculations['shipping'], 2),
                'shipping_info' => $shippingInfo,
                'qualifies_for_free_shipping' => $calculations['qualifies_for_free_shipping'],
                'free_shipping_threshold' => number_format($freeShippingThreshold, 2),
                'amount_needed_for_free_shipping' => $freeShippingThreshold > $calculations['subtotal']
                    ? number_format($freeShippingThreshold - $calculations['subtotal'], 2)
                    : 0,
                // Tax is included in product prices, hiding from general view as per request
                // 'tax' => number_format($calculations['tax'], 2),
                // 'tax_percentage' => number_format($calculations['tax_percentage'], 2),
                'payment_method' => $paymentMethod ? [
                    'id' => $paymentMethod->id,
                    'name' => $paymentMethod->name,
                    'name_en' => $paymentMethod->name_en,
                    'name_ar' => $paymentMethod->name_ar,
                    'image' => $paymentMethod->image
                        ? asset('storage/'.$paymentMethod->image)
                        : null,
                    'status' => $paymentMethod->status,
                ] : null,
                'points_discount' => number_format($calculations['points_discount'], 2),
                'points_info' => $pointsInfo,
                'total' => number_format($calculations['total'], 2),
            ],
            'delivery_method' => $request->delivery_method,
            'location' => $location ? new \App\Http\Resources\LocationResource($location) : null,
            'settings' => [
                'tax_percentage' => number_format($calculations['tax_percentage'], 2),
                'free_shipping_threshold' => number_format($freeShippingThreshold, 2),
                'point_value' => number_format($settings->point_value ?? 1.00, 2),
            ],
            'warnings' => $warnings,
            'stock_issues' => $stockIssues,
        ];
    }
}

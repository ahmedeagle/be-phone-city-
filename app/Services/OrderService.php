<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service class for handling order creation and management
 */
class OrderService
{
    public function __construct(
        protected OrderCalculationService $calculationService,
        protected PointsService $pointsService
    ) {}

    /**
     * Create order from cart items
     *
     * @param array $orderData Order data
     * @return Order Created order model
     * @throws \Exception If order creation fails
     */
    public function createOrderFromCart(array $orderData): Order
    {
        DB::beginTransaction();

        try {
            // Get cart items
            $userId = $orderData['user_id'] ?? Auth::id();
            $cartItems = Cart::where('user_id', $userId)
                ->with(['product', 'productOption'])
                ->get();

            if ($cartItems->isEmpty()) {
                throw new \Exception(__('Cart is empty'));
            }

            // Create the order
            $order = $this->createOrder($orderData);

            // Create order items from cart
            $this->createOrderItems($order, $cartItems);

            // Points are awarded when order is paid (see OrderObserver)

            // Consume points if used
            if (isset($orderData['points_to_consume']) && $orderData['points_to_consume'] > 0) {
                $this->pointsService->consumePoints(
                    $userId,
                    $orderData['points_to_consume'],
                    $order->id
                );
            }

            // Clear cart
            Cart::where('user_id', $userId)->delete();

            // Load relationships
            $order->load([
                'items.product',
                'items.productOption',
                'location.city',
                'paymentMethod',
                'discountCode',
                'invoice'
            ]);

            DB::commit();

            return $order;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create order record
     *
     * @param array $orderData Order data
     * @return Order Created order
     */
    protected function createOrder(array $orderData): Order
    {
        return Order::create([
            'user_id' => $orderData['user_id'] ?? Auth::id(),
            'notes' => $orderData['notes'] ?? null,
            'location_id' => $orderData['location_id'] ?? null,
            'payment_method_id' => $orderData['payment_method_id'],
            'delivery_method' => $orderData['delivery_method'],
            'subtotal' => $orderData['subtotal'],
            'discount' => $orderData['discount'] ?? 0,
            'discount_id' => $orderData['discount_id'] ?? null,
            'vip_discount' => $orderData['vip_discount'] ?? 0,
            'vip_tier_at_order' => $orderData['vip_tier_at_order'] ?? null,
            'vip_tier_label' => $orderData['vip_tier_label'] ?? null,
            'shipping' => $orderData['shipping'] ?? 0,
            'tax' => $orderData['tax'] ?? 0,
            'points_discount' => $orderData['points_discount'] ?? 0,
            'total' => $orderData['total'],
            'status' => $orderData['status'] ?? Order::STATUS_PENDING,
        ]);
    }

    /**
     * Create order items from cart items
     *
     * @param Order $order Order model
     * @param \Illuminate\Support\Collection $cartItems Cart items collection
     * @return void
     */
    protected function createOrderItems(Order $order, $cartItems): void
    {
        foreach ($cartItems as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'product_option_id' => $cartItem->product_option_id,
                'price' => $cartItem->price,
                'quantity' => $cartItem->quantity,
                'total' => $cartItem->price * $cartItem->quantity,
            ]);
        }
    }

    /**
     * Get user's orders with filters
     *
     * @param int $userId User ID
     * @param array $filters Filters to apply
     * @return \Illuminate\Database\Eloquent\Builder Query builder
     */
    public function getUserOrders(int $userId, array $filters = [])
    {
        $query = Order::where('user_id', $userId)
            ->with([
                'items.product',
                'items.productOption',
                'location.city',
                'paymentMethod',
                'discountCode',
                'invoice'
            ])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}

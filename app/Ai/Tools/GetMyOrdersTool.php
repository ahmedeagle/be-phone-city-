<?php

namespace App\Ai\Tools;

use App\Models\Order;
use App\Models\User;

class GetMyOrdersTool extends BaseTool
{
    public static function getName(): string
    {
        return 'get_my_orders';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Get user\'s orders and their status. Requires authentication. Use this when user wants to check their order history or track orders.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'enum' => ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'completed', 'cancelled', 'all'],
                        'description' => 'Filter by order status (default: all)',
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of orders to return (default: 10, max: 50)',
                        'minimum' => 1,
                        'maximum' => 50,
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        if (!$this->requiresAuth($user)) {
            return $this->error('Authentication required to view orders');
        }

        try {
            $status = $arguments['status'] ?? 'all';
            $limit = min($arguments['limit'] ?? 10, 50);

            $query = Order::where('user_id', $user->id)
                ->orderBy('created_at', 'desc');

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $orders = $query->limit($limit)->get();

            $ordersData = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_display' => $order->getStatusDisplayName(),
                    'payment_status' => $order->payment_status,
                    'payment_status_display' => $order->getPaymentStatusDisplayName(),
                    'subtotal' => $order->subtotal,
                    'discount' => $order->discount,
                    'shipping' => $order->shipping,
                    'tax' => $order->tax,
                    'total' => $order->total,
                    'delivery_method' => $order->delivery_method,
                    'tracking_number' => $order->tracking_number,
                    'tracking_url' => $order->tracking_url,
                    'items_count' => $order->items()->count(),
                    'created_at' => $order->created_at->toDateTimeString(),
                ];
            })->toArray();

            return $this->success([
                'orders' => $ordersData,
                'count' => count($ordersData),
                'total' => Order::where('user_id', $user->id)->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve orders: ' . $e->getMessage());
        }
    }
}

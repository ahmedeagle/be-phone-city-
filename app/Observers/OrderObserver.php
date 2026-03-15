<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductView;
use App\Notifications\OrderCompletedReviewRequest;
use App\Services\NotificationService;
use App\Services\PointsService;

class OrderObserver
{
    public function __construct(
        protected NotificationService $notificationService,
        protected PointsService $pointsService
    ) {}

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Do NOT notify or mark as purchased here
        // Wait until payment is confirmed (payment_status = PAID)
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $statusChangedByUs = false;

        // Check if payment status changed to PAID
        if ($order->isDirty('payment_status')) {
            $originalPaymentStatus = $order->getOriginal('payment_status');
            $newPaymentStatus = $order->payment_status;

            // When payment becomes PAID, send "order created" notification and mark products as purchased
            if ($newPaymentStatus === Order::PAYMENT_STATUS_PAID
                && $originalPaymentStatus !== Order::PAYMENT_STATUS_PAID) {

                // Decrement stock for every order item
                $this->decrementStock($order);

                // Send the "order created" notification NOW (after payment confirmed)
                $this->notificationService->notifyOrderCreated($order);

                // Award points from order items NOW (after payment confirmed)
                $this->pointsService->awardPointsFromOrder($order);

                // Mark product views as purchased NOW (after payment confirmed)
                $this->markProductViewsAsPurchased($order);

                // If order has OTO order ID, set status to PROCESSING
                if (!empty($order->oto_order_id)) {
                    // Only update if status is not already PROCESSING or higher
                    $originalStatus = $order->getOriginal('status');
                    if (!in_array($originalStatus, [
                        Order::STATUS_PROCESSING,
                        Order::STATUS_SHIPPED,
                        Order::STATUS_IN_PROGRESS,
                        Order::STATUS_DELIVERED,
                        Order::STATUS_COMPLETED,
                    ])) {
                        // Use saveQuietly to avoid triggering another observer event
                        $order->status = Order::STATUS_PROCESSING;
                        $order->saveQuietly();
                        $statusChangedByUs = true;
                    }
                }
            }
        }

        // Check if order status changed to CANCELLED — restore stock
        if ($order->isDirty('status')) {
            $originalStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            if ($newStatus === Order::STATUS_CANCELLED
                && $originalStatus !== Order::STATUS_CANCELLED) {
                // Only restore stock if the order was previously paid (stock was decremented)
                if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
                    $this->restoreStock($order);
                }
            }
        }

        // Handle status changes (only if status was changed in the original update, not by us)
        if ($order->isDirty('status') && !$statusChangedByUs) {
            $this->notificationService->notifyOrderStatusChanged($order);

            // Send review request email when order is completed
            // Only send if status changed TO completed (not if it was already completed)
            $originalStatus = $order->getOriginal('status');
            if ($order->status === Order::STATUS_COMPLETED
                && $originalStatus !== Order::STATUS_COMPLETED
                && $order->user) {
                $this->sendReviewRequest($order);
            }
        } elseif ($statusChangedByUs) {
            // Manually trigger notification since we used saveQuietly
            $this->notificationService->notifyOrderStatusChanged($order);
        }
    }

    /**
     * Decrement stock for each item in the order
     */
    protected function decrementStock(Order $order): void
    {
        $items = $order->items()->get();

        foreach ($items as $item) {
            if ($item->product_option_id) {
                ProductOption::where('id', $item->product_option_id)
                    ->decrement('quantity', $item->quantity);
            } else {
                Product::where('id', $item->product_id)
                    ->decrement('quantity', $item->quantity);
            }
        }
    }

    /**
     * Restore stock for each item in the order (when order is cancelled)
     */
    protected function restoreStock(Order $order): void
    {
        $items = $order->items()->get();

        foreach ($items as $item) {
            if ($item->product_option_id) {
                ProductOption::where('id', $item->product_option_id)
                    ->increment('quantity', $item->quantity);
            } else {
                Product::where('id', $item->product_id)
                    ->increment('quantity', $item->quantity);
            }
        }
    }

    /**
     * Send review request email for completed order
     */
    protected function sendReviewRequest(Order $order): void
    {
        if (!$order->user || !$order->user->email) {
            return;
        }

        // Get all unique products from order items
        $orderItems = $order->items()->with('product')->get();
        $products = $orderItems->map(function ($item) {
            return $item->product;
        })->filter()->unique('id')->values();

        if ($products->isEmpty()) {
            return;
        }

        // Send review request notification
        $order->user->notify(new OrderCompletedReviewRequest($order, $products));
    }

    /**
     * Mark product views as purchased for products in the order
     */
    protected function markProductViewsAsPurchased(Order $order): void
    {
        if (!$order->user_id) {
            return;
        }

        $productIds = $order->items()->pluck('product_id')->unique();

        if ($productIds->isEmpty()) {
            return;
        }

        // Mark all product views for this user and these products as purchased
        ProductView::where('user_id', $order->user_id)
            ->whereIn('product_id', $productIds)
            ->where('purchased', false)
            ->update(['purchased' => true]);
    }
}

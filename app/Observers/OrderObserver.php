<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\ProductView;
use App\Notifications\OrderCompletedReviewRequest;
use App\Services\NotificationService;

class OrderObserver
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->notificationService->notifyOrderCreated($order);

        // Mark product views as purchased for all products in this order
        $this->markProductViewsAsPurchased($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('status')) {
            $this->notificationService->notifyOrderStatusChanged($order);

            // Send review request email when order is completed
            // Only send if status changed TO completed (not if it was already completed)
            $originalStatus = $order->getOriginal('status');
            if ($order->status === Order::STATUS_COMPLETED
                && $originalStatus !== Order::STATUS_COMPLETED
                && $order->user) {
                $this->sendReviewRequest($order);
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
